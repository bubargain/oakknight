<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;
use app\dao\GoodsStatisticsDao;

class GoodsDao extends YmallDao {
	const BUY_STATUS = 12; // 0b11111 : 1：待审( 11000、0 ) - 1：商家上架( 1、0 ) - 1：审核通过( 1、0 ) - 1: 禁售( 1、0 ) - 1: 删除( 1、0 )
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_goods';
	}
	public function getPKey() {
		return 'goods_id';
	}
	public function getList($params, $limit = '0,9', $sort = 'utime DESC ') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		$list = $this->_pdo->getRows ( $sql );
		foreach ( ( array ) $list as $key => $row ) {
			$list [$key] = array_merge ( $row, self::status2Arr ( $row ['status'] ) );
		}
		return $list;
	}
	public function getListCnt($params) {
		$sql = "SELECT COUNT(*) FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	public function stock($id, $num, $type) {
		if (! in_array ( $type, array (
				'+',
				'-' 
		) ))
			throw new \Exception ( '参数异常，请联系管理员', 50000 );
		
		$sql = "UPDATE " . self::getTableName () . " SET stock=stock" . $type . $num . " where goods_id=" . intval ( $id );
		
		return $this->_pdo->exec ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
	public function info($id) {
		$sql = "SELECT * FROM " . self::getTableName () . " g INNER JOIN ym_goods_statistics gs ON gs.goods_id=g.goods_id AND g.goods_id=? AND g.status&1 = 0 ";
		$info = $this->_pdo->getRow ( $sql, array (
				$id 
		) );
		if ($info) {
			$info ['more_property'] = unserialize ( $info ['more_property'] );
			$info ['more_sale'] = unserialize ( $info ['more_sale'] );
		}
		
		return $info;
	}
	public function editStatus($status, $where, $sale_time = 0) {
		if (! $where)
			throw new \Exception ( 'edit goods status must be set where', 3001 );
		
		$sql = "update " . self::getTableName () . " set `status`=$status";
        if($sale_time)
            $sql .= ",`sale_time`=$sale_time";

        $sql .= " where " . $where;

		return $this->_pdo->exec ( $sql );
	}
	public function status2Arr($status) {
		// 8 = 1000, 4 = 0100, 2 = 0010, 1 = 0001,
		$list = self::statusCfg ();
		foreach ( $list as $key => $val ) {
			$list [$key] = ($status & $val) == $val ? 1 : 0;
		}
		return $list;
	}
	public function arr2status($arr) {
		$list = self::statusCfg ();
		$status = 0;
		foreach ( $arr as $key => $val ) {
			if ($val != 1 || ! $list [$key])
				continue;
			$status |= $list [$key];
		}
		return $status;
	}
	// closed,approval_status
	private function statusCfg() {
		return array (
				'pre_sale' => 16,
				'if_show' => 8,
				'approval' => 4,
				'closed' => 2,
				'deleted' => 1 
		);
	}
	// 根据goods_ids数组获取商品信息
	public function getInfoByGoodsIds($goods_ids) {
		if (! $goods_ids || ! is_array ( $goods_ids ))
			return array ();
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE goods_id IN (" . implode ( ',', $goods_ids ) . ")";
		$result = $this->_pdo->getRows ( $sql );
		$ret = array ();
		foreach ( $result as $val ) {
			$ret[$val['goods_id']] = $val;
		}
		return $ret;
	}
	// 根据条件获取所有商品
	public function getAllGoods($params = array()) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getRows ( $sql );
	}
	// 根据goods_id获取库存列表
	public function getStockByGoodsIds($goods_ids) {
		$list = array();
		$sql = "SELECT goods_id, stock FROM " . self::getTableName () . " WHERE goods_id IN (".implode(',', $goods_ids).")";
		$result = $this->_pdo->getRows ( $sql );
		foreach ($result as $val){
			$list[$val['goods_id']]['stock'] = $val['stock'];
		}
		return $list;
	}
}