<?php

namespace touch\controller;

use sprite\lib\Cookie;
use sprite\mvc\controller;
use \stdClass;

// touch base controller
class BaseController extends Controller {
	protected $current_user = array (
			'user_id' => 0,
			'user_name' => '' 
	);
    protected $_log = array();
	protected $has_login = false;
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );

        if(!isset($_COOKIE['YID'])) {
            $_COOKIE['YID'] = self::gen_session_key();
            setcookie( 'YID', $_COOKIE['YID'], time () + 365 * 24 * 60 * 60 );
        }

		/* 身份处理 */
		if (isset ( $_COOKIE ['user_info_app'] )) {
			$info = unserialize ( base64_decode ( $_COOKIE ['user_info_app'] ) );
		}

		if ($info ['user_id'] && $info ['user_name']) {
			$this->has_login = true;
			$this->current_user = $info;
		}
        $this->current_user['uuid'] = $_COOKIE['YID'];//设定用户KEY
		header ( "Content-type: text/html; charset=utf-8" );
	}
	public function showError($msg, $url = '') {
		self::showMsg ( $msg, $url );
	}
	public function showMsg($msg, $url = '') {
		echo "<script type=\"text/javascript\">";
		echo "alert('" . $msg . "');";
		if (! empty ( $url )) {
			echo "window.location.href='" . $url . "';";
		} else {
			echo "history.back();";
		}
		echo "</script>";
		exit ();
	}
	public function showMessage($txt, $url) {
		$this->_response->txt = $txt;
		$this->_response->url = $url;
		$this->layoutSmarty ( 'message' );
	}

    public function befor() {
        parent::befor();
        self::addViewLog();//增加访问全日制
    }

    public function addViewLog() {
        $info = array();
        $info['user_id'] = $this->current_user['user_id'];
        $info['uuid'] = $this->current_user['uuid'];
        $info['item_id'] = isset ( $info ['id'] ) ? $info ['id'] : 0;
        $info['source'] = 2;
        $info['type'] = $this->_controller;
        $info['action'] = $this->_action;

        $info['info'] = json_encode(array_merge($_GET, $_POST));
        $info['ctime'] = time();

        return \app\dao\ViewLogDao::getMasterInstance()->add( $info );
    }

	public function userLog($info) {
		$info ['user_id'] = isset ( $info ['user_id'] ) ? $info ['user_id'] : 0;
		$info ['uuid'] = isset ( $info ['uuid'] ) ? $info ['uuid'] : '';

        $info['item_id'] = isset ( $info ['id'] ) ? $info ['id'] : 0;
		$info ['info'] = $info ['info'] ? serialize ( $info ['info'] ) : '';
		$info ['ctime'] = time ();
		return \app\dao\UserLogDao::getMasterInstance()->add( $info );
	}
	public function checkLogin($url = '') {
		if (! $this->has_login) {
			$goto = TOUCH_OAK . '/index.php?_c=login';
			if ($url)
				$goto .= '&refer=' . urlencode ( $url );
			$this->redirect ( $goto );
		}
	}
	public function get_refer() {
		$ret_url = '';
		if (isset ( $_POST ['refer'] )) {
			$ret_url = rawurldecode ( $_POST ['refer'] );
		} elseif (isset ( $_GET ['refer'] )) {
			$ret_url = rawurldecode ( $_GET ['refer'] );
		}
		
		if (! $ret_url && isset ( $_SERVER ['HTTP_REFERER'] )) {
			$search = array (
					'_c=login' 
			);
			if ($_SERVER ['HTTP_REFERER'] == str_replace ( $search, "", $_SERVER ['HTTP_REFERER'] )) {
				$ret_url = $_SERVER ['HTTP_REFERER'];
			}
		}
		if (! $ret_url) {
			$ret_url = TOUCH_OAK . '/index.php';
		}
		return htmlspecialchars_decode ( $ret_url );
	}
	public function error($code, $message, $error_detail = '') {
		if (! $error_detail)
			$error_detail = $message;
		return self::renderJson ( array (
				'status' => $code,
				'error_info' => $message,
				'error_detail' => $error_detail 
		) );
	}
	public function result($data) {
		return self::renderJson ( array (
				'status' => 200,
				'data' => $data 
		) );
	}
	// 获取返回地址
	public function getBackUrl($cookie_name, $search, $reBack) {
		if ($reBack == 1) {
			return $_COOKIE [$cookie_name];
		} else {
			$str = self::get_refer ();
			if (! stripos ( $str, $search )) {
				setcookie ( $cookie_name, $str );
			}
			return "javascript:window.history.go(-1);";
		}
	}
	// 页面跳转
	public function _go($num) {
		echo "<script type='text/javascript'>window.history.go(" . $num . ");</script>";
		exit ();
	}

    public function gen_session_key() {
        static $ip = '';
        $session_key = 'touch.ymall';
        $session_id = md5(uniqid(mt_rand(), true));
        if ($ip == '') {
            $this->_ip = \sprite\lib\Ip::getIp();
            $ip = substr($this->_ip, 0, strrpos($this->_ip, '.'));
        }
        return md5(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] . $session_key . $ip . $session_id : $session_key . $ip . $session_id);
        #return sprintf('%08x', crc32(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] . $session_key . $ip . $session_id : $session_key . $ip . $session_id));
    }
}