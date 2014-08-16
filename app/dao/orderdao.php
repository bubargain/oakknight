<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class OrderDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_order';
	}
	public function getPKey() {
		return 'order_id';
	}
	/**
	 *
	 * @param
	 *        	$buyer_id
	 * @return mixed
	 */
	public function getBuyerAll($buyer_id, $status = 0, $limit = '') {
		$sql = "select * from " . self::getTableName () . " o inner join ym_order_goods og on og.order_id=o.order_id where o.buyer_id=? ";
		if ($status)
			$sql .= " and o.order_status='$status'";
		
		$sql .= " order by order_time desc ";
		
		if ($limit)
			$sql .= " limit " . $limit;
		
		return $this->_pdo->getRows ( $sql, array (
				$buyer_id 
		) );
	}
	public function getList($params, $limit = '0,9', $sort = ' a.add_time DESC, a.order_id DESC ') {
		$sql = "SELECT a.*, b.*, c.phone_mob
				FROM ym_order AS a, ym_order_goods AS b, ym_order_extm AS c 
				WHERE a.order_id = b.order_id AND a.order_id = c.order_id" . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function getListCnt($params) {
		$sql = "SELECT count(*) 
				FROM ym_order AS a, ym_order_goods AS b, ym_order_extm AS c 
				WHERE a.order_id = b.order_id AND a.order_id = c.order_id" . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return ' AND ' . implode ( ' AND ', $params );
		} else {
			return '';
		}
	}
	public function orderList($params, $limit = '0,9', $sort = ' add_time DESC ') {
		$sql = "select * from " . self::getTableName () . " where 1 " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function orderListCnt($params) {
		$sql = "select count(*) from " . self::getTableName () . " where 1 " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	public function getInfo($order_id) {
		$sql = "SELECT a.order_id, a.seller_name, a.order_sn, a.buyer_name, a.order_amount, a.payment_name, a.order_status, a.add_time, b.goods_name, b.sku, c.phone_mob
				FROM ym_order AS a, ym_order_goods AS b, ym_order_extm AS c 
				WHERE a.order_id = b.order_id AND a.order_id = c.order_id AND a.order_id = " . $order_id;
		return $this->_pdo->getRow ( $sql );
	}
	// 根据user_id和订单状态获取相应的数量
	public function getOrderCnt($buyer_id, $order_status = 0) {
		$sql = "SELECT count(*) FROM ym_order WHERE buyer_id = ?";
		if ($order_status > 0)
			$sql .= " AND order_status = '$order_status'";
		return $this->_pdo->getOne ( $sql, array (
				$buyer_id 
		) );
	}
	// 根据参数获取订单
	public function getSaleOrderCnt($params = array()) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE 1 " . self::makeSql ( $params ) . " ORDER BY order_id ASC";
		return $this->_pdo->getRows ( $sql );
	}
	// 根据条件获取购买者
	public function getDistinctBuyer($start_time, $buyer) {
		$sql = "SELECT DISTINCT(buyer_id) FROM " . self::getTableName () . " WHERE pay_time < " . $start_time . " AND `buyer_id` IN(" . implode ( ',', array_keys ( $buyer ) ) . ")";
		return $this->_pdo->getRows ( $sql );
	}
	// 根据参数获取订单id
	public function getOrderIds($params = array()) {
		$sql = "SELECT order_id FROM " . self::getTableName () . " WHERE 1 " . self::makeSql ( $params ) . " ORDER BY order_id ASC";
		$result = $this->_pdo->getRows ( $sql );
		$list = array ();
		if (! $result)
			return $list;
		foreach ( $result as $val ) {
			$list [$val ['order_id']] = $val ['order_id'];
		}
		return $list;
	}
	
	// 根据user_id获取该用户某状态订单的数量
	public function getCntByStatus($buyer_id, $order_status) {
		$sql = "SELECT COUNT(order_id) AS num FROM ym_order WHERE buyer_id = ? AND order_status = ?";
		return $this->_pdo->getOne ( $sql, array (
				$buyer_id,
				$order_status 
		) );
	}
}