<?php
namespace admin\controller;
use \app\common\util\MobileMessage;
use app\dao\OrderDao;
use app\dao\OrderlogDao;
use \sprite\lib\Log;
use \app\service\transfer\kuaidi100srv;
/**
 * 
 * 用于订单状态自动变化的检测类
 * @author daniel
 *
 */
class AutoController extends BaseController{
	protected $_pdo;
	protected $_admin = array('user_id'=>1000, 'user_name'=>'admin');

	//http://admin.ymall.com/index.php?_c=auto&password=liwudian2013
	public function index($request,$response)
	{
		if($request->get('password') == "liwudian2013" ) {//启动密码
			$this->_pdo = \app\dao\AdminDao::getMasterInstance()->getpdo();
			#$this->checkReceivedOrder(); //delete by wanjilong@yoka.com
			$this->checkShippingOrder();
			$this->checkUnpayOrder();
			$this->UnpayWarning();
		}
	}
	
	/**
	 * 
	 * 定期与ERP系统更新所有商品库存数
	 * @param object $request
	 * @param object $response
	 */
	public function updateSKU($request,$response)
	{	
		$data['method']="ecerp.shangpin.get";
		$data['page_size']="50";

        $ret = \app\service\ecerp\request::get($data);
        
        $totalResult= $ret->total_results; //获取商品总数
       
        try{
        	\app\dao\GoodsDao::getMasterInstance()->beginTransaction();
	        for($i=1; $i<=(int)$totalResult/50+1;$i++)
	        {
	        	$data['method']="ecerp.shangpin.get";
				$data['page_size']="50";
	        	$data['page_no'] = (string)$i;
	        
	        	$retStr = \app\service\ecerp\request::get($data);
	        	
	        	
	        	        
	      	    $dataSync = new \app\service\ecerp\OrderSrv();
	      	    $dataSync->ErpSync($retStr);
	      	    
	      	   
	        	}
	        	
	         \app\dao\GoodsDao::getMasterInstance()->commit();	
	    }   
		catch(Exception $e)
        {
        		\app\dao\GoodsDao::getMasterInstance()->rollBack();
        		var_dump($e->message());
        }
        
       \sprite\lib\Debug::log("status","auto update SKU finished");
      
	}

    public function postERPOrder() {

        $pdo = \app\dao\AdminDao::getMasterInstance()->getpdo();
        $order_status = \app\service\OrderSrv::PAYED_ORDER;
        $sql = "select * from ym_order where order_status=".$order_status." and erp_sn='' limit 100";
        $list = $pdo->getRows($sql);
        $ok = $total = 0;
        $msg = '';

        $ecerpObj = new \app\service\ecerp\OrderSrv();

        for($i = 0; $i<count($list); $i++) {
            $sql = "select * from ym_order_extm where order_id=".$list[$i]['order_id'];
            $address = $pdo->getRow($sql);

            $sql = "select * from ym_order_goods where order_id=".$list[$i]['order_id'];
            $goods = $pdo->getRow($sql);

            try{
                $ret = $ecerpObj->submit($list[$i], $goods, $address);
                if($ret) {
                    OrderDao::getMasterInstance()->edit($list[$i]['order_id'], array('erp_sn'=>$ret['erp_sn']));
                    if($ret['erp_sn'] == 'ERP_ERROR') {
                        \app\dao\OrderlogDao::getMasterInstance()->add(
                            array(//order_id,operator,order_status,changed_status,remark,log_time,
                                'order_id'=>$list[$i]['order_id'],
                                'operator'=>'system auto',
                                'order_status'=>'待发货',
                                'changed_status'=>'待发货',
                                'remark'=>'ERP_ERROR:'.$ret['msg'],
                                'log_time'=>time(),
                            )
                        );
                    }
                }
            }
            catch(\Exception $e) { }
        }
    }

    public function log2file($request,$response) {
        $params = array();
        if( !$request->start_time ) {
            $end_time = strtotime ( date ( 'Y/m/d', time () ) );
            $start_time = $params ['end_time'] - 24 * 60 * 60;
        }
        else {
            $start_time = strtotime ( $request->start_time );
            $end_time = strtotime ( $request->end_time );
        }

        /*
        $params = array();
        $params['ctime'] = "ctime >= " . $start_time . " AND ctime < " . $end_time;
        $params['`type`'] = "`type` in('love', 'share', 'goods', 'notify')";

        */
        $sql = "select u.*, p.ctime as _id from ym_user_log u left join ym_push_token p on p.uuid=u.uuid where u.`type` in('love', 'share', 'goods', 'notify') and u.ctime >= " . $start_time . " AND u.ctime < " . $end_time;
        $list = \app\dao\UserLogDao::getSlaveInstance()->getpdo()->getRows($sql);
        ob_start();
        foreach($list as $r) {
            if($r['type'] == 'love' && $r['action'] == 'like') {
                $action_type = 3;
            }
            elseif($r['type'] == 'love' && $r['action'] == 'unlike') {
                $action_type = -3;
            }
            elseif($r['type'] == 'share' && ( $r['action'] == 'goods' || $r['action'] == 'order')) {
                $action_type = 2;
            }
            elseif($r['type'] == 'goods' && $r['action'] == 'info') {
                $action_type = 1;
            }
            elseif($r['type'] == 'notify' && $r['action'] == 'set') {
                $action_type = 5;
            }
            elseif($r['type'] == 'goods' && $r['action'] == 'buy') {
                $action_type = 4;
            }

            if($action_type == 0 || !$r['_id'])
                continue;

            //$key = $r['uuid'] . ',' . $r['item_id'].',' . $action_type;
            $key = $r['_id'] . ',' . $r['item_id'].',' . $action_type;
            if( isset($maps[$key]) )
                continue;

            echo $key . "\n";
        }

        $result = ob_get_clean();
        $file = LOG_PATH  . '/ymall_user_behavior_'.date('Ymd').'.log';
        file_put_contents( $file, $result );
        echo 'ok file:'.$file;
    }
	
	
	
	/**
	 *  已发货订单10天后自动转已完成
	 */
	protected function checkShippingOrder()
	{
        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'start|______|__checkShippingOrder________'."\n\n"
        );
		$addTime = 10 * 24 * 60 * 60; //day to second
		$order_status = \app\service\OrderSrv::SHIPPING_ORDER;
		$sql = "select order_id from ym_order where order_status=".$order_status." and order_time < unix_timestamp()-$addTime";
		//var_dump($sql);
		$rs = $this->_pdo->getRows($sql);
        $ok = $total = 0;
        $msg = '';
		if($rs && count($rs) > 0)
		{
            $total++;
            $order = new \app\service\OrderSrv();
			foreach($rs as $oneR)
			{
                $order_id = $oneR['order_id'];
                try{
                    $order->finished($order_id, $this->_admin['user_id']);
                    $ok++;
                }
                catch(\Exception $e) { $msg .= $e->getMessage() . "\n"; }
			}

            $msg = 'total:'.$total.', ok:'.$ok . $msg;
            Log::customLog(
                'order_auto_'.date('Ymd').'.log',
                'info|______|________'.$msg."\n\n"
            );
		}
        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'end|______|__checkShippingOrder________'."\n\n"
        );
	}
	
	/**
	 * 
	 * 已收货订单7天后自动转“已完成”
	 */
	protected function checkReceivedOrder()
	{
		$addTime = 7 * 24 * 60 * 60;
		$order_status = \app\service\OrderSrv::RECEIVED_ORDER;
		$sql = "select order_id from ym_order where order_status=".$order_status." and order_time < unix_timestamp()-$addTime";
		$rs = $this->_pdo->getRows($sql);
		if($rs && count($rs) > 0)
		{
			$order = new \app\service\OrderSrv();
			foreach($rs as $oneR)
			{
				$order_id = $oneR['order_id'];
				$order->finished($order_id,$this->sys_name);
			}
		}
	}
	
	/**
	 * 
	 * 待付款订单 48小时 转 已取消
	 */
	
	protected function checkUnpayOrder()
	{
        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'start|______|__checkUnpayOrder________'."\n\n"
        );

        $addTime = 2 * 24 * 60 * 60;
		$order_status = \app\service\OrderSrv::UNPAY_ORDER;
		$sql = "select order_id from ym_order where order_status=".$order_status." and order_time < unix_timestamp()-$addTime";
		$rs = $this->_pdo->getRows($sql);

        $ok = $total = 0;
        $msg = '';
		if($rs && count($rs) > 0)
		{
            $total++;
            $order = new \app\service\OrderSrv();
			foreach($rs as $oneR)
			{
				$order_id = $oneR['order_id'];
                try{
                    $order->cancel($order_id, $this->_admin['user_id'], '超过48小时未支付，订单自动取消');
                    $ok++;
                }
                catch(\Exception $e) { $msg .= $e->getMessage() . "\n"; }
			}
            $msg = 'total:'.$total.', ok:'.$ok . $msg;
            \sprite\lib\Log::customLog(
                'order_auto_'.date('Ymd').'.log',
                'info|______|________'.$msg."\n\n"
            );
		}

        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'end|______|__checkUnpayOrder________'."\n\n"
        );
	}
	
	/**
	 * 待付款订单 24小时转 短信提醒
	 * 选择区间之前 22小时30分-23小时30分内的订单 
	 */
	protected function UnpayWarning()
	{

        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'start|______|__UnpayWarning________'."\n\n"
        );

        $_time = time();
        $end = $_time - 1 * 24 * 60 * 60;

        $lock_key = 'app_unpay_warning';
        $lock = \app\dao\SettingDao::getMasterInstance()->find($lock_key);
        if(!$lock) {
            $lock['uvalue'] = $_time - 1  * 24 * 60 * 60 - 60 * 60;
            $lock['ctime'] = $lock['ctime'] = $_time;
            $lock['ukey'] = $lock_key;
        }
        $start = $lock['uvalue'];
        $lock['uvalue'] = $end;
        $lock['ctime'] = $_time;
        \app\dao\SettingDao::getMasterInstance()->replace($lock);

		$order_status = \app\service\OrderSrv::UNPAY_ORDER;
		/**
			修改时间：2013-08-28
			只给app发短信
		 */
		$sql = "select order_id, buyer_id, buyer_name from ym_order where order_status=".$order_status." and order_time>=$start and order_time<$end and type='app'";
		$rs = $this->_pdo->getRows($sql);
        $ok = $total = 0;
        $msg = '';

        $mobileMessage = new MobileMessage();

		if($rs && count($rs) > 0) {
			foreach($rs as $oneR) {
                $total++;
                $goodsSearch = \app\dao\OrderGoodsDao::getSlaveInstance()->find(Array('order_id'=>$oneR['order_id']));
				$text ="您购买的".$goodsSearch['goods_name']."就要被取消订单了，请快去付款哦，点击v.ymall.com快速进入~【YMALL礼物店】";
				
				if($oneR['buyer_name']) {
                    try{
                        $mobileMessage->send($oneR['buyer_name'], $text);
                        $ok++;
                    }catch(\Exception $e) { $msg .= $e->getMessage() . "\n"; }
				}
                $msg = 'total:'.$total.', ok:'.$ok . $msg;
                Log::customLog(
                    'order_auto_'.date('Ymd').'.log',
                    'info|______|________'.$msg."\n\n"
                );
			}
		}

        Log::customLog(
            'order_auto_'.date('Ymd').'.log',
            'end|______|__UnpayWarning________'."\n\n"
        );
	}

	
	/**
	 * 
	 * 同步ERP订单发货信息
	 */
    public function SyncShip() {
        $orderSrv = new \app\service\OrderSrv();

        $params = array('order_status'=>'order_status='.$orderSrv::PAYED_ORDER. " and erp_sn>'' and erp_sn<>'ERP_ERROR' and shipping_code is null"); //$orderSrv
        //$params = array('order_sn'=>"order_sn in('1326551886', '1326121326', '1326464826')"); //$orderSrv

        $list = \app\dao\OrderDao::getSlaveInstance()->orderList($params);
        if(!$list)
            return ;

        $ecerpObj = new \app\service\ecerp\OrderSrv();

        $mobileMessage = new MobileMessage();

        foreach($list as $row) {
            try{
                $extm = \app\dao\OrderExtmDao::getSlaveInstance()->find($row['order_id']);
                try{
                    $ret = $ecerpObj->getOrder($row['erp_sn']);
                }
                catch(\Exception $e) {//合并订单处理步骤
                    $merge_list = $ecerpObj->getOrdersByBuyer( $extm['consignee'] ); //合并订单同步处理
                    if($merge_list) {
                        foreach($merge_list as $_sn=>$_erp_sn) {
                            OrderDao::getMasterInstance()->edit(array('order_sn'=>$_sn, 'order_status'=>$orderSrv::PAYED_ORDER), array('erp_sn'=>$_erp_sn));
                        }
                    }
                    throw $e;
                }

                $ret['order_id'] = $row['order_id'];
                $ret['order_sn'] = $row['order_sn'];
                $ret['user_name'] = 'system';

                $orderSrv->ship($ret);

                $goods = \app\dao\OrderGoodsDao::getSlaveInstance()->find(array('order_id'=>$row['order_id']));
                $text ="您购买的".$goods['goods_name']."已经发货，敬请期待吧，点击v.ymall.com快速进入~【YMALL礼物店】";

                $mobileMessage->send($extm['phone_mob'], $text);
                
                //给快递100发通知
                $shipping_name = '';
                switch($ret['shipping_name'])
                {
                	case "顺丰快递":
                		$shipping_name = "shunfeng";break;
                	case "顺丰":	
                		$shipping_name = "shunfeng";break;
                	case "圆通快递":
                		$shipping_name = "yuantong";break;
                	case "圆通":
                		$shipping_name = "yuantong";break;
                    case "申通":
                    case "申通快递":
                		$shipping_name = "shentong";break;

                	default:
                		$shipping_name = $ret['shipping_name'];
                		
                }
                $data['shipping_name'] = $shipping_name  ;
                
				$data['shipping_code'] = $ret['shipping_code'] ;
				$data['region_name']   = $extm['region_name'] ;
				//$data['from_region']   = '福建台州' ; //可选参数
				\sprite\lib\Log::customLog(
           		 'Transfer_auto_'.date('Ymd').'.log',
            	 '__Transfer_CallBack________success :'.$ret['order_id']."\n\n"
     		    );

				$postOrderIns = new kuaidi100srv();
				$postOrderIns->postOrder($data);

            }catch (\Exception $e) {
                continue;
            }
        }
    }


    public function autoSaleGoods() {
        $status = \app\dao\GoodsDao::BUY_STATUS;
        $where = 'sale_time<'.time() . ' and status=24';
        \app\dao\GoodsDao::getMasterInstance()->editStatus($status, $where);
    }
}