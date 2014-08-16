<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class PushTokenDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_push_token';
	}
	public function getPKey() {
		return 'uuid';
	}
	public function getListCnt() {
		$sql = "SELECT count(*) FROM " . $this->getTableName ();
		return $this->_pdo->getOne( $sql );
	}

    public function getList($limit = '0,100') {
        $sql = "SELECT distinct(push_token) FROM " . $this->getTableName () ." limit ".$limit;
        return $this->_pdo->getRows( $sql );
    }
}