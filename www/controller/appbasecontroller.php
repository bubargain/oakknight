<?php
namespace www\controller;

use sprite\mvc\controller;
use \stdClass;

//www base controller
class AppBaseController extends BaseController {
    protected  $current_user = null;
    protected $header = null;
    protected $has_login = false;
	public function __construct($request, $response) {
		parent::__construct($request, $response);

        $this->header = $this->get_request_header(); //请求头信息

        \sprite\lib\Debug::log('header', $this->header);
        $this->initUser(); //
	}

    /**
     * 初始化用户信息
     */
    protected function initUser() {

        $this->current_user = array('user_id'=>0, 'user_name'=>'guest', 'clientid'=>$this->header['clientid'], 'accesstoken'=>'');
        /**/
        $this->header['uid'] = 1000;
        $this->header['accesstoken'] = '9301ef6d511b93c0eaf2d9cbadd661ea';

        if(isset($this->header['uid']) && $this->header['uid']) {
            $info = \app\dao\UserInfoDao::getSlaveInstance()->find($this->header['uid']);

            if( $this->header['accesstoken'] && $info['token'] == $this->header['accesstoken'] ) {
                $this->current_user = array(
                    'user_id'=>$info['user_id'],
                    'user_name'=>$info['user_name'],
                    'clientid'=>$this->header['clientid'],
                    'accesstoken'=>$info['accesstoken']
                );
                $this->has_login = true;
            }
        }

    }

    protected function get_request_header() {
        if(function_exists('apache_request_headers')) {
            return apache_request_headers();
        }
        else {
            $arh = array();
            $rx_http = '/\AHTTP_/';
            foreach($_SERVER as $key => $val) {
                if( preg_match($rx_http, $key) ) {
                    $arh_key = preg_replace($rx_http, '', $key);
                    $rx_matches = array();
                    $rx_matches = explode('_', $arh_key);
                    if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                        foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                        $arh_key = implode('-', $rx_matches);
                    }
                    $arh_key = strtolower($arh_key);//强制转换成小写，保证协议不同
                    $arh[$arh_key] = $val;
                }
            }
            \sprite\lib\Debug::log('arh_key', $arh);
            return( $arh );
        }
    }

    public function after($_c, $_a) {

    }

    public function befor() {
        parent::befor();

        $this->addViewLog();
    }

    public function addViewLog() {
        $info = array();
        $info['user_id'] = $this->current_user['user_id'];
        $info['uuid'] = $this->current_user['uuid'] ? $this->current_user['uuid'] : '';
        $info['item_id'] = isset ( $info['id'] ) ? $info ['id'] : 0;
        $info['source'] = 1;
        $info['type'] = $this->_controller;
        $info['action'] = $this->_action;

        $info['info'] = json_encode(array_merge($_GET, $_POST));
        $info['ctime'] = time();

        return \app\dao\ViewLogDao::getMasterInstance()->add( $info );
    }

    public function userLog($info) {
        if(!isset($info['user_id'])) {
            $info['user_id'] = $this->current_user['user_id'] ? $this->current_user['user_id'] : 0;
        }
        if(!isset($info['uuid'])) {
            $info['uuid'] = isset($this->current_user['clientid']) ? $this->current_user['clientid'] : '';
        }
        parent::userLog($info);
    }

    public function error($code, $message, $error_detail = '') {
        if(!$error_detail)
            $error_detail = $message;

        //return self::renderJson(array('code'=>$code, 'message'=>$message));
        return self::renderJson(array('status'=>$code, 'error_info'=>$message, 'error_detail'=>$error_detail));
    }

    public function result($data) {
        return self::renderJson(array('status'=>200, 'data'=>$data));
    }

    protected function checkLogin() {
        if(!$this->has_login) {
            $this->error(10001,'Didnt get right uid and access_token','缺少uid和access_token或者不匹配');
            throw new \Exception('Didnt get right uid and access_token',10001);
        }
    }

    protected function formatText($content) {
        return strip_tags(stripslashes($content));
    }
}