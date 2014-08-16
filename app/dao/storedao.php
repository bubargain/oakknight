<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class StoreDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_store';
	}
	public function getPKey() {
		return 'store_id';
	}
	public function getList($order = 'sort_order ASC, store_id DESC') {
		$sql = "SELECT * FROM " . $this->getTableName () . " ORDER BY " . $order;
		return $this->_pdo->getRows ( $sql );
	}
	// 保存记录
	public function save($store_id, $params, $isEdit = false) {
		// 是否是修改而非新增
		if ($isEdit) {
			return $this->edit ( $store_id, $params );
		} else {
			try {
				$this->add ( $params );
			} catch ( \Exception $e ) {
				return false;
			}
			return true;
		}
	}
}