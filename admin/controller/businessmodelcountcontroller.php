<?php

namespace admin\controller;

use app\dao\PushTokenDao;
use app\service\ecerp\OrderSrv;
use sprite\mvc\controller;
use \stdClass;

class BusinessmodelcountController extends BaseController {
	// 商业模型统计列表
	public function index($request, $response) {
		$response->title = '商业模型统计';
		// 设置默认时间为当天
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

	public function getList($start_time, $end_time) {

        $ret = array (
            'order' => 0,
            'order_app' => 0,//APP成交订单数
            'order_touch' => 0,//TOUCH成交订单数
            'order_app_quantity' => 0, //APP成交件数
            'order_touch_quantity' => 0,//TOUCH成交件数
            'order_amount' => 0.00,//净营业收入
            'user_amount_avg' => 0.00,//平均客单价
            'order_amount_avg' => 0.00,//平均件单价
            're_buyer' => 0,//重复购买用户
            're_quantity' => 0,//重复购买件数
            're_buyer_rate' => 0.00,//重复购买用户贡献
            're_buyer_quantity' => 0,//重复购买下单件数
        );

		// 获取时间段内已支付且未关闭的订单，由此进行统计
        $orderSrv = new \app\service\OrderSrv();
		$params['pay_time'] = "pay_time >= " . $start_time . " AND pay_time < " . $end_time;
        $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
		$list = \app\dao\OrderDao::getSlaveInstance()->orderList( $params, '0,100000', ' order_id asc');

		if (! $list)
			return $ret;

		foreach ( $list as $row ) {
            $ret['order']++;
            if($row['type'] == 'app') {
                $ret['order_app']++;
            }
            if($row['type'] == 'touch') {
                $ret['order_touch']++;
            }

            $maps['orders'][$row['order_id']] = $row['type'];
            $maps['_buyers'][$row['buyer_id']]++;

            $ret['order_amount'] += $row['order_amount'];
            $ret['goods_amount'] += $row['goods_amount'];
		}

		// 处理重复购买
        $orderSrv = new \app\service\OrderSrv();
        $params['pay_time'] = "pay_time < " . $start_time;
        $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
        $params['buyer_id'] = "buyer_id in( " . implode(',', array_keys($maps['_buyers']) ) . ")";
        $old = \app\dao\OrderDao::getSlaveInstance()->orderList( $params, '0,100000', ' order_id asc');
        if ($old) {
            foreach ( $old as $o ) {
                $maps['old_buyer'][$o['buyer_id']] = 1;
            }
        }


        foreach ( $list as $row ) {
            if($maps['old_buyer'][$row['buyer_id']] || ( $maps['_buyers'][$row['buyer_id']] > 1) ) {
                //$ret['re_buyer']++;
                $maps['re_buyer'][$row['buyer_id']] = 1;
                $maps['re_order'][$row['order_id']] = 1;
            }
        }
        $ret['re_buyer'] = count($maps['re_buyer']);//重复用户
        $ret['re_order'] = count($maps['re_order']);//订单数
        $ret['buyer'] = count($maps['_buyers']);//订单数人数


        $goods_list = \app\dao\OrderGoodsDao::getSlaveInstance()->getGoodsByOrderIds( array_keys($maps['orders']) );
        foreach($goods_list as $r) {
            if( $maps['orders'][$r['order_id']] == 'app') {
                $ret['order_app_quantity'] += $r['quantity'];
            }

            if( $maps['orders'][$r['order_id']] == 'touch') {
                $ret['order_touch_quantity'] += $r['quantity'];
            }

            if(isset($maps['re_order'][$r['order_id']])) {
                $ret['re_quantity'] += $r['quantity'];
            }

            $ret['quantity'] += $r['quantity'];

        }
        $ret['re_buyer_rate'] = round( $ret['re_quantity'] * 100 / $ret['quantity'] , 2) . '%';
        $ret['user_amount_avg'] = round( $ret['goods_amount'] / $ret['order'] , 2);
        $ret['order_amount_avg'] = round( $ret['goods_amount'] / $ret['quantity'] , 2);
        if($ret['re_buyer'])
            $ret['re_buyer_quantity'] = round( $ret['re_quantity'] / $ret['re_buyer'] , 1);

		return $ret;
	}
	// 导出两个月数据
	public function leadingout() {
		// 设置开始时间为两个月前
		$start_time = strtotime ( '-2 month' );
		$end_time = strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		// 获取列表
		$ret = self::getList ( $start_time, $end_time );

        ob_start();
        echo '<table border="1">';
        echo '<tr><td colspan="11">' . date('Y/m/d', $start_time) . ' 至 '.date('Y/m/d', $end_time). '</td></tr>';
        echo '<tr><th>APP成交订单数</th><th>TOUCH成交订单数</th><th>APP成交件数</th><th>TOUCH成交件数</th><th>净营业收入</th><th>平均客单价</th><th>平均件单价</th><th>重复购买用户</th><th>重复购买件数</th><th>重复购买用户贡献</th><th>重复购买下单件数</th></tr>';

        echo "<tr><td>{$ret['order_app']}</td><td>{$ret['order_touch']}</td><td>{$ret['order_app_quantity']}</td><td>{$ret['order_touch_quantity']}</td><td>{$ret['order_amount']}</td><td>{$ret['user_amount_avg']}</td><td>{$ret['order_amount_avg']}</td><td>{$ret['re_buyer']}</td><td>{$ret['re_quantity']}</td><td>{$ret['re_buyer_rate']}</td><td>{$ret['re_buyer_quantity']}</td></tr>";
        $result = ob_get_clean();


        self::makeExcel($result, '商业模型统计'.date('Y/m/d'));
	}
}