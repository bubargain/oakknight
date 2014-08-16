<?php

namespace app\dao;

use app\dao\YmallDao;

class ViewLogDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_view_log';
	}
	public function getPKey() {
		return 'id';
	}
	public function getActionList($params) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getRows ( $sql );
	}
	public function getActionCnt($params) {
		$sql = "SELECT COUNT(*) FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
}