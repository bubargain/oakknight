<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class NotifyGoodsDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_notify_goods';
	}
	public function getPKey() {
		return 'id';
	}

    public function getListCnt($params) {
        $sql = "select count(*) num from ".self::getTableName()." where " . self::makeSql($params);
        return $this->_pdo->getOne($sql);
    }

    public function getList($params, $limit = '0, 20') {
        $sql = "select * from ".self::getTableName()." where " . self::makeSql($params) . ' limit '. $limit;
        return $this->_pdo->getRows($sql);
    }

    public function getGroupByGoods($params) {
        $sql = "select goods_id, count(*) num from ".self::getTableName()." where " . self::makeSql($params) . ' group by goods_id';
        return $this->_pdo->getRows($sql);
    }

    private function makeSql($params) {
        if (is_array ( $params ) && count ( $params ) > 0) {
            return implode ( ' AND ', $params );
        } else {
            return '1';
        }
    }
 
}