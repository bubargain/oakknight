<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class ShortDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_short_url';
	}
	public function getPKey() {
		return 'sid';
	}

    public function getListCnt($params) {
        $sql = "select count(*) from ".self::getTableName()." where 1". self::makeSql($params);

        return $this->_pdo->getOne($sql);
    }

    public function getList($params, $limit = '0, 20') {
        $sql = "select * from ".self::getTableName()." where 1" . self::makeSql($params);

        $sql .= " order by sid desc limit " . $limit;
        $list = $this->_pdo->getRows($sql);
        return $list;
    }

    private function makeSql($params) {
        if (is_array ( $params ) && count ( $params ) > 0) {
            return ' AND ' . implode ( ' AND ', $params );
        } else {
            return '';
        }
    }
}