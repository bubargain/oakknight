<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class WishGoodsDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_wish_goods';
	}
	public function getPKey() {
		return 'id';
	}
	public function getList($params, $limit = '0,9', $sort = ' ctime DESC ') {
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
    public function cntByGoods($goods_id) {
        $sql = "SELECT COUNT(*) FROM ym_wish_goods WHERE goods_id =? AND is_delete=1";
        return $this->_pdo->getOne ( $sql, array($goods_id) );
    }

    public function GroupByGoods($params) {
        $sql = 'select count(*) cnt, goods_id from '.self::getTableName().' where 1';
        if($params['goods_id'])
            $sql .= ' and goods_id='.intval($params['goods_id']);

        if($params['start'])
            $sql .= ' and ctime>='.intval($params['start']);

        if($params['end'])
            $sql .= ' and ctime<'.intval($params['end']);

        if(isset($params['is_delete']))
            $sql .= ' and is_delete='.intval($params['is_delete']);

        $sql .= ' group by goods_id';

        return $this->_pdo->getRows( $sql );
    }
}