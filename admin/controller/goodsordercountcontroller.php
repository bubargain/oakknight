<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class GoodsordercountController extends BaseController {
	public function index($request, $response) {
		$response->title = '订单商品统计';
		// 处理搜索信息
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : 0;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : 0;
		// 必须输入商品id和查询时间才能获取列表
		if ($start_time && $end_time) {
			if ($request->goods_id) {
				$params ['add_time'] = "order_time >= " . $start_time . " AND order_time < " . $end_time;
				$params ['goods_id'] = "goods_id = " . intval ( $request->goods_id );
				//
				$response->start_time = date ( 'Y-m-d', $start_time );
				$response->end_time = date ( 'Y-m-d', $end_time - 1 );
				$response->list = self::getOrderList ( $start_time, $end_time, $params );
			}
		}
		$this->layoutSmarty ( 'index' );
	}
	public function getOrderList($start_time, $end_time, $params) {
		// 订单统计列表
		$list = self::getOrderCntList ( $start_time, $end_time, $params );
		// 通过goods_ids获取商品库存
		if (! count ( array_keys ( $list ) )) {
			return false;
		}
		// 构造goos_ids
		$goods_ids = array ();
		foreach ( array_keys ( $list ) as $v ) {
			if ($v) {
				$goods_ids [] = $v;
			}
		}
		// 根据goods_ids获取库存列表
		$stockList = \app\dao\GoodsDao::getSlaveInstance ()->getStockByGoodsIds ( $goods_ids );
		// 整合为商品-订单列表
		$keys = array (
				'add_time',
				'pay_time',
				'pay_colsed_time',
				'uppay_colsed_time',
				'order_amount' 
		);
		foreach ( $list as $key => $val ) {
			foreach ( $keys as $k ) {
				$list [$key] [$k] = isset ( $list [$key] [$k] ) ? $list [$key] [$k] : 0;
			}
			$list [$key] ['stock'] = isset ( $stockList [$key] ['stock'] ) ? $stockList [$key] ['stock'] : 0;
		}
		return $list;
	}
	// 订单统计列表
	public function getOrderCntList($start_time, $end_time, $params) {
		$list = array ();
		// 订单列表
        //goods_id goods_name add_time pay_time uppay_colsed_time pay_colsed_time order_amount stock
		$orderList = \app\dao\OrderGoodsDao::getSlaveInstance ()->getOrderGoodsList ( $params, 'ORDER BY goods_id ASC' );
		$start_time = date ( 'Ymd', $start_time );
		foreach ( $orderList as $val ) {
			$list [$val ['goods_id']] ['goods_id'] = $val ['goods_id'];
			$list [$val ['goods_id']] ['goods_name'] = $val ['goods_name'];
			$add_time = date ( 'Ymd', $val ['add_time'] );

            $list [$val ['goods_id']] ['add_time'] ++;

			if ($val ['pay_time']) {
				$pay_time = date ( 'Ymd', $val ['pay_time'] );
				if ($pay_time >= $start_time && $pay_time < $end_time) {
					// 已支付
					$list [$val ['goods_id']] ['pay_time'] ++;
					// 净销售额
					$list [$val ['goods_id']] ['order_amount'] += $val ['price'] * $val ['quantity'];
				}
			}

			if ($val ['closed_time']) {
				$closed_time = date ( 'Ymd', $val ['closed_time'] );
				if ($closed_time >= $start_time && $closed_time < $end_time) {
					// 已关闭，有支付
					if ($val ['pay_time']) {
						// 已关闭（有支付）
						$list [$val ['goods_id']] ['pay_colsed_time'] ++;
						// 净销售额
						$list [$val ['goods_id']] ['order_amount'] -= $val ['price'] * $val ['quantity'];
					} else {
						// 已关闭（无支付）
						$list [$val ['goods_id']] ['uppay_colsed_time'] ++;
					}
				}
			}
		}
		return $list;
	}
	// 导出半年数据
	public function leadingout() {
		// 设置开始时间为半年前
		$start_time = strtotime ( '2013-08-09' );
		$end_time = strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		$params ['add_time'] = "add_time >= " . $start_time . " AND add_time < " . $end_time;
		// 获取商品-订单数据列表
		$goodsOrderList = self::getOrderList ( $start_time, $end_time, $params );
		// 设置csv表头
		$arr = array (
				'pid',
				'商品名称',
				'开始时间',
				'结束时间',
				'待付款订单数',
				'已支付订单数',
				'已取消订单数（无支付）',
				'已取消订单数（有支付）',
				'净销售金额',
				'库存' 
		);
		self::csvTemplate ( $arr, $goodsOrderList, $start_time, $end_time );
	}
	public function csvTemplate($arr, $goodsOrderList, $start_time, $end_time) {
		$filename = "goods&order(" . date ( 'Y-m-d', $start_time ) . "至" . date ( 'Y-m-d', $end_time - 1 ) . ").csv";
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
		foreach ( $goodsOrderList as $k => $v ) {
			$list [$k] ['pid'] = $v ['goods_id'];
			$list [$k] ['goods_name'] = iconv ( 'utf-8', 'gb2312', $v ['goods_name'] );
			$list [$k] ['start_time'] = date ( 'Y-m-d', $start_time );
			$list [$k] ['end_time'] = date ( 'Y-m-d', $end_time - 1 );
			$list [$k] ['add_time'] = $v ['add_time'];
			$list [$k] ['pay_time'] = $v ['pay_time'];
			$list [$k] ['uppay_colsed_time'] = $v ['uppay_colsed_time'];
			$list [$k] ['pay_colsed_time'] = $v ['pay_colsed_time'];
			$list [$k] ['order_amount'] = $v ['order_amount'];
			$list [$k] ['stock'] = $v ['stock'];
		}
		foreach ( $list as $vs ) {
			fputcsv ( $fp, $vs );
		}
	}
}