<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class ActioncountController extends BaseController {
	// 行为列表
	public function index($request, $response) {
		$response->title = '总行为统计';
		// 设置默认时间
		$default_time = strtotime ( date ( 'Ymd' ) );
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $start_time );
		$response->end_time = date ( 'Y-m-d', $end_time - 1 );
		//
		$response->list = self::getList ( $start_time, $end_time );
		$this->layoutSmarty ( 'index' );
	}

    public function share($request, $response) {
        if(self::isPost()) {

            if($request->start_time && $request->end_time)
                $params['ctime'] = "ctime >= " . strtotime($request->start_time . ' 00:00:00') . " AND ctime <= " . strtotime($request->end_time . ' 23:59:59');

            if($request->user_name) {
                preg_match_all('/[0-9]{11}/', $request->user_name, $_u);
                if($_u[0]) {
                    $_user = \app\dao\UserDao::getSlaveInstance()->nameToIds( $_u[0] );

                    if(!$_user)
                        $this->showError ( "查找用户不存在", "index.php?_c=actioncount&_a=share" );

                    $params['user_id'] = "user_id in(" . implode(',', array_keys($_user)) . ')';
                }
            }

            if($request->sku) {
                preg_match_all('/\w{5,}/', $request->sku, $_g);
                if($_g[0]) {
                    $_gs = array("sku"=>"sku in('".implode("','", $_g[0])."')" );
                    $_goods = \app\dao\GoodsDao::getSlaveInstance()->getAllGoods( $_gs );
                    if( !$_goods )
                        $this->showError ( "查找用户不存在", "index.php?_c=actioncount&_a=share" );

                    foreach($_goods as $g) {
                        $_ids[] = $g['goods_id'];
                    }
                    $params['item_id'] = "item_id in(" . implode(',', $_ids) . ')';
                }
            }

            $params['action'] = "`type`= 'share' and action in('goods', 'order')";
            $list = \app\dao\UserLogDao::getSlaveInstance()->getActionList ( $params );

            $users = $goods = array();
            if($list) {
                foreach ($list as $row) {
                    $user_ids[] = $row['user_id'];
                    $goods_ids[] = $row['item_id'];
                }

                $goods = \app\dao\GoodsDao::getSlaveInstance()->getInfoByGoodsIds($goods_ids);
                $users = \app\dao\UserInfoDao::getSlaveInstance()->getInfoByIds($user_ids);
            }

            ob_start();
            echo '<table border="1">';
            echo '<tr><th>id</th><th>设备号</th><th>user id</th><th>	分享的商品名称</th><th>	sku</th><th>分享时间</th><th>分享位置</th></tr>';
            foreach($list as $item) {//user_id,uuid

                echo '<tr><td>'.$item['id'].'</td><td>'.$item['uuid'].'</td><td>'.$users[$item['user_id']]['user_name'].'</td><td>'.$goods[$item['item_id']]['goods_name'].'</td><td>'.$goods[$item['item_id']]['sku'].'</td><td>'.date('Y/m/d H:i:s', $item['ctime'])
                    .'</td><td>'.$item['action'].'</td></tr>';
            }
            echo "</table>";

            $result = ob_get_clean();

            self::makeExcel($result, '分享商品用户统计'.$request->start_time . '-' . $request->end_time);
        }
        else {
            $this->layoutSmarty();
        }
    }

	public function getList($start_time, $end_time) {
		$ret = array (
				'user' => 0,
				'like' => 0,
				'unlike' => 0,
				'all' => 0,
				'linkers' => 0,
				'sub' => 0,
				'share_pv' => 0,
				'share_uv' => 0,
				'payment_pv' => 0,
				'payment_uv' => 0,
				'notify_pv' => 0,
				'notify_uv' => 0,
				'goods_pv' => 0,
				'goods_uv' => 0,
				'buy_uv' => 0,
				'buy_pv' => 0 
		);
		$flag = array ();
		$list = \app\dao\UserLogDao::getSlaveInstance ()->getActionList ( array (
				"ctime >= " . $start_time . " AND ctime < " . $end_time 
		) );
		foreach ( $list as $val ) {
			if ($val ['type'] == 'love' && $val ['action'] == 'like')
				$ret ['like'] ++;
			
			if ($val ['type'] == 'love' && $val ['action'] == 'unlike')
				$ret ['unlike'] ++;
			
			if ($val ['type'] == 'share' && ($val ['action'] == 'goods' || $val ['action'] == 'order')) {
				$ret ['share_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps ['share_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'goods' && $val ['action'] == 'buy') {
				$ret ['buy_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps ['buy_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'notify' && $val ['action'] == 'set') {
				$ret ['notify_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps ['notify_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'goods' && $val ['action'] == 'info') {
				$ret ['goods_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps ['goods_uv'] [$_id] = $_id;
			}
		}
		
		foreach ( $maps as $_k => $_v ) {
			$ret [$_k] = count ( $_v );
		}
		
		$_user_cnt = self::useCnt ( $start_time, $end_time );
		$ret ['all'] = $_user_cnt ['all'];
		$ret ['sub'] = $_user_cnt ['sub'];
		$ret ['linkers'] = $_user_cnt ['linkers'];
		
		return $ret;
	}
	// 导出两个月数据
	public function leadingout() {
		// 设置开始时间为两个月前
		$start_time = strtotime ( '-2 month' );
		$end_time = strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		// 获取列表
		$businessList = self::getList ( $start_time, $end_time );
		// 设置csv表头
		$arr = array (
				'开始时间',
				'结束时间',
				'总注册数',
				'平均电话好友数',
				'注册数',
				'喜欢总数',
				'取消喜欢总数',
				'分享点击UV(PV)',
				'订单确认页UV(PV)',
				'到货提醒点击UV(PV)',
				'进详情页UV(PV)' 
		);
		
		$_user_cnt = self::useCnt ( $start_time, $end_time );
		$businessList ['all'] = $_user_cnt ['all'];
		$businessList ['sub'] = $_user_cnt ['sub'];
		self::csvTemplate ( $arr, $businessList, $start_time, $end_time );
	}
	public function csvTemplate($arr, $actionList, $start_time, $end_time) {
		$filename = "action(" . date ( 'Y-m-d', $start_time ) . "至" . date ( 'Y-m-d', $end_time - 1 ) . ").csv";
		header ( "Content-Type: application/vnd.ms-excel; charset=GB2312" );
		header ( "Content-Disposition:attachment;filename=" . $filename . "" );
		header ( "Cache-Control: max-age=0" );
		$fp = fopen ( 'php://output', 'a' );
		// 标题
		foreach ( $arr as $i => $val ) {
			$arr [$i] = iconv ( 'utf-8', 'gb2312', $val );
		}
		fputcsv ( $fp, $arr );
		// 内容
		$row = array ();
		$row [] = date ( 'Y-m-d', $start_time );
		$row [] = date ( 'Y-m-d', $end_time - 1 );
		$row [] = $actionList ['like'];
		$row [] = $actionList ['unlike'];
		$row [] = $actionList ['all'];
		$row [] = $actionList ['linkers'];
		$row [] = $actionList ['sub'];
		$row [] = $actionList ['share_uv'] . "(" . $actionList ['share_pv'] . ")";
		$row [] = $actionList ['buy_uv'] . "(" . $actionList ['buy_pv'] . ")";
		$row [] = $actionList ['notify_uv'] . "(" . $actionList ['notify_pv'] . ")";
		$row [] = $actionList ['goods_uv'] . "(" . $actionList ['goods_uv'] . ")";
		
		fputcsv ( $fp, $row );
	}
	public function proxycount($request, $response) {
		$response->title = '代理跳转统计';
		// 设置默认时间为当天
		$default_time = strtotime ( date ( 'Ymd' ) );
		$params ['start_time'] = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$params ['end_time'] = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $params ['start_time'] );
		$response->end_time = date ( 'Y-m-d', $params ['end_time'] - 1 );
		//
		$list = \app\dao\UserLogDao::getSlaveInstance ()->getProxyCount ( $params );
		$response->list = $list;
		$response->params = $params;
		$this->layoutSmarty ( 'proxycount' );
	}
	private function useCnt($start, $end) {
		$ret = array (
				'all' => 0,
				'sub' => 0 
		);
		$pdo = \app\dao\UserDao::getSlaveInstance ()->getpdo ();
		$sql = "select count(*) from ym_user where ctime>=$start and ctime<$end";
		$ret ['sub'] = $pdo->getOne ( $sql );
		
		$sql = "SELECT COUNT(*) FROM `ym_contact_info` WHERE `home_phone` != ''";
		$ret ['linkers'] = $pdo->getOne ( $sql );
		return $ret;
    }
}