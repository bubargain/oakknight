<?php
/*
 * 商家后台订单管理
 * @author: daniel
 */
namespace shop\controller;

use app\dao\UserInfoDao;
use app\dao\GcategoryDao;

use app\dao\OrderDao;
use app\dao\OrderGoodsDao;
use app\dao\OrderExtmDao;
use app\service\OrderSrv;
use app\common\util\SubPages;
use app\common\util\MobileMessage;
use app\service\transfer\kuaidi100srv;

class OrdersController extends BaseController {
	protected $pdo;
	
	/**
	 * 设置快递信息
	 * @param unknown_type $reuqest
	 * @param unknown_type $response
	 */
	public function set_shipping_order($request,$response)
	{
		//echo '1';
		
		if( $request->post('shipping_order_id') && $request->post('shipping_order_sn') && $request->post('shipping_co'))
		{
			//$data['order_id'] = $request->shipping_order_id;
			$data['order_id'] = $request->shipping_order_id;
			$data['shipping_code'] =$request->shipping_order_sn;
			$data['shipping_name'] =$request->shipping_co;
			$data['user_name'] = $this->current_user['user_name'];
			//$data['order_status'] = \app\service\OrderSrv::SHIPPING_ORDER;
			//$rs =\app\dao\OrderDao::getMasterInstance()->edit($request->shipping_order_id,$data);
			$Order = new \app\service\OrderSrv();
			$Order->ship($data);
			
            $goodsSearch = \app\dao\OrderGoodsDao::getSlaveInstance()->find(Array('order_id'=>$data['order_id']));
            $text ="您购买的".$goodsSearch['goods_name']."已经发货，敬请期待吧~【YMALL礼物店】";
            $tmpRs=\app\dao\OrderExtmDao::getSlaveInstance()->find($data['order_id']);
            $phone = $tmpRs['phone_mob'];
            \sprite\lib\Debug::log('phone',$phone);
            $mobileMessage = new MobileMessage();
            $mobileMessage->send($phone, $text);

            $shipping_name = '';
            switch($data['shipping_name'])
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
                    $shipping_name = $data['shipping_name'];

            }
            $post = array();
            $post['shipping_name'] = $shipping_name  ;
            $post['shipping_code'] = $data['shipping_code'];
            $post['region_name']   = $tmpRs['region_name'] ;
            //$data['from_region']   = '福建台州' ; //可选参数
            \sprite\lib\Log::customLog(
                'Transfer_auto_'.date('Ymd').'.log',
                '__Transfer_CallBack________success :'.$data['order_id']."\n\n"
            );

            try{
                $postOrderIns = new kuaidi100srv();
                $postOrderIns->postOrder($post);
            }
            catch(\Exception $e) {
                throw $e;
            }

            return True;
			
		}
		else 
			return false;
		
	}
	
	/**
	 * 取消订单
	 * @param user_id :  当前登录用户的id
	 * @param unknown_type $response
	 */
	public function cancel_order($request,$response)
	{
		$order_id = $request->order_id;
		$user_id = $this->current_user['user_id']; 
		//$request->user_id;
		$order=new \app\service\OrderSrv();
		$order->cancel($order_id,$user_id);
	}
	
	public function print_order($request,$response)
	{
		if($request->order_id)
		{
			$order_id = $request->order_id;
			$order = new \app\service\OrderSrv();
			$rs = $order->queryOrderDetail($order_id);
			$response->data = $rs;
			//var_dump($rs);
			$this->renderSmarty();
		}
	}
	
	
	public function orderinfo($request,$response)
	{
		$order = new \app\service\OrderSrv();
		$order_id =$request->order_id;
		$rs=$order->queryOrderDetail($order_id);
		$response->order = $rs;
		$response->CDN_YMALL = CDN_YMALL;
		//var_dump($rs);
		switch($rs['order_status'])
		{
			case OrderSrv::CLOSED_ORDER:
				$status = '已取消';
				break;
			case OrderSrv::FINISHED_ORDER:
				$status = '已关闭';
				break;
			case OrderSrv::PAYED_ORDER:
				$status= '已支付';
				break;
            /*
			case OrderSrv::RECEIVED_ORDER:
				$status = '已收货';
				break;
            */
			case OrderSrv::SHIPPING_ORDER:
				$status = '配送中';
				break;
			case OrderSrv::UNPAY_ORDER:
				$status = '未支付';
				break;
		}
		$response->status = $status;
		
		$response->log = \app\dao\OrderlogDao::getSlaveInstance()->findByField('order_id',$order_id);
		
		//var_dump($rs);
		//$this->setLayout ("default2");
		$this->renderSmarty();
	}
	
	public function index($request,$response)
	{
		$where = Array();
        $where['seller_id'] = $this->current_user['user_id'];
		if($request->searchBox == 'order_sn') {
            $where['order_sn'] = "order_sn ='$request->searchContent'";
        }
		else if($request->searchBox == 'buyer_name') 
		{
			$where['buyer_name'] = "buyer_name ='$request->searchContent'";
		}
		
		switch($request->status){
			case 'unpay':
                $where['order_status'] = "order_status =".OrderSrv::UNPAY_ORDER;
                $this->unpay($request,$response);
				break;
			case 'unsend':
                $where['order_status'] = "order_status =".OrderSrv::PAYED_ORDER;
				$this->unsend($request,$response);
				break;
			case 'sended':
                $where['order_status'] = "order_status =".OrderSrv::SHIPPING_ORDER;
				$this->sended($request,$response);
				break;
            /**/
			case 'warning':
                $where['order_status'] = "order_status =".OrderSrv::PAYED_ORDER;
                $where['erp_sn'] = "erp_sn ='ERP_ERROR'";
				$this->warning($request,$response);
				break;

			case 'finished':
                $where['order_status'] = "order_status =".OrderSrv::FINISHED_ORDER;
				$this->finished($request,$response);
				break;
			case 'canceled':
                $where['order_status'] = "order_status =".OrderSrv::CLOSED_ORDER;
				$this->canceled($request,$response);
				break;			
		}

        $start = $request->start_time;
        $end = $request->end_time;
        if($start)
            $where[] = "add_time >=".strtotime($start);

        if($end)
            $where[] = "add_time <".strtotime($end) + 24*3600;

        $orderDao = \app\dao\OrderDao::getSlaveInstance();
		$total =  $orderDao->orderListCnt($where);

        $page_size = 20;
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		// 分页对象

		$page = new SubPages( $url, $page_size, $total, $curPageNum );
		$limit = $page->GetLimit() ;
		
		$response->page = $page->GetPageHtml();

		$response->tableCon = $orderDao->orderList($where, $limit);

		$response->_tag = $request->status;
		$this->layoutSmarty();
	}
	
	protected function unpay($request, &$response){
		$response->status = '待付款订单';
		$response->btn = '取消订单';
	}
	
	protected function unsend($request, &$response){
		$response->status = '待发货订单';
		$response->btn = '发货';
	}
	protected function sended($request, &$response){
		$response->status = '已发货订单';
		
	}
	protected function canceled($request, &$response){
		$response->status = '已取消订单';
		
	}
	protected function finished($request, &$response){
		$response->status = '已完成订单';
	}

    protected function warning($request, &$response){
        $response->status = 'erp 同步失败';
    }

    public function saleDown($request, $response) {
        //手机	管理员备注	配送方式	物流号	商品ID	一级分类	二级分类	品牌名称	商品名称	数量	成本价
        if(self::isPost()) {
			$params = array();
            $params['seller_id'] = $this->current_user['user_id'];
            if($request->start_time)
                $params['start_time'] = 'pay_time>='.strtotime( $request->start_time . ' 00:00:00' );
            if($request->end_time)
                $params['end_time'] = 'pay_time<='.strtotime( $request->end_time  . ' 23:59:59');

            $params['seller'] = 'seller_id='.$this->current_user['user_id'];

            $orderSrv = new OrderSrv();
            $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
            /* 取得满足条件的订单列表 */
            $orders = OrderDao::getSlaveInstance()->orderList($params, '0, 10000');

            if(!$orders)
                return array();

            $list = $order_ids = array();
            foreach($orders as $r) {
                $order_ids[] = $r['order_id'];
                $list[$r['order_id']] = $r;
            }
            unset($orders);

            /* 取得满足条件的订单商品列表 */
            $order_goods = OrderGoodsDao::getSlaveInstance()->getGoodsByOrderIds($order_ids);
            foreach( $order_goods as $r) {
                $list[$r['order_id']]['goods'][] = $r;

                $cate_ids[$r['cate_id_1']] = $r['cate_id_1'];
                $cate_ids[$r['cate_id_2']] = $r['cate_id_2'];
            }

            unset($order_goods);

            /* 取得收货人手机号码 */
            $extm = OrderExtmDao::getSlaveInstance()->getInfoByOrderIds($order_ids);

            /* 取得收货人手机号码 */
            $cate_info = GcategoryDao::getSlaveInstance()->getInfoByIds($cate_ids);

            ob_start();
            echo '<table>';
            echo '<tr><th>order_sn</th><th>手机</th><th>支付时间</th><th>管理员备注</th><th>快递公司</th><th>物流号</th><th>订单金额</th><th>商品ID</th><th>一级分类</th><th>二级分类</th><th>商品名称</th><th>数量</th><th>成本价</th></tr>';

            foreach($list as $r) {
                $item_num = count($r['goods']);
                if($item_num >1 )
                    $_rowspan = ' rowspan="'.$item_num.'"';

                $_id = $r['order_id'];

                $pre = '<tr><td'.$_rowspan.'>'.$r['order_sn'].'</td><td'.$_rowspan.'>'.$extm[$_id]['phone_mob'].'</td><td'.$_rowspan.'>'.date('Y/m/d H:i:s', $r['pay_time']).'</td><td'.$_rowspan.'>'.$r['postscript'].'</td>'
                    .'<td'.$_rowspan.'>'.$r['shipping_name'].'</td><td'.$_rowspan.'>'.$r['shipping_code'].'</td><td'.$_rowspan.'>'.$r['order_amount'].'</td>';

                foreach($r['goods'] as $g) {
                    echo $pre . '<td>'.$g['goods_id'].'</td><td>'.$cate_info[$g['cate_id_1']]['cate_name'].'</td><td>'.$cate_info[$g['cate_id_2']]['cate_name']
                        .'</td><td>'.$g['goods_name'].'</td><td>'.$g['quantity'].'</td><td>'.$g['cost_price'].'</td></tr>';

                    $pre = '<tr>';
                }
            }

            $result = ob_get_clean();

            self::makeExcel($result, '订单销售报表'.date('Y/m/d'));

        } else {
            $params['end_time'] = strtotime ( date ( 'Y/m/d', time () ) );
            $params['start_time'] = $params ['end_time'] - 24 * 60 * 60;

            $response->params = $params;
            $response->_tag = $this->_action;
            $this->layoutSmarty();
        }
    }

    protected function makeExcel($string, $title) {
        $result_str = '<head><meta http-equiv="Content-Type" content="text/html;charset=gb2312"></head>' . $string;
        header ( 'Content-Transfer-Encoding: gbk' );
        header ( 'Content-Type: application/vnd.ms-excel;' );
        header ( "Content-type: application/x-msexcel" );
        header ( iconv ( 'UTF-8', 'GBK//IGNORE', 'Content-Disposition: attachment; filename="' . $title . '.xls"' ) );
        echo iconv('UTF-8', 'GBK//IGNORE', $result_str);
    }

    protected function initMenu() {
        return array(
            0=>array('url'=>'index.php?_c=orders&status=warning', 'tag'=>'warning', 'title'=>'E同步失败'),
            1=>array('url'=>'index.php?_c=orders&status=unpay', 'tag'=>'unpay', 'title'=>'待付款订单'),
            2=>array('url'=>'index.php?_c=orders&status=unsend', 'tag'=>'unsend', 'title'=>'待发货订单'),
            3=>array('url'=>'index.php?_c=orders&status=sended', 'tag'=>'sended', 'title'=>'已发货订单'),
            4=>array('url'=>'index.php?_c=orders&status=finished', 'tag'=>'finished', 'title'=>'已完成订单'),
            5=>array('url'=>'index.php?_c=orders&status=canceled', 'tag'=>'canceled', 'title'=>'已取消订单'),
            6=>array('url'=>'index.php?_c=orders&_a=saledown', 'tag'=>'saledown', 'title'=>'销售导出'),
            7=>array('url'=>'index.php?_c=refund', 'tag'=>'refund', 'title'=>'退款管理'),
        );
    }
}