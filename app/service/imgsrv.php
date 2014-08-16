<?php

namespace app\service;

class ImgSrv extends BaseSrv {
	private $base_path = 'mobile';
	private $maxSize = 200;
	private $thumb_width = 100;
	private $thumb_height = 100;
	private $bgcolor = 'ffffff';
	
	/**
	 *
	 * @return the $maxSize
	 */
	public function getMaxSize() {
		return $this->maxSize;
	}
	
	/**
	 *
	 * @return the $bgcolor
	 */
	public function getBgcolor() {
		return $this->bgcolor;
	}
	
	/**
	 *
	 * @param string $maxSize        	
	 */
	public function setMaxSize($maxSize) {
		$this->maxSize = $maxSize;
	}
	
	/**
	 *
	 * @param string $bgcolor        	
	 */
	public function setBgcolor($bgcolor) {
		$this->bgcolor = $bgcolor;
	}
	
	/**
	 *
	 * @return the $thumb_width
	 */
	public function getThumb_width() {
		return $this->thumb_width;
	}
	
	/**
	 *
	 * @return the $thumb_height
	 */
	public function getThumb_height() {
		return $this->thumb_height;
	}
	
	/**
	 *
	 * @param string $thumb_width        	
	 */
	public function setThumb_width($thumb_width) {
		$this->thumb_width = $thumb_width;
	}
	
	/**
	 *
	 * @param string $thumb_height        	
	 */
	public function setThumb_height($thumb_height) {
		$this->thumb_height = $thumb_height;
	}
	
	//
	public function __construct() {
	}
	/**
	 * 图片上传函数
	 *
	 * @access public
	 * @param
	 *        	fdata 文件数据流
	 *        	
	 * @return 返回上传结果数组
	 */
	public function uploadFile($fdata) {
		/* 创建目录（以年月日命名） */
		$folderName = date ( 'Y/m/d' );
		$dir = CDN_YMALL_PATH . '/' . $this->base_path . '/' . $folderName . '/';
		/* 如果目标目录不存在，则创建它 */
		$this->create_folders ( $dir );
		// 命名
		$unique_name = $this->unique_name ( $dir );
		// 完整路径
		$file_name = $dir . $unique_name . $this->get_filetype ( $fdata ['name'] );
		
		try {
			move_uploaded_file ( $fdata ['tmp_name'], $file_name );
			$imgInfo = array ();
			$imgInfo ['status'] = true;
			$imgInfo ['file_type'] = substr ( $fdata ['type'], strpos ( $fdata ['type'], '/' ) + 1 );
			$imgInfo ['file_size'] = $fdata ['size'];
			$imgInfo ['file_name'] = $unique_name;
			$imgInfo ['file_path'] = str_replace ( CDN_YMALL_PATH, '', $file_name );
			$imgInfo ['add_time'] = time ();
			//
			return $imgInfo;
		} catch ( \Exception $e ) {
			throw $e;
		}
	}
	/**
	 * 生成指定目录不重名的文件名
	 *
	 * @access public
	 * @param string $dir
	 *        	要检查是否有同名文件的目录
	 * @param bool $isThumb
	 *        	是否是缩略图
	 *        	
	 * @return string 文件名
	 */
	public function unique_name($dir) {
		return time () . rand ( 100, 9999 );
	}
	
	/**
	 * 创建图片的缩略图
	 *
	 * @access public
	 * @param
	 *        	string img 上传的图片的路径
	 *        	
	 * @return mix 如果成功返回生成缩略图的信息
	 */
	public function make_thumb($img) {
		/* 创建年月日目录 */
		$folderName = date ( 'Y/m/d' );
		$dir = CDN_YMALL_PATH . '/' . $this->base_path . '/' . $folderName . '/';
		/* 如果目标目录不存在，则创建它 */
		$this->create_folders ( $dir );
		
		// 获取 GD 版本。0 表示没有 GD 库，1 表示 GD 1.x，2 表示 GD 2.x
		$gd = $this->gd_version ();
		if ($gd == 0) {
			throw new \Exception ( '没有 GD库' );
		}
		/* 检查原始文件是否存在及获得原始文件的信息 */
		$org_info = @getimagesize ( $img );
		if (! $org_info) {
			throw new \Exception ( '原图片不存在' );
		}
		if (! $this->check_img_function ( $org_info [2] )) {
			throw new \Exception ( '图片处理失败' );
		}
		$img_org = $this->img_resource ( $img, $org_info [2] );
		
		/* 原始图片的宽高比例 */
		$scale_org = $org_info [0] / $org_info [1];
		/* 处理只有缩略图宽和高有一个为0的情况，这时背景和缩略图一样大 */
		if ($this->thumb_width == 0) {
			$this->thumb_width = $this->thumb_height * $scale_org;
		}
		if ($this->thumb_height == 0) {
			$this->thumb_height = $this->thumb_width / $scale_org;
		}
		
		/*
		 * 如果缩略图大于原始图片宽度，则不改变图片大小
		 */
		
		if ($this->thumb_width > $org_info [0]) {
			$this->thumb_width = $org_info [0];
			$this->thumb_height = $org_info [1];
		}
		/* 创建缩略图的标志符 */
		if ($gd == 2) {
			$img_thumb = imagecreatetruecolor ( $this->thumb_width, $this->thumb_height );
		} else {
			$img_thumb = imagecreate ( $this->thumb_width, $this->thumb_height );
		}
		/* 背景颜色 */
		$red = '';
		$green = '';
		$blue = '';
		sscanf ( $this->bgcolor, "%2x%2x%2x", $red, $green, $blue );
		$clr = imagecolorallocate ( $img_thumb, $red, $green, $blue );
		imagefilledrectangle ( $img_thumb, 0, 0, $this->thumb_width, $this->thumb_height, $clr );
		
		if ($org_info [0] / $this->thumb_width > $org_info [1] / $this->thumb_height) {
			$lessen_width = $this->thumb_width;
			$lessen_height = $this->thumb_width / $scale_org;
		} else {
			/* 原始图片比较高，则以高度为准 */
			$lessen_width = $this->thumb_height * $scale_org;
			$lessen_height = $this->thumb_height;
		}
		
		$dst_x = ($this->thumb_width - $lessen_width) / 2;
		$dst_y = ($this->thumb_height - $lessen_height) / 2;
		
		/* 将原始图片进行缩放处理 */
		if ($gd == 2) {
			imagecopyresampled ( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info [0], $org_info [1] );
		} else {
			imagecopyresized ( $img_thumb, $img_org, $dst_x, $dst_y, 0, 0, $lessen_width, $lessen_height, $org_info [0], $org_info [1] );
		}
		
		/* 如果文件名为空，生成不重名随机文件名 */
		$filename = $this->unique_name ( $dir );
		/* 生成文件 */
		if (function_exists ( 'imagejpeg' )) {
			$filename .= '.jpg';
			imagejpeg ( $img_thumb, $dir . $filename );
		} elseif (function_exists ( 'imagegif' )) {
			$filename .= '.gif';
			imagegif ( $img_thumb, $dir . $filename );
		} elseif (function_exists ( 'imagepng' )) {
			$filename .= '.png';
			imagepng ( $img_thumb, $dir . $filename );
		} else {
			throw new \Exception ( '生成指定类型缩略图失败' );
		}
		imagedestroy ( $img_thumb );
		imagedestroy ( $img_org );
		// 确认文件是否生成
		if (is_file ( $dir . $filename )) {
			// 上传后的图片信息
			$imgInfo = array ();
			$imgInfo ['status'] = true;
			$imgInfo ['image_url'] = str_replace ( CDN_YMALL_PATH, '', $dir . $filename );
			//
			return $imgInfo;
		} else {
			throw new \Exception ( '生成缩略图失败' );
		}
	}
	
	/**
	 * 根据来源文件的文件类型创建一个图像操作的标识符
	 *
	 * @access public
	 * @param string $img_file
	 *        	图片文件的路径
	 * @param string $mime_type
	 *        	图片文件的文件类型
	 * @return resource 如果成功则返回图像操作标志符，反之则返回错误代码
	 */
	public function img_resource($img_file, $mime_type) {
		switch ($mime_type) {
			case 1 :
			case 'image/gif' :
				$res = imagecreatefromgif ( $img_file );
				break;
			
			case 2 :
			case 'image/pjpeg' :
			case 'image/jpeg' :
				$res = imagecreatefromjpeg ( $img_file );
				break;
			
			case 3 :
			case 'image/x-png' :
			case 'image/png' :
				$res = imagecreatefrompng ( $img_file );
				break;
			
			default :
				return false;
		}
		
		return $res;
	}
	/**
	 * 获得服务器上的 GD 版本
	 *
	 * @return int 可能的值为0，1，2
	 */
	public function gd_version() {
		static $version = - 1;
		
		if ($version >= 0) {
			return $version;
		}
		
		if (! extension_loaded ( 'gd' )) {
			$version = 0;
		} else {
			// 尝试使用gd_info函数
			if (PHP_VERSION >= '4.3') {
				if (function_exists ( 'gd_info' )) {
					$ver_info = gd_info ();
					preg_match ( '/\d/', $ver_info ['GD Version'], $match );
					$version = $match [0];
				} else {
					if (function_exists ( 'imagecreatetruecolor' )) {
						$version = 2;
					} elseif (function_exists ( 'imagecreate' )) {
						$version = 1;
					}
				}
			} else {
				if (preg_match ( '/phpinfo/', ini_get ( 'disable_functions' ) )) {
					/* 如果phpinfo被禁用，无法确定gd版本 */
					$version = 1;
				} else {
					// 使用phpinfo函数
					ob_start ();
					phpinfo ( 8 );
					$info = ob_get_contents ();
					ob_end_clean ();
					$info = stristr ( $info, 'gd version' );
					preg_match ( '/\d/', $info, $match );
					$version = $match [0];
				}
			}
		}
		
		return $version;
	}
	/**
	 * 检查图片处理能力
	 *
	 * @access public
	 * @param string $img_type
	 *        	图片类型
	 * @return void
	 */
	public function check_img_function($img_type) {
		switch ($img_type) {
			case 'image/gif' :
			case 1 :
				return function_exists ( 'imagecreatefromgif' );
				break;
			case 'image/pjpeg' :
			case 'image/jpeg' :
			case 2 :
				return function_exists ( 'imagecreatefromjpeg' );
				break;
			
			case 'image/x-png' :
			case 'image/png' :
			case 3 :
				return function_exists ( 'imagecreatefrompng' );
				break;
			
			default :
				return false;
		}
	}
	/**
	 * 检查图片类型
	 *
	 * @param string $img_type
	 *        	图片类型
	 * @return bool
	 */
	public function check_img_type($img_type) {
		return $img_type == 'image/pjpeg' || $img_type == 'image/x-png' || $img_type == 'image/png' || $img_type == 'image/gif' || $img_type == 'image/jpeg';
	}
	/**
	 * 检查图片大小
	 *
	 * @param string $img_size
	 *        	图片大小
	 * @return bool
	 */
	public function check_img_size($img_size) {
		if (intval ( $img_size / 1024 ) > $this->getMaxSize ()) {
			return false;
		} else {
			return true;
		}
	}
	/**
	 * 返回文件后缀名，如‘.php’
	 *
	 * @return string 文件后缀名
	 */
	public function get_filetype($path) {
		$pos = strrpos ( $path, '.' );
		if ($pos !== false) {
			return substr ( $path, $pos );
		} else {
			return '';
		}
	}
	public function create_folders($dir) {
		try {
			return is_dir ( $dir ) or ($this->create_folders ( dirname ( $dir ) ) and mkdir ( $dir, 0777 ));
		} catch ( \Exception $e ) {
			throw $e;
		}
	}
}

?>