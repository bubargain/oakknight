<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class LinksDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_links';
	}
	public function getPKey() {
		return 'id';
	}
	public function getList($params, $limit = '0,9', $sort = 'sort DESC ') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function getListCnt($params) {
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
	public function getFriendLinksActionCnt($params) {
		$sql = "SELECT info AS appName, COUNT(info) AS clickCnt FROM ym_user_log WHERE `type` = 'click' AND `action`= 'appcommend' AND " . self::makeSql ( $params ) . " GROUP BY info";
		return $this->_pdo->getRows ( $sql );
	}
	
}