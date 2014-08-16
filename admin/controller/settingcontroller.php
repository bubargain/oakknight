<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use \app\dao\SettingDao;
use app\service\ImgSrv;

class SettingController extends BaseController {
	public function agreement($request, $response) {
		$response->title = '注册须知';
		if ($request->type == 'saveAgreement') {
			if ($request->uvalue) {
				$params = array (
						'ukey' => 'agreement',
						'uvalue' => trim ( $request->uvalue ),
						'utime' => time () 
				);
			} else {
				$this->showError ( '提交信息不完整或有误', 'index.php?_c=setting&_a=agreement' );
			}
			// 保存
			$result = \app\dao\SettingDao::getMasterInstance ()->save ( $params, $request->ukey );
			if (! $result) {
				$this->showError ( '保存信息失败', 'index.php?_c=setting&_a=agreement' );
			}
			header ( "Location: index.php?_c=setting&_a=agreement" );
		} else {
			$result = \app\dao\SettingDao::getSlaveInstance ()->find ( 'agreement' );
			if ($result) {
				$response->info = $result;
				$response->ukey = 'agreement';
			}
			$this->layoutSmarty ();
		}
	}
	
	/**
	 * app version setting
	 *
	 * @param
	 *        	$request
	 * @param
	 *        	$response
	 */
	public function version($request, $response) {
		$response->title = '版本管理';
		$version_key = 'app_version';
		if ($this->isPost ()) {
			$version = array (
					"no" => $request->post ( 'version_no' ),
					"must" => $request->post ( 'ck_must' ),
					"show" => $request->post ( 'ck_show' ),
					"desc" => addslashes( nl2br ( $request->post ( 'version_desc', '' ) ) ),
					"companyWeb" => $request->post ( 'companyWeb' , ''),
					"qqGroup" => $request->post ( 'qqGroup' , ''),
					"serviceTel" => $request->post ( 'serviceTel' , ''),
					"companyName" => $request->post ( 'companyName' , ''),
					"copyright" => $request->post ( 'copyright' , ''),
					"shareTitle" => $request->post ( 'shareTitle', '' ),
					"shareBody" => $request->post ( 'shareBody', '' ),
			);
			
			$data = array (
					'ukey' => $version_key,
					'uvalue' => serialize ( $version ),
					'ctime' => time () 
			);
			SettingDao::getMasterInstance ()->replace ( $data );
			// $this->showMessage('保存成功', 'index.php?_c=setting&_a=version');
		} else {
			$info = SettingDao::getSlaveInstance ()->find ( 'app_version' );
			if ($info)
				$version = unserialize ( $info ['uvalue'] );
		}
		$response->version = $version;
		$this->layoutSmarty ();
	}
	public function bottompic($request, $response) {
		$response->title = '页尾图片';
		
		if ($request->type == 'saveImg') {
			if ($_FILES ['newPic']) {
				
				$imgUpload = new \app\service\ImgSrv ();
				$imgInfo = $imgUpload->uploadFile ( $_FILES ['newPic'] );
				// var_dump($imgInfo);die();
				$location = $imgInfo ['file_path'];
				$params = array (
						'ukey' => 'img_buttom',
						'uvalue' => trim ( $location ),
						'utime' => time () 
				);
			} else {
				$this->showError ( '提交信息不完整或有误', 'index.php?_c=setting&_a=bottompic' );
			}
			// 保存
			$result = \app\dao\SettingDao::getMasterInstance ()->save ( $params, $request->ukey );
			if (! $result) {
				$this->showError ( '保存信息失败', 'index.php?_c=setting&_a=bottompic' );
			}
			header ( "Location: index.php?_c=setting&_a=bottompic" );
		} else {
			$result = \app\dao\SettingDao::getSlaveInstance ()->find ( 'img_buttom' );
			if ($result) {
				$response->img = CDN_YMALL . $result ['uvalue'];
				$response->ukey = 'img_buttom';
			}
			$this->layoutSmarty ( 'bottompic' );
		}
	}
	// 开机大图设置
	public function startpic($request, $response) {
		$response->title = '开机大图设置';
		//
		$ukey = 'startPic';
		if (self::isPost ()) {
			$data ['flag'] = 'open';
			$data ['list'] = array ();
			$img_arr = $request->post ( 'images' );
			$image_ids_arr = $request->post ( 'image_ids' );
			$title_arr = $request->post ( 'titles' );
			$desc_arr = $request->post ( 'descs' );
			$phone_type_arr = $request->post ( 'phone_type_arr' );
			foreach ( $img_arr as $k => $v ) {
				if (! $v)
					continue;
				$data ['list'] [$k] = array (
						'url' => $v,
						'image_id' => $image_ids_arr [$k],
						'title' => $title_arr [$k],
						'desc' => $desc_arr [$k],
						'phone_type' => $phone_type_arr [$k]
				);
			}
			\app\dao\SettingDao::getMasterInstance ()->replace ( array (
					'ukey' => $ukey,
					'uvalue' => serialize ( $data ),
					'ctime' => time () 
			) );
			header ( "Location: index.php?_c=setting&_a=startpic" );
		} else {
			$ret = \app\dao\SettingDao::getSlaveInstance ()->find ( $ukey );
			if ($ret) {
				$params = unserialize ( $ret ['uvalue'] );
			}
			//
			$response->CDN_YMALL = CDN_YMALL;
			$response->flag = $params ['flag'];
			$response->list = $params ['list'];
			$this->layoutSmarty ( 'startpic' );
		}
	}
	// 开机大图的开或关
	public function changeStatus($request, $response) {
		$ukey = 'startPic';
		// 获取记录
		$ret = \app\dao\SettingDao::getMasterInstance ()->find ( $ukey );
		$params = unserialize ( $ret ['uvalue'] );
		$params ['flag'] = $request->status;
		
		\app\dao\SettingDao::getMasterInstance ()->replace ( array (
				'ukey' => $ukey,
				'uvalue' => serialize ( $params ),
				'ctime' => time () 
		) );
		header ( "Location: index.php?_c=setting&_a=startpic" );
	}
}