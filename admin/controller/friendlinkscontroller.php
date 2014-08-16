<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\service\ImgSrv;
use app\common\util\subpages;

class FriendlinksController extends BaseController {
	// 列表
	public function index($request, $response) {
		$response->title = '友情链接列表';
		$total = \app\dao\LinksDao::getSlaveInstance ()->getListCnt ();
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\LinksDao::getSlaveInstance ()->getList ( array (), $limit );
		}
		$response->list = $list;
		$response->cdn_ymall = CDN_YMALL;
		$response->page = $page->GetPageHtml ();
		$this->layoutSmarty ( 'index' );
	}
	// 添加
	public function add($request, $response) {
		$response->title = '添加友情链接';
		if (self::isPost ()) {
			$params = array (
					'title' => $request->title,
					'img' => $request->img,
					'desc' => $request->desc,
					'url' => $request->url,
					'sort' => $request->sort,
					'extra' => serialize ( array (
							'file_id' => $request->file_id 
					) ) 
			);
			if ($request->id) {
				$result = \app\dao\LinksDao::getMasterInstance ()->edit ( $request->id, $params );
			} else {
				$result = \app\dao\LinksDao::getMasterInstance ()->add ( $params );
			}
			if ($result) {
				header ( "Location: index.php?_c=friendlinks&_a=index" );
			} else {
				$this->showError ( '保存友情链接失败' );
			}
		} else {
			$this->layoutSmarty ( 'add.form' );
		}
	}
	public function edit($request, $response) {
		$response->title = '修改友情链接';
		$info = \app\dao\LinksDao::getSlaveInstance ()->find ( $request->id );
		$extra = unserialize ( $info ['extra'] );
		$info ['file_id'] = $extra ['file_id'];
		$response->info = $info;
		$response->cdn_ymall = CDN_YMALL;
		$this->layoutSmarty ( 'add.form' );
	}
	// 删除
	public function delete($request, $response) {
		if ($request->id) {
			// 先将图片文件置为is_del=1
			$info = \app\dao\LinksDao::getSlaveInstance ()->find ( $request->id );
			$extra = unserialize ( $info ['extra'] );
			$file_id = $extra ['file_id'];
			if ($file_id) {
				\app\dao\UploadedFileDao::getMasterInstance ()->edit ( $file_id, array (
						'is_del' => 1 
				) );
			}
			// 删除记录
			$result = \app\dao\LinksDao::getMasterInstance ()->delete ( $request->id );
			if ($result) {
				header ( "Location: index.php?_c=friendlinks&_a=index" );
			} else {
				$this->showError ( '删除失败' );
			}
		} else {
			$this->showError ( '获取友情链接id失败' );
		}
	}
	// 统计
	public function count($request, $response) {
		$response->title = '友情链接统计';
		// 设置默认时间
		$default_time = strtotime ( date ( 'Ymd' ) );
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $start_time );
		$response->end_time = date ( 'Y-m-d', $end_time - 1 );
		//
		$response->list = \app\dao\LinksDao::getSlaveInstance ()->getFriendLinksActionCnt ( array (
				"ctime >= " . $start_time . " AND ctime < " . $end_time 
		) );
		$this->layoutSmarty ( 'count' );
	}
}