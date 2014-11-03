<?php

namespace touch\controller;

class logincontroller extends BaseController {
	public function index($request, $response) {
		self::setLoginBackUrl('login_backUrl', array('_c=index','_c=order&_a=confirm'));
		if ($this->has_login) {
			// 自动登录
			$this->redirect ( $this->get_refer () );
		} else if ($this->isPost ()) {
			try {
				$info = \app\dao\UserDao::getSlaveInstance ()->find ( array (
						'user_name' => $request->phone 
				) );
				// 对于新用户或者忘记密码的情况，走验证码流程
				if (! $info || $request->style == 'forgetPass') {
					// 登陆处理
					$user_name = $request->phone;
					$type = 'login';
					try {
						$verifySrv = new \app\service\VerifySrv ();
						$verifySrv->make ( $user_name, $type );
					} catch ( \Exception $e ) {
						$this->showError ( "验证码发送失败，请稍候重试" );
					}
					$url = "index.php?_c=login&_a=verify&phone=" . $request->phone . "&type=" . $type;
					$this->redirect ( $url );
				} else {
					$response->phone = $request->phone;
					$this->layoutSmarty ( 'passw' );
				}
			} catch ( \Exception $e ) {
				$this->showError ( "无效登录" );
			}
		} else {
			$response->TOUCH_OAK = TOUCH_OAK;
			$response->id = $request->id;
			$response->style = $request->style;
			$response->phone = $request->phone;
			$response->title = '登录';
			$this->layoutSmarty ( 'index' );
		}
	}
	public function verify($request, $response) {
		$response->phone = $request->phone;
		if ($this->isPost ()) {
			// 判断验证码是否正确
			$ret = self::msCheckLogin ( $request->phone, $request->type, $request->code );
			if (! $ret) {
				$this->showError ( '请输入正确的验证码' );
			} else { // 验证通过
				$response->title = '设置密码';
				$this->layoutSmarty ( 'setpass' );
			}
		} else {
			$response->title = '填写验证码';
			$response->type = $request->type;
			$this->layoutSmarty ( 'verify' );
		}
	}
	/**
	 * 密码登录
	 *
	 * @param http $request        	
	 * @param http $response        	
	 */
	public function passw($request, $response) {
		if ($this->isPost ()) {
			$phone = $request->phone;
			$pass = $request->pass;
			$info = \app\dao\UserDao::getSlaveInstance ()->find ( array (
					'user_name' => $phone,
					'password' => md5 ( $pass ) 
			) );
			if ($info) {
				// 写cookie
				$user_info = array (
						'user_id' => $info ['user_id'],
						'user_name' => $info ['user_name'] 
				);
				$cookie_user_info = base64_encode ( serialize ( $user_info ) );
				// setcookie ( 'user_info', $cookie_user_info, time () + 7 * 24
				// * 60 * 60, '/', 'ymall.com' );
				setcookie ( 'user_info_app', $cookie_user_info, time () + 7 * 24 * 60 * 60 );
				$this->redirect(self::getLoginBackUrl('login_backUrl'));
			} else {
				$this->showError ( "密码不正确" );
			}
		}
		$this->layoutSmarty ( "passw" );
	}
	/**
	 * 设置密码
	 *
	 * @param http $request        	
	 * @param http $response        	
	 */
	public function setPass($request, $response) {
		try {
			$phone = $request->post ( 'phone' );
			$info ['user_name'] = $phone;
			$ret = \app\dao\UserDao::getSlaveInstance ()->find ( array (
					'user_name' => $phone 
			) );
			$userOper = new \app\service\UserSrv ();
			if ($ret) {
				// 修改
				$info ['user_id'] = $ret ['user_id'];
				$info ['pass'] = $request->post ( 'pwd' );
				$rs = $userOper->modifyUserInfoForTouch ( $info );
			} else {
				// 新增
				$info ['password'] = md5 ( $request->post ( 'pwd' ) );
				$info ['source'] = 2;
				$rs = $userOper->addUser ( $info );
			}
			$user_info = array (
					'user_id' => $rs ['user_id'],
					'user_name' => $rs ['user_name'] 
			);
			$cookie_user_info = base64_encode ( serialize ( $user_info ) );
			// setcookie ( 'user_info', $cookie_user_info, time () + 7 * 24
			// * 60 * 60, '/', 'ymall.com' );
			setcookie ( 'user_info_app', $cookie_user_info, time () + 7 * 24 * 60 * 60 );
			$this->renderJson ( array (
					'ret' => array (
							'status' => 200,
							'data' => '登录成功',
							'back_url' =>self::getLoginBackUrl('login_backUrl')
					) 
			) );
		} catch ( \Exception $e ) {
			$this->renderJson ( array (
					'ret' => array (
							'status' => 10000,
							'data' => $e->getMessage () 
					) 
			) );
		}
	}
	public function logout($request, $response) {
		setcookie ( 'user_info_app', '', time () - 3600 );
		// 删除地址跳转的cookie
		setcookie ( 'goods_detail', '', time () - 3600 );
		setcookie ( 'order_detail', '', time () - 3600 );
		setcookie ( 'login_backUrl', '', time () - 3600 );
		//
		header ( 'Location: index.php?_c=index' );
	}
	private function msCheckLogin($phone, $type, $code) {
		try {
			$verifySrv = new \app\service\VerifySrv ();
			$verifySrv->check ( $phone, $type, $code );
		} catch ( \Exception $e ) {
			return false;
		}
		return true;
	}
	// 将首页和订单确认页的URL保存，以备登录成功后回跳用
	private function setLoginBackUrl($cookie_name, $str) {
		$url = $this->get_refer ();
		// 如果来源地址与地址数组中的值匹配，则将该地址保存到cookie中以备登录成功后回跳用
		foreach ( $str as $val ) {
			if (stripos ( $url,$val )) {
				setcookie ( $cookie_name, $url );
			}
		}
	}
	//获取登录成功后的回调地址
	private function getLoginBackUrl($cookie_name) {
		return $_COOKIE[$cookie_name]?$_COOKIE[$cookie_name]:'index.php?_c=index';
	}
}