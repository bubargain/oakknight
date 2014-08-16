<?php

namespace admin\controller;

use app\common\util\subpages;

class TextlinkController extends BaseController {
	public function index($request, $response) {
		$response->title = '文字链接列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->text_title) {
			$params ['title'] = "title LIKE '%" . trim ( $request->text_title ) . "%'";
			$response->text_title = $request->text_title;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&text_title=' ) === false) {
				$extUrl .= "&text_title=" . trim ( $request->text_title );
			}
		}
		if ($request->loc_id) {
			$params ['loc_id'] = "b.loc_id = " . intval ( $request->loc_id );
			$response->loc_id = intval ( $request->loc_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&loc_id=' ) === false) {
				$extUrl .= "&loc_id=" . intval ( $request->loc_id );
			}
		} else {
			$params ['loc_id'] = "b.loc_id > 0 AND b.loc_id <> 4";
			if (strpos ( $_SERVER ['REQUEST_URI'], '&loc_id=' ) === false) {
				$extUrl .= "&loc_id=" . intval ( $request->loc_id );
			}
		}
		if ($request->status || $request->status === '0') {
			$params ['status'] = "b.status = " . intval ( $request->status );
			$response->status = intval ( $request->status );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&status=' ) === false) {
				$extUrl .= "&status=" . intval ( $request->status );
			}
		}
		$total = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] ) . $extUrl;
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getList ( 'sort ASC, id ASC', $params, $limit );
		}
		$response->list = $list;
		$response->page = $page->GetPageHtml ();
		$response->cdn_ymall = CDN_YMALL;
		$response->locationList = \app\dao\CmsLocationDao::getSlaveInstance ()->getList ();
		$this->layoutSmarty ( 'index' );
	}
	public function add($request, $response) {
		$response->title = '添加/修改记录';
		if (self::isPost ()) {
			if ($request->loc_id && $request->title) {
				$params = array (
						'title' => $request->title,
						'loc_id' => $request->loc_id,
						'utype' => '',
						'alt' => '',
						'sort' => $request->sort,
						'parent_id' => $request->parent_id,
						'status' => $request->status,
						'url' => $request->url 
				);
			} else {
				$this->showError ( "信息填写不完整" );
			}
			if ($request->id) {
				$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->edit ( $request->id, $params );
			} else {
				$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->add ( $params );
			}
			if (! $result) {
				$this->showError ( '保存信息失败' );
			}
			header ( "Location: index.php?_c=textlink&_a=index" );
		} else {
			if ($request->id) {
				$info = \app\dao\CmsTextlinkDao::getSlaveInstance ()->find ( $request->id );
			}
			$response->cdn_ymall = CDN_YMALL;
			$response->info = $info;
			$response->locationList = \app\dao\CmsLocationDao::getSlaveInstance ()->getList (array('status = 1'));
			$this->layoutSmarty ( 'add.form' );
		}
	}
	public function delete($request, $response) {
		$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->delete ( $request->id );
		if (! $result) {
			$this->showError ( '删除失败' );
		}
		header ( "Location: index.php?_c=textlink&_a=index" );
	}
}