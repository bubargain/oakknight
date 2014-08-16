<?php

namespace admin\controller;

use \app\service\member;

class LoginController extends BaseController {
	public function index($request, $response) {
		$refer = $this->referer ( $request );
		if ($this->has_login) {
			header ( "Location: $refer" );
		}
		if (! $this->isPost ()) {
			// 显示登陆页
			$this->renderSmarty ();
		} else {
			// 登陆处理
			try {
				$info = \app\dao\UserDao::getSlaveInstance ()->find ( array (
						'user_name' => $request->user_name 
				) );
				if (! $info || md5 ( $request->pwd ) != $info ['password']) {
					//throw new \Exception ( '账户或密码错误', 4001 );
					$this->showError ( '账户或密码错误' );
				}
				
				if (!self::isAdmin ( $info ['user_id'] )) {
					//throw new \Exception ( '账户不是管理员', 4002 );
					$this->showError ( '账户不是管理员' );
				}
				
				// 写cookie
				$user_info = array (
						'user_id' => $info ['user_id'],
						'user_name' => $info ['user_name'] 
				);
				
				$cookie_user_info = base64_encode ( serialize ( $user_info ) );
				setcookie ( 'admin_info', $cookie_user_info, time () + 15 * 60 );
				header ( "Location: $refer" );
				exit ();
			} catch ( \Exception $e ) {
				$response->warn = $e->getMessage ();
				$this->renderSmarty ();
			}
		}
	}
	public function logout() {
		setcookie ( 'admin_info', '', time () - 3600 );
		header ( 'Location: index.php?_c=login' );
	}
	private function isAdmin($user_id) {
		return \app\dao\AdminDao::getSlaveInstance ()->find ( $user_id ) ? true : false;
	}
	private function referer($request) {
		$refer = 'index.php';
		if (isset ( $request->refer )) {
			$refer = $request->refer;
		}
		
		$search = array (
				'_c=login' 
		);
		$refer = str_replace ( $search, '', $refer );
		
		return $refer;
	}
}