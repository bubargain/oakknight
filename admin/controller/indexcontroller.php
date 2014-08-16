<?php

namespace admin\controller;

class IndexController extends BaseController {
	public function index($request, $response) {
		header ( "Location: index.php?_c=store&_a=index" );
	}
	// 错误信息页面
	public function error($request, $response) {
		$response->title = '错误页面';
		$response->showNav = 'no';
		$response->msg = $request->msg;
		$this->layoutSmarty ( 'error' );
	}
}