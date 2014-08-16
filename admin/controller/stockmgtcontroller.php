<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class StockmgtController extends BaseController {
	public function index($request, $response) {
		$response->title = '商品实时库存';
		// 设置默认时间为两月前
		$default_time = strtotime ( '-2 month' );
		$start_time = $request->start ? $request->start : 0;
		$end_time = $request->end ? $request->end : 9999;
		//
		$response->start_time = $start_time ;
		$response->end_time =  $end_time ;
		//
		$response->list = self::getList ( $start_time, $end_time );
		$this->layoutSmarty ( 'index' );
	}
	
	public function getList($start_time, $end_time) {
		$list = array ();
		$params ['stock'] = "stock >= " . $start_time . " AND stock < " . $end_time;
		$result = \app\dao\GoodsDao::getSlaveInstance ()->getList( $params,'0,1000','stock asc' );
		//var_dump( $result);die();
		//
		foreach ( $result as $val ) {
			//$add_time = date ( 'Ymd', $val ['add_time'] );
			//
			$list[]=array('goods_id'=>$val['goods_id'],'erp_id'=>$val['erp_id'],'goods_name'=>$val['goods_name'],'stock'=>$val['stock']);
			
		}
		//var_dump($list);die();
		return $list;
	}
	// 导出两个月数据
	public function leadingout() {
		// 设置开始时间为两个月前
		$start_time = 0;
		$end_time = 9999;
		// 获取列表
		$saleList = self::getList ( $start_time, $end_time );
		// 设置csv表头
		$arr = array (
				'商品ID',
				'商品编码',
				'商品名称',
				'库存数'
		);
		self::csvTemplate ( $arr, $saleList, $start_time, $end_time );
	}
	public function csvTemplate($arr, $saleList, $start_time, $end_time) {
		$filename = "stock_of_all.csv";
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
			
			$list [$k] ['goods_id'] = $v ['goods_id'];
			$list [$k] ['erp_id'] = $v ['erp_id'];
			$list [$k] ['goods_name'] = iconv ( 'utf-8', 'gb2312',$v ['goods_name']);
			
			$list [$k] ['stock'] = $v ['stock'];
			
		}
		foreach ( $list as $vs ) {
			fputcsv ( $fp, $vs );
		}
	}
}