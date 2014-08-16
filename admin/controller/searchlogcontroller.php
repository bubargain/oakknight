<?php

namespace admin\controller;

use \app\dao\SearchLogDao;

class SearchLogController extends BaseController {
	
	/**
	 * 关键词日志
	 *
	 * @param
	 *        	$request
	 * @param
	 *        	$response
	 */
	public function index($request, $response) {
		$response->title = '搜索统计';
		// 设置默认时间为当天
		$default_time = strtotime ( date ( 'Ymd' ) );
		$params ['start_time'] = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$params ['end_time'] = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $params ['start_time'] );
		$response->end_time = date ( 'Y-m-d H:i:s', $params ['end_time'] - 1 );
		//
		if ($request->do == 'search') {
			if ($request->keyword)
				$params ['keyword'] = $request->keyword;
		}
		//
		$params ['from'] = 'search';
		$list = SearchLogDao::getSlaveInstance ()->getSearchTop ( $params );
		$response->list = $list;
		$response->params = $params;
		$this->layoutSmarty ( 'count' );
	}
	
	/**
	 * 菜单统计日志
	 *
	 * @param
	 *        	$request
	 * @param
	 *        	$response
	 */
	public function menu($request, $response) {
		$response->title = '菜单点击统计';
		$list = array ();
		// 设置默认时间为当天
		$default_time = strtotime ( date ( 'Ymd' ) );
		$params ['start_time'] = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$params ['end_time'] = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $params ['start_time'] );
		$response->end_time = date ( 'Y-m-d', $params ['end_time'] - 1 );
		//
		$params ['from'] = '';
		$list = SearchLogDao::getSlaveInstance ()->getSearchMenuTop ( $params );
		foreach ( $list as $k => $r ) {
			$r ['params'] = json_decode ( $r ['params'], true );
			$list [$k] ['keyword'] = '';
			foreach ( $r ['params'] as $_k => $_v ) {
				$list [$k] ['keyword'] .= "$_k:$_v;";
			}
			if (! $list [$k] ['keyword'])
				$list [$k] ['keyword'] = '默认首页';
		}
		$response->list = $list;
		$response->params = $params;
		$this->layoutSmarty ( 'index' );
	}
	public function down($request, $response) {
		$params = array ();
		if ($request->get ( 'start' ))
			$params ['start'] = strtotime ( $request->get ( 'start' ) );
		
		if ($request->get ( 'end' ))
			$params ['end'] = strtotime ( $request->get ( 'end' ) );
		
		if ($request->get ( 'keyword' ))
			$params ['keyword'] = strtotime ( $request->get ( 'keyword' ) );
		
		$from = $request->get ( 'from', '' );
		
		$params ['from'] = ($from == 'menu' || $from == '') ? '' : $from;
		
		$list = SearchLogDao::getSlaveInstance ()->getSearchLog ( $params );
		
		ob_start (); // user_id | keyword | params | from | page | ctime
		echo '<table>';
		echo '<tr><th>user_id</th><th>keyword</th><th>page</th><th>ctime</th></tr>';
		foreach ( $list as $row ) {
			$row ['params'] = json_decode ( $row ['params'], true );
			$row ['keyword'] = '';
			foreach ( $row ['params'] as $_k => $_v ) {
				$row ['keyword'] .= "$_k:$_v;";
			}
			if (! $row ['keyword'])
				$row ['keyword'] = '默认首页';
			
			echo '<tr><td>' . $row ['user_id'] . '</td><td>' . $row ['keyword'] . '</td><td>' . $row ['page'] . '</td><td>' . date ( 'Y/m/d H:i:s', $row ['ctime'] ) . '</td></tr>';
		}
		echo '</table>';
		$result = ob_get_clean ();
		
		$title = $request->get ( 'start' ) . '--' . $request->get ( 'end' ) . '统计日志';
		self::makeExcel ( $result, $title );
	}
}