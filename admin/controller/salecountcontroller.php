<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class SalecountController extends BaseController {
	public function index($request, $response) {
		$response->title = '总销售统计';
		// 设置默认时间为两月前
		$default_time = strtotime ( '-2 month' );
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $start_time );
		$response->end_time = date ( 'Y-m-d', $end_time );
		//
		$response->list = self::getList ( $start_time, $end_time );
		$this->layoutSmarty ( 'index' );
	}
	public function getList($start_time, $end_time) {
		$list = array ();
		$params ['add_time'] = "add_time >= " . $start_time . " AND add_time < " . $end_time;
		$result = \app\dao\OrderDao::getSlaveInstance ()->getSaleOrderCnt ( $params );
		//
		foreach ( $result as $val ) {
			$add_time = date ( 'Ymd', $val ['add_time'] );
			// 待付款
			$list [$add_time] ['add_time'] ++;
			//
			if ($val ['pay_time']) {
				$pay_time = date ( 'Ymd', $val ['pay_time'] );
				if ($pay_time == $add_time) {
					// 已支付
					$list [$pay_time] ['pay_time'] ++;
					// 净销售额
					$list [$pay_time] ['order_amount'] += $val ['order_amount'];
				} else {
					// 已支付（过往）
					$list [$pay_time] ['pass_pay_time'] ++;
					// 净销售金额（过往）
					$list [$pay_time] ['pass_order_amount'] += $val ['order_amount'];
				}
			}
			//
			if ($val ['closed_time']) {
				$closed_time = date ( 'Ymd', $val ['closed_time'] );
				if ($closed_time == $add_time) {
					if ($val ['pay_time']) {
						// 已关闭（有支付）
						$list [$closed_time] ['pay_colsed_time'] ++;
						// 净销售额
						$list [$closed_time] ['order_amount'] -= $val ['order_amount'];
					} else {
						// 已关闭（无支付）
						$list [$closed_time] ['uppay_colsed_time'] ++;
					}
				} else {
					if ($val ['pay_time']) {
						// 已关闭（过往）（有支付）
						$list [$closed_time] ['pass_pay_colsed_time'] ++;
						// 净销售金额（过往）
						$list [$closed_time] ['pass_order_amount'] -= $val ['order_amount'];
					} else {
						// 已关闭（过往）（无支付）
						$list [$closed_time] ['pass_uppay_colsed_time'] ++;
					}
				}
			}
		}
		$keys = array (
				'add_time',
				'pay_time',
				'pass_pay_time',
				'pay_colsed_time',
				'uppay_colsed_time',
				'pass_pay_colsed_time',
				'pass_uppay_colsed_time',
				'order_amount',
				'pass_order_amount' 
		);
		$day = ceil ( ($end_time - $start_time) / (24 * 60 * 60) );
		for($i = 0; $i < $day; $i ++) {
			$_idx = $start_time + $i * 24 * 60 * 60;
			$_idx = date ( 'Ymd', $_idx );
			$list [$_idx] ['time'] = $_idx;
			foreach ( $keys as $_k ) {
				$list [$_idx] [$_k] = isset ( $list [$_idx] [$_k] ) ? $list [$_idx] [$_k] : 0;
			}
		}
		krsort ( $list );
		return $list;
	}
	// 导出两个月数据
	public function leadingout() {
		// 设置开始时间为两个月前
		$start_time = strtotime ( '-2 month' );
		$end_time = strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		// 获取列表
		$saleList = self::getList ( $start_time, $end_time );
		// 设置csv表头
		$arr = array (
				'开始时间',
				'结束时间',
				'待付款订单数',
				'已支付订单数',
				'已取消订单数(无支付)',
				'已取消订单数(有支付)',
				'净销售金额',
				'已支付订单数(过往)',
				'已取消订单数(无支付|过往)',
				'已取消订单数(有支付|过往)',
				'净销售金额(过往)',
				'净销售总金额' 
		);
		self::csvTemplate ( $arr, $saleList, $start_time, $end_time );
	}
	public function csvTemplate($arr, $saleList, $start_time, $end_time) {
		$filename = "sale(" . date ( 'Y-m-d', $start_time ) . "至" . date ( 'Y-m-d', $end_time - 1 ) . ").csv";
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
		foreach ( $saleList as $k => $v ) {
			$list [$k] ['start_time'] = date ( 'Y-m-d', strtotime ( $v ['time'] ) );
			$list [$k] ['end_time'] = date ( 'Y-m-d', strtotime ( $v ['time'] ) );
			$list [$k] ['add_time'] = $v ['add_time'];
			$list [$k] ['pay_time'] = $v ['pay_time'];
			$list [$k] ['uppay_colsed_time'] = $v ['uppay_colsed_time'];
			$list [$k] ['pay_colsed_time'] = $v ['pay_colsed_time'];
			$list [$k] ['order_amount'] = $v ['order_amount'];
			$list [$k] ['pass_pay_time'] = $v ['pass_pay_time'];
			$list [$k] ['pass_uppay_colsed_time'] = $v ['pass_uppay_colsed_time'];
			$list [$k] ['pass_pay_colsed_time'] = $v ['pass_pay_colsed_time'];
			$list [$k] ['pass_order_amount'] = $v ['pass_order_amount'];
			$list [$k] ['amount'] = $v ['order_amount'] + $v ['pass_order_amount'];
		}
		foreach ( $list as $vs ) {
			fputcsv ( $fp, $vs );
		}
	}
}