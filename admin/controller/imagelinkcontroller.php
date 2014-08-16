<?php

namespace admin\controller;

use app\common\util\subpages;

class ImagelinkController extends BaseController {
	public function index($request, $response) {
		$response->title = '图片链接列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->image_title) {
			$params ['title'] = "title LIKE '%" . trim ( $request->image_title ) . "%'";
			$response->image_title = $request->image_title;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&image_title=' ) === false) {
				$extUrl .= "&image_title=" . trim ( $request->image_title );
			}
		}
		if ($request->loc_id) {
			$params ['loc_id'] = "b.loc_id = " . intval ( $request->loc_id );
			$response->loc_id = intval ( $request->loc_id );
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
		$total = \app\dao\CmsImagelinkDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] ) . $extUrl;
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\CmsImagelinkDao::getSlaveInstance ()->getList ( $params, $limit );
		}
		$response->list = $list;
		$response->page = $page->GetPageHtml ();
		$response->cdn_ymall = CDN_YMALL;
		$response->locationList = \app\dao\CmsLocationDao::getSlaveInstance ()->getList ();
		$this->layoutSmarty ( 'index' );
	}
	public function add($request, $response) {
		$response->title = '添加/修改记录';
		$response->cates_html = self::getCateLevelHtml ();
		if (self::isPost ()) {
			if ($request->loc_id && $request->sort) {
				$extra = array ();
				if ($request->file_id) {
					$extra = array (
							'file_id' => $request->file_id 
					);
				} elseif ($request->extra) {
					$arr = json_decode ( $request->extra, true );
					$brand_info = \app\dao\BrandDao::getSlaveInstance ()->find ( $arr ['brand_id'] );
					$extra ['brand_name'] = $brand_info ['brand_name'];
					$extra = array_merge ( $extra, $arr );
				}
				$params = array (
						'title' => $request->image_title,
						'loc_id' => $request->loc_id,
						'image_url' => $request->image_url,
						'sort' => $request->sort,
						'parent_id' => $request->parent_id,
						'status' => $request->status,
						'url' => $request->url,
						'extra' => json_encode ( $extra ) 
				);
			} else {
				$this->showError ( "信息填写不完整", "index.php?_c=imagelink&_a=index" );
			}
			if ($request->id) {
				$result = \app\dao\CmsImagelinkDao::getMasterInstance ()->edit ( $request->id, $params );
			} else {
				$result = \app\dao\CmsImagelinkDao::getMasterInstance ()->add ( $params );
			}
			if (! $result) {
				$this->showError ( '保存信息失败' );
			}
			header ( "Location: index.php?_c=imagelink&_a=index" );
		} else {
			$response->cdn_ymall = CDN_YMALL;
			$response->info = self::getInfo ( $request->id );
			$response->locationList = \app\dao\CmsLocationDao::getSlaveInstance ()->getList ( array (
					'status = 1' 
			) );
			$this->layoutSmarty ( 'add.form' );
		}
	}
	public function delete($request, $response) {
		// 先将图片文件置为is_del=1
		$info = \app\dao\CmsImagelinkDao::getSlaveInstance ()->find ( $request->id );
		$fileArr = json_decode ( $info ['extra'], true );
		$file_id = $fileArr ['file_id'];
		if ($file_id) {
			\app\dao\UploadedFileDao::getMasterInstance ()->edit ( $file_id, array (
					'is_del' => 1 
			) );
		}
		// 删除记录
		$result = \app\dao\CmsImagelinkDao::getMasterInstance ()->delete ( $request->id );
		if (! $result) {
			$this->showError ( '删除失败' );
		}
		header ( "Location: index.php?_c=imagelink&_a=index" );
	}
	public function goods($request, $response) {
		if ($request->cate_id) {
			$params ['cate_id'] = " cate_id = " . $request->cate_id;
		}
        $str = '';
		$params ['goods_name'] = " goods_name LIKE '%" . trim ( $request->goods_name ) . "%'";
		$params ['status'] = "`status` = 12 ";
		$list = \app\dao\GoodsDao::getSlaveInstance ()->getAllGoods ( $params );
		if ($list) {
			foreach ( $list as $val ) {
				$arr = array (
						'goods_name' => $val ['goods_name'],
						'brand_id' => $val ['brand_id'],
						'cate_name' => $val ['cate_name'],
						'goods_id' => $val ['goods_id'],
						'default_thumb' => $val ['default_thumb'],
						'tags' => $val ['tags'] 
				);
				$arr = json_encode ( $arr );
				$str .= "<p><a href='javascript:;' y-data='" . $arr . "'>" . $val ['goods_name'] . "</a></p>";
			}
		} else {
			$str .= "<p>暂无结果</p>";
		}
		echo $str;
	}
	private function getInfo($id) {
		$info = array ();
		if ($id) {
			$info = \app\dao\CmsImagelinkDao::getSlaveInstance ()->find ( $id );
			$extra = json_decode ( $info ['extra'], true );
			$info ['file_id'] = $extra ['file_id'];
		}
		return $info;
	}
	// 获取商品分类下拉框
	private function getCateLevelHtml($id = 0) {
		$maps = $info = array ();
		$html = "<option value=''>选择分类</option>";
		$list = \app\dao\GcategoryDao::getSlaveInstance ()->findByField ( 'if_show', 1 );
		if (! $list) {
			return $html;
		}
		foreach ( $list as $row ) {
			$maps [$row ['parent_id']] [$row ['cate_id']] = $row ['cate_id'];
			$info [$row ['cate_id']] = $row;
		}
		if (! $maps [0]) {
			return $html;
		}
		foreach ( $maps [0] as $_one ) {
			if (! isset ( $maps [$_one] )) {
				continue;
			}
			$html .= "<option value='' style='font-weight:bold;'>" . $info [$_one] ["cate_name"] . "</option>";
			foreach ( $maps [$_one] as $_two ) {
				$html .= "<option value='{$info[$_two]['cate_id']}' style='padding-left:20px;'>" . $info [$_two] ['cate_name'] . '</option>';
			}
		}
		return $html;
	}
}