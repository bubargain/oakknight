<?php

namespace admin\controller;

use app\common\util\subpages;

class LocationController extends BaseController {
	public function index($request, $response) {
		$response->title = '位置列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->ukey) {
			$params ['ukey'] = "ukey LIKE '%" . trim ( $request->ukey ) . "%'";
			$response->ukey = $request->ukey;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&ukey=' ) === false) {
				$extUrl .= "&ukey=" . trim ( $request->ukey );
			}
		}
		if ($request->type) {
			$params ['type'] = "type = '" . trim ( $request->type ) . "'";
			$response->type = $request->type;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&type=' ) === false) {
				$extUrl .= "&type=" . trim ( $request->type );
			}
		}
		if ($request->status || $request->status === '0') {
			$params ['status'] = "status = " . intval ( $request->status );
			$response->status = intval ( $request->status );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&status=' ) === false) {
				$extUrl .= "&status=" . intval ( $request->status );
			}
		}
		$response->list = \app\dao\CmsLocationDao::getSlaveInstance ()->getList ($params);
		$this->layoutSmarty ( 'index' );
	}
	public function add($request, $response) {
		$response->title = '添加/修改位置';
		if (self::isPost ()) {
			if ($request->ukey) {
				$params = array (
						'ukey' => $request->ukey,
						'type' => $request->type,
						'info' => $request->info,
						'status' => $request->status 
				);
			} else {
				$this->showError ( "信息填写不完整" );
			}
			if ($request->loc_id) {
				$result = \app\dao\CmsLocationDao::getMasterInstance ()->edit ( $request->loc_id, $params );
			} else {
				$result = \app\dao\CmsLocationDao::getMasterInstance ()->add ( $params );
			}
			if (! $result) {
				$this->showError ( '保存信息失败' );
			}
			header ( "Location: index.php?_c=location&_a=index" );
		} else {
			if ($request->loc_id) {
				$info = \app\dao\CmsLocationDao::getSlaveInstance ()->find ( $request->loc_id );
			}
			$response->info = $info;
			$this->layoutSmarty ( 'add.form' );
		}
	}
	public function delete($request, $response) {
		// 先判断是否有关联记录
		$ret1 = \app\dao\CmsTextlinkDao::getSlaveInstance ()->findByField ( 'loc_id', $request->loc_id );
		$ret2 = \app\dao\CmsImagelinkDao::getSlaveInstance ()->findByField ( 'loc_id', $request->loc_id );
		if ($ret1 || $ret2) {
			$this->showError ( '请先删除该位置下的文字链接或者图片链接' );
		}
		$result = \app\dao\CmsLocationDao::getMasterInstance ()->delete ( $request->loc_id );
		if (! $result) {
			$this->showError ( '删除失败' );
		}
		header ( "Location: index.php?_c=location&_a=index" );
	}
}