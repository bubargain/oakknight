<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\service\ImgSrv;
use app\common\util\subpages;

class BrandController extends BaseController {
	// 品牌列表
	public function index($request, $response) {
		$response->title = '品牌列表';
		$total = \app\dao\BrandDao::getSlaveInstance ()->getListCnt (null);
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		// 分页对象
		$page = new SubPages ( $url, 10, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\BrandDao::getSlaveInstance ()->getList (array(), $limit );
		}
		$response->list = $list;
		$response->cdn_ymall = CDN_YMALL;
		$response->page = $page->GetPageHtml ();
		$this->layoutSmarty ( 'index' );
	}
	// 添加品牌
	public function add($request, $response) {
		$response->title = '添加品牌';
		// 保存新增
		if ($request->type == 'saveBrand') {
			$brand_id = intval ( $request->brand_id );
			$brand_logo_isDel = intval ( $request->brand_logo_isDel );
			$brand_logo = trim ( $request->brand_logo );
			if ($request->brand_name) {
				if ($_FILES ['brand_logo'] ['name']) {
					if (! $brand_logo_isDel) {
						$this->showError ( '请先删除原有logo' );
					}
					$brand_logo = self::uploadBrandLogo ();
				}
				// 获取表单变量
				$params = array (
						'brand_name' => trim ( $request->brand_name ),
						'brand_cname' => trim ( $request->brand_cname ),
						'brand_ename' => trim ( $request->brand_ename ),
						'brand_logo' => $brand_logo,
						'sort_order' => intval ( $request->sort_order ),
						'if_show' => intval ( $request->if_show ) 
				);
				// 保存
				$result = \app\dao\BrandDao::getMasterInstance ()->save ( $brand_id, $params, $request->isEdit );
				if (! $result) {
					$this->showError ( '保存信息失败' );
				}
				header ( "Location: index.php?_c=brand&_a=index" );
			} else {
				$this->showError ( '提交信息不完整或有误' );
			}
		} else {
			$this->layoutSmarty ( 'add.form' );
		}
	}
	// 修改品牌信息
	public function edit($request, $response) {
		$response->title = '修改品牌信息';
		$brand_id = intval ( $request->brand_id );
		// 获取记录
		$info = \app\dao\BrandDao::getSlaveInstance ()->find ( $brand_id );
		if (empty ( $info ['brand_logo'] )) {
			$brand_logo_isDel = 1;
		}
		$response->info = $info;
		$response->brand_logo_isDel = $brand_logo_isDel;
		$response->cdn_ymall = CDN_YMALL;
		$response->isEdit = true;
		$this->layoutSmarty ( 'add.form' );
	}
	// 删除品牌
	public function delete($request, $response) {
		$brand_id = intval ( $request->brand_id );
		// 获取品牌图片的地址
		$info = \app\dao\BrandDao::getSlaveInstance ()->find ( $brand_id );
		$brand_logo = CDN_YMALL_PATH . $info ['brand_logo'];
		$result = \app\dao\BrandDao::getMasterInstance ()->delete ( $brand_id );
		if ($result) {
			// 删除图片
			if (file_exists ( $brand_logo )) {
				unlink ( $brand_logo );
			}
			header ( "Location: index.php?_c=brand&_a=index" );
		} else {
			$this->showError ( '删除品牌失败' );
		}
	}
	// 仅删除品牌图片
	public function deleteBrandLogo($request, $response) {
		$brand_logo = CDN_YMALL_PATH . $request->brand_logo;
		$brand_id = intval ( $request->brand_id );
		// 修改图片字段值为空
		$result = \app\dao\BrandDao::getMasterInstance ()->edit ( $brand_id, array (
				'brand_logo' => '' 
		) );
		if ($result) {
			// 删除图片
			if (file_exists ( $brand_logo )) {
				if (! unlink ( $brand_logo )) {
					$this->showError ( '删除品牌图片失败' );
				}
			}
			header ( "Location: index.php?_c=brand&_a=edit&brand_id=" . $brand_id );
		} else {
			$this->showError ( '修改品牌图片信息失败' );
		}
	}
	// 上传 图片
	public function uploadBrandLogo() {
		if ($_FILES ['brand_logo'] ['name']) {
			$img = new ImgSrv ();
			if (! $img->check_img_type ( $_FILES ['brand_logo'] ['type'] )) {
				$this->showError ( '图片格式有误' );
			}
			if (! $img->check_img_size ( $_FILES ['brand_logo'] ['size'] )) {
				$this->showError ( '图片不能超过' . $img->getMaxSize () . 'K' );
			}
			try {
				// 上传文件
				$uploadResult = $img->uploadFile ( $_FILES ['brand_logo'] );
			} catch ( \Exception $e ) {
				$this->showError ( $e->getMessage () );
			}
			$brand_logo = $uploadResult ['file_path'];
		} else {
			$this->showError ( '提交信息不完整或有误' );
		}
		
		return $brand_logo;
	}
}