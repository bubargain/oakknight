<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class CmsImagelinkDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_cms_imagelink';
	}
	public function getPKey() {
		return 'id';
	}
	public function getList($params = array(), $limit = '0,9', $sort = ' sort ASC, id DESC ') {
		$sql = "SELECT a.ukey, b.* FROM ym_cms_location AS a RIGHT JOIN ym_cms_imagelink AS b ON a.loc_id = b.loc_id WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function getListCnt($params) {
		$sql = "SELECT COUNT(b.id) FROM ym_cms_location AS a RIGHT JOIN ym_cms_imagelink AS b ON a.loc_id = b.loc_id WHERE " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
	public function getAllBySort($params = array(), $limit = '', $sort = 'sort ASC, id DESC') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort;
		if ($limit) {
			$sql .= " LIMIT " . $limit;
		}
		return $this->_pdo->getRows ( $sql );
	}
}