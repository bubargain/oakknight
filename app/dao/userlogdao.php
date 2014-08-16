<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UserLogDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_user_log';
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
	public function getProxyCount($params) {
		$sql = "SELECT COUNT(*) AS num, `type`, `action` FROM " . self::getTableName () . " WHERE `type` in('proxy','tuiguang') ";
		if ($params ['start_time']) {
			$sql .= ' AND ctime>=' . $params ['start_time'];
		}
		if ($params ['end_time']) {
			$sql .= ' AND ctime<' . $params ['end_time'];
		}
		$sql .= "  GROUP BY `type`, `action`";
		return $this->_pdo->getRows ( $sql );
	}
}