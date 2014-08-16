<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class GoodsactioncountController extends BaseController {
	public function index($request, $response) {
		$response->title = '商品行为统计';
		// 处理搜索信息
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : 0;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : 0;
		// 必须输入商品id和查询时间才能获取列表
		if ($start_time && $end_time) {
			if ($request->goods_id) {
				$params ['goods_id'] = "item_id = " . intval ( $request->goods_id );
				//
				$response->start_time = date ( 'Y-m-d', $start_time );
				$response->end_time = date ( 'Y-m-d', $end_time - 1 );
				$response->list = self::getList ( $start_time, $end_time, $params );
			}
		}
		$this->layoutSmarty ( 'index' );
	}
	public function getList($start_time, $end_time, $params) {
		$list = self::getActionList ( $start_time, $end_time, $params );
		if (! $list) {
			return $list;
		}
		$where [] = 'goods_id in(' . implode ( ',', array_keys ( $list ) ) . ')';
		$goodsList = \app\dao\GoodsDao::getSlaveInstance ()->getAllGoods ( $where );
		
		$map = array ();
		foreach ( $goodsList as $r ) {
			$map [$r ['goods_id']] = $r;
		}
		
		foreach ( $list as $k => $row ) {
			$list [$k] ['goods_id'] = $k;
            /*
            if(!isset($map [$k])) {
                unset($list[$k]);
                continue;
            }
            */

			$list [$k] ['goods_name'] = $map [$k] ? $map [$k] ['goods_name'] : '已删除';
			$list [$k] ['stock'] = $map [$k] ? $map [$k] ['stock'] : 0;
			$list [$k] ['sku'] = $map [$k] ? $map [$k] ['sku'] : '';
			$list [$k] ['status'] = $map [$k] ['status'] == 12 ? '上架' : '非上架';
		}
		
		return $list;
	}
	
	// 获取商品-行为数据列表
	public function getActionList($start_time, $end_time, $params, $type = '') {
		$ret = array ();
		$params ['ctime'] = "ctime >= " . $start_time . " AND ctime < " . $end_time;
		$result = \app\dao\UserLogDao::getSlaveInstance ()->getActionList ( $params );
		
		if (! $result)
			return array ();
		foreach ( $result as $val ) {
			$_item_id = $val ['item_id'];
			if ($val ['type'] == 'love' && $val ['action'] == 'like')
				$ret [$_item_id] ['like'] ++;
			
			if ($val ['type'] == 'love' && $val ['action'] == 'unlike')
				$ret [$_item_id] ['unlike'] ++;
			
			if ($val ['type'] == 'share' && ($val ['action'] == 'goods' || $val ['action'] == 'order')) {
				$ret [$_item_id] ['share_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps [$_item_id] ['share_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'goods' && $val ['action'] == 'buy') {
				$ret [$_item_id] ['buy_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps [$_item_id] ['buy_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'notify' && $val ['action'] == 'set') {
				$ret [$_item_id] ['notify_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps [$_item_id] ['notify_uv'] [$_id] = $_id;
			}
			
			if ($val ['type'] == 'goods' && $val ['action'] == 'info') {
				$ret [$_item_id] ['goods_pv'] ++;
				
				$_id = $val ['user_id'] ? $val ['user_id'] : $val ['uuid'];
				$maps [$_item_id] ['goods_uv'] [$_id] = $_id;
			}
		}
		
		foreach ( $ret as $k => $row ) {
			foreach ( $maps [$k] as $_k => $_v ) {
				$ret [$k] [$_k] = count ( $_v );
			}
		}
		unset ( $maps );
		return $ret;
	}
	
	// 导出半年数据
	public function leadingout() {
		// 设置开始时间为半年前
		$start_time = strtotime ( '2013-08-09' );
		$end_time = strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		// 获取商品-行为数据列表
		$goodsActionList = self::getList ( $start_time, $end_time, array () );
		// 设置csv表头
		$arr = array (
				'商品id',
				'SKU',
				'商品名称',
				'商品详情',
				'开始时间',
				'结束时间',
				'喜欢总数',
				'取消喜欢总数',
				'分享点击UV(PV)',
				'订单确认页UV(PV)',
				'总到货提醒点击UV(PV)',
				'详情页UV(PV)',
				'库存',
				'状态' 
		);
		self::csvTemplate ( $arr, $goodsActionList, $start_time, $end_time );
	}
	public function csvTemplate($arr, $goodsActionList, $start_time, $end_time) {
		$filename = "goods&action(" . date ( 'Y-m-d', $start_time ) . "至" . date ( 'Y-m-d', $end_time - 1 ) . ").csv";
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
		foreach ( $goodsActionList as $k => $v ) {
			$row = array ();
			//
			$row [] = $v ['goods_id'];
            $row [] = $v ['sku'];
			$row [] = iconv ( 'utf-8', 'gb2312', $v ['goods_name'] );
			$row [] = 'http://touch.ymall.com/index.php?_c=goods&_a=detail&id=' . $v ['goods_id'];
			$row [] = date ( 'Y-m-d', $start_time );
			$row [] = date ( 'Y-m-d', $end_time - 1 );
			$row [] = $v ['like'] ? $v ['like'] : 0;
			$row [] = $v ['unlike'] ? $v ['unlike'] : 0;
			$row [] = ($v ['share_uv'] ? $v ['share_uv'] : 0) . ($v ['share_pv'] ? "(" . $v ['share_pv'] . ")" : "(0)");
			$row [] = ($v ['buy_uv'] ? $v ['buy_uv'] : 0) . ($v ['buy_pv'] ? "(" . $v ['buy_pv'] . ")" : "(0)");
			$row [] = ($v ['notify_uv'] ? $v ['notify_uv'] : 0) . ($v ['notify_pv'] ? "(" . $v ['notify_pv'] . ")" : "(0)");
			$row [] = ($v ['goods_uv'] ? $v ['goods_uv'] : 0) . ($v ['goods_pv'] ? "(" . $v ['goods_pv'] . ")" : "(0)");
			$row [] = $v ['stock'];
			$row [] = iconv ( 'utf-8', 'gb2312', $v ['status'] );
			
			fputcsv ( $fp, $row );
		}
	}
}