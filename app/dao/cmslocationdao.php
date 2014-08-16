<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class CmsLocationDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_cms_location';
	}
	public function getPKey() {
		return 'loc_id';
	}
	public function getList($params = array(), $order = 'loc_id ASC') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $order;
		return $this->_pdo->getRows ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
	public function getAll() {
		$sql = "SELECT * FROM " . self::getTableName () . " ORDER BY loc_id ASC";
		$ret = $this->_pdo->getRows ( $sql );
		$list = array ();
		foreach ( $ret as $val ) {
			$list [$val ['ukey']] ['ukey'] = $val ['ukey'];
			$list [$val ['ukey']] ['loc_id'] = $val ['loc_id'];
		}
		return $list;
	}
}