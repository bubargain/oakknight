<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class PushDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_push';
	}
	public function getPKey() {
		return 'id';
	}
	public function getList($params, $limit = '0,9', $sort = 'ctime DESC ') {
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

    public function getValidPush() {
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE `status`=0 AND push_time<" .time() ." ORDER BY id ASC limit 1";
        return $this->_pdo->getRow( $sql );
    }

    public function getTypes() {
        return array(
            'all'=>'全部用户',
            'wishes'=>'商品收藏用户',
            'notify'=>'商品到货提醒用户',
            'users'=>'指定用户',
        );
    }
}