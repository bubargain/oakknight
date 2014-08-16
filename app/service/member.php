<?php

namespace app\service;

use \app\dao\MemberDao;

class member {
	protected $db = null;
	public function __construct($request, $response) {
		$this->db = MemberDao::getSlaveInstance ();
	}
	// 登陆
	public function checkLogin($user_name, $user_password) {
		$info = $this->db->find ( array (
				'user_name' => $user_name 
		) );
		if (! $info) {
			$result = "用户不存在！";
			return $result;
		}
		if ($info ['password'] != md5 ( $user_password )) {
			$result = "密码错误！";
			return $result;
		}
		// 保存至cookie
		setcookie ( 'user_id', $info ['user_id'] );
		setcookie ( 'user_name', $user_name );
		$result = intval ( $info ['user_id'] );
		return $result;
	}
}

?>