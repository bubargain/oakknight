<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class LoveDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_wish_goods';
	}
	public function getPKey() {
		return 'id';
	}
	public function getMyListByGoodsIds($goods_ids, $user_id) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE user_id=? AND is_delete=0 AND goods_id in(" . implode ( ',', $goods_ids ) . ")";
		return $this->_pdo->getRows ( $sql, array (
				$user_id 
		) );
	}
	public function getList($params, $limit, $sort = 'w.ctime desc') {
		$sql = "SELECT w.*, g.* FROM ym_wish_goods w INNER JOIN ym_goods g ON g.goods_id=w.goods_id WHERE w.is_delete=0 AND g.status=12";
		if ($params ['user_id'])
			$sql .= ' and w.user_id=?';
		
		$sql .= ' order by ' . $sort . ' limit ' . $limit;
		return $this->_pdo->getRows ( $sql, array (
				$params ['user_id'] 
		) );
	}
	public function getCnt($params) {
		$sql = "SELECT count(*) FROM ym_wish_goods w INNER JOIN ym_goods g ON g.goods_id=w.goods_id WHERE w.is_delete=0 AND g.status=12";
		if ($params ['user_id'])
			$sql .= ' and w.user_id=?';
		
		return $this->_pdo->getOne ( $sql, array (
				$params ['user_id'] 
		) );
	}
	public function getInfo($params) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getRow ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
}