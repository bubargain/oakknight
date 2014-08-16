<?php

namespace touch\controller;

use \app\service\LoveSrv;

class LoveController extends BaseController {
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
	}
	public function index($request, $response) {
		$response->title = "我的喜欢";
		$this->checkLogin ( 'index.php?_c=love' );
		
		$page = $request->get ( 'page', 1 );
		$size = $request->get ( 'size', 10 );
		//
		$LoveSrv = new LoveSrv ();
		$total = $LoveSrv->getMyCnt ( $this->current_user ['user_id'] );
		if ($total) {
			$limit = ($page - 1) * $size . ',' . $size;
			$sort = 'w.ctime desc';
			$ret ['list'] = $LoveSrv->getMyList ( $this->current_user ['user_id'], $limit, $sort );
		}
		$ret ['cur_page'] = $page;
		$ret ['pages'] = ceil ( $total / $size );
		$ret ['prev'] = ($page <= 1) ? false : $page - 1;
		$ret ['next'] = ($ret ['pages'] <= $page) ? false : $page + 1;
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		$response->cur_url = $url;
		$response->ret = $ret;
		$this->layoutSmarty ( 'index' );
	}
	public function wishes($request, $response) {
		if (! $this->has_login) {
			$this->renderJson ( array (
					'ret' => array (
							'status' => 300,
							'data' => 'index.php?_c=login' 
					) 
			) );
		} else {
			$is_delete = $request->type == 'love' ? 0 : 1;
			$goods_id = $request->goods_id;
			try {
				$ret = \app\service\LoveSrv::setLoveByUid ( $this->current_user ['user_id'], $goods_id, $is_delete );
				if ($ret ['status']) { // 增加统计日志
					self::userLog ( array (
							'user_id' => $this->current_user ['user_id'],
							'type' => 'love',
							'action' => $is_delete ? 'unlike' : 'like',
							'item_id' => $request->goods_id 
					) );
				}
			} catch ( \Exception $e ) {
				$this->renderJson ( array (
						'ret' => array (
								'status' => 10000,
								'data' => '添加喜欢-操作内部错误' 
						) 
				) );
			}
			$this->renderJson ( array (
					'ret' => array (
							'status' => 200,
							'data' => $ret ['wishes'] 
					) 
			) );
		}
	}
}