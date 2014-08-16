<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\service\baseSrv;

// admin base controller
class BaseController extends Controller {
	protected $current_user = array (
			'user_id' => 0,
			'user_name' => 'guest' 
	);
	protected $has_login = false;
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
		/* 身份处理 */
		
		if (isset ( $_COOKIE['admin_info'] )) {
			$info = unserialize ( base64_decode ( $_COOKIE['admin_info'] ) );
		}
		
		if (!in_array($this->_controller, array('login', 'auto')) && ! $info) {
			header ( 'Location: index.php?_c=login' );
			exit ();
		}
		
		if ($info ['user_id'] && $info ['user_name']) {
			$this->has_login = true;
			$this->current_user = $info;
			$response->current_user_name = $info ['user_name'];
            setcookie ( 'admin_info', $_COOKIE['admin_info'], time () + 15 * 60 );
		}
		
		header ( "Content-type: text/html; charset=utf-8" );
	}
	
	// 错误信息的js弹窗
	public function showError($msg, $url = '') {
		echo "<script language=\"javascript\">";
		echo "alert('" . $msg . "');";
		if (! empty ( $url )) {
			echo "window.location.href='" . $url . "';";
		} else {
			echo "history.back();";
		}
		echo "</script>";
		exit ();
	}
	// 多维数组转换以为成一维数组
	public function array_multi2single($array) {
		static $result_array = array ();
		foreach ( $array as $value ) {
			if (is_array ( $value )) {
				$this->array_multi2single ( $value );
			} else {
				$result_array [] = $value;
			}
		}
		return $result_array;
	}
	public function showMessage($txt, $url) {
		$this->_response->txt = $txt;
		$this->_response->url = $url;
        $this->layoutSmarty('message');
    }

    protected function makeExcel($string, $title) {
        $result_str = '<head><meta http-equiv="Content-Type" content="text/html;charset=gb2312"></head>' . $string;
        //header ( "Content-Type:text/plain;charset=utf-8" );
        header ( 'Content-Transfer-Encoding: gbk' );
        header ( 'Content-Type: application/vnd.ms-excel;' );
        header ( "Content-type: application/x-msexcel" );
        header ( iconv ( 'UTF-8', 'GBK//IGNORE', 'Content-Disposition: attachment; filename="' . $title . '.xls"' ) );
        echo iconv('UTF-8', 'GBK//IGNORE', $result_str);
        //echo $result_str;
    }
}