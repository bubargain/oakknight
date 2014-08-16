<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\common\util\subpages;

class FeedBackController extends BaseController {
	// 品牌列表
	public function index($request, $response) {
		$response->title = '反馈列表';
        if ($request->content) {
			$params ['content'] = "content LIKE '%" . trim ( $request->content ) . "%'";
			$response->content = trim ( $request->content );
		}
		if ($request->contact) {
			$params ['contact'] = "contact LIKE '%" . trim ( $request->contact ) . "%'";
			$response->contact = trim ( $request->contact );
		}
		if ($request->start_time || $request->end_time) {
			$_default = strtotime ( date ( "Y-m-d" ) );
			$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $_default;
			$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : $_default + 24 * 3600;
			$params ['ctime'] = "ctime >= " . $start_time . " AND ctime < " . $end_time;
		}

        $type = $request->get('type', 'feed');
        $params ['type'] = "type ='$type' ";

		$total = \app\dao\FeedBackDao::getSlaveInstance()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\FeedBackDao::getSlaveInstance()->getList( $params, $limit );
		}
		$response->list = $list;
		$response->type = $type;

        $response->type_arr = array('feed'=>'反馈','love'=>'喜好');

		$response->page = $page->GetPageHtml ();
		$this->layoutSmarty ( 'index' );
	}
	// 删除品牌
	public function delete($request, $response) {
		$id = intval ( $request->id );
		$result = \app\dao\FeedBackDao::getMasterInstance ()->delete( $id );
		if ($result) {
			header ( "Location: index.php?_c=feedback&_a=index" );
		} else {
			$this->showError ( '删除留言失败' );
		}
	}
}