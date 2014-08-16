<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class VerifyCodeDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_verify_code';
	}
	public function getPKey() {
		return 'id';
	}
	public function getValid($phone, $type) {
		$time = time () - 24 * 60 * 60;
		$sql = "select * from " . self::getTableName () . " where `phone`=? and `type`=? and ctime>? order by id desc limit 1";
		
		return $this->_pdo->getRow ( $sql, array (
				$phone,
				$type,
				$time 
		) );
	}
	public function getValidByParams($params) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY id DESC";
		return $this->_pdo->getRow ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
    	}
    }
}