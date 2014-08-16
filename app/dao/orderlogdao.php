<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class OrderlogDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_order_log';
	}
	public function getPKey() {
		return 'log_id';
	}
	public function getList($params) {
		$sql = "SELECT * FROM " . $this->getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY log_id ASC";
		return $this->_pdo->getRows ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			if (count ( $params ) == 1) {
				return implode ( '', $params );
			} else {
				return implode ( ' AND ', $params );
			}
		} else {
			return '1';
		}
	}
}