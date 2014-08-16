<?php

namespace shop\controller;

use sprite\mvc\controller;
use \stdClass;

// admin base controller
class BaseController extends Controller {
    protected $current_user = array('user_id'=>0, 'user_name'=>'guest');
    protected $has_login = false;
    public function __construct($request, $response) {
		parent::__construct ( $request, $response );
		/* 身份处理 */

        if(isset($_COOKIE['user_info']))
            $info = unserialize(base64_decode($_COOKIE['user_info']));

		if ($this->_controller != 'login' && !$info) {
			header('Location: index.php?_c=login');
            exit();
		}

        if($info['user_id'] && $info['user_name']) {
            $this->has_login = true;
            $this->current_user = $info;

            setcookie('user_info', $_COOKIE['user_info'], time () + 15 * 60 );
        }

		header ( "Content-type: text/html; charset=utf-8" );
	}

    public function befor() {
        parent::befor();
        $t = $this->initMenu();
        $this->_response->menu_list = $t;
    }

    protected function initMenu() { }

    public function error($url, $msg) {
        echo 'error:'.$msg;
        echo $url;

    }

    public function success($url, $msg) {

        /*
        echo 'ok:' . $msg;
        echo $url;
        */
        header("Location: $url");
    }
}