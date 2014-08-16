<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class OrderGoodsDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_order_goods';
	}
	public function getPKey() {
		return 'rec_id';
	}
	public function getList($params) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY rec_id ASC";
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
	public function getOrderGoodsCnt($params = array()) {
		$sql = "SELECT COUNT(DISTINCT(b.goods_id)) FROM ym_order AS a INNER JOIN ym_order_goods AS b ON a.order_id = b.order_id WHERE " . self::makeSql ( $params ) . " ORDER BY goods_id ASC";
		return $this->_pdo->getOne ( $sql );
	}
	public function getOrderGoods($params = array(), $limit = '0,9') {
		$sql = "SELECT DISTINCT(b.goods_id), b.goods_name FROM ym_order AS a INNER JOIN ym_order_goods AS b ON a.order_id = b.order_id WHERE " . self::makeSql ( $params ) . " ORDER BY goods_id ASC LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	// 连表获取订单-订单商品列表
	public function getOrderGoodsList($params, $extStr = '') {
		$sql = "SELECT b.goods_id AS goods_id, b.goods_name AS goods_name, b.price AS price, b.quantity as quantity, a.order_id, a.buyer_id, a.add_time AS add_time, a.pay_time, a.closed_time FROM ym_order AS a LEFT JOIN ym_order_goods AS b ON a.order_id = b.order_id WHERE " . self::makeSql ( $params ) . " " . $extStr;
		$list = $this->_pdo->getRows ( $sql );
		return $list;
	}
	// 根据订单id获取商品id
	public function getGoodsByOrderIds($order_ids) {
        $list = array ();
		if (is_array ( $order_ids ) && count ( $order_ids ) > 0) {
			$sql = "SELECT * FROM " . self::getTableName () . " WHERE order_id IN ( " . implode ( ',', $order_ids ) . ")";
			$list = $this->_pdo->getRows( $sql );
		}
		return $list;
	}

    /**
     * @param $order_ids
     * @return array
     * @desc 订单商品统计
     */
    public function getGoodsCntByIds($order_ids) {
        $result = array ('quantity'=>0, 'cost_amount'=>0, 'goods_num'=>0, 'empty_cost'=>0);
        if (is_array ( $order_ids ) && count ( $order_ids ) > 0) {
            $sql = "SELECT * FROM " . self::getTableName () . " WHERE order_id IN ( " . implode ( ',', $order_ids ) . ")";
            $list = $this->_pdo->getRows ( $sql );
            foreach ( $list as $val ) {
                $result['quantity'] += $val['quantity'];
                $result['cost_amount'] += $val['cost_price'] * $val['quantity'];
                $tmp[$val['goods_id']] = $val['goods_id'];
                if($val['cost_price'] <=1)
                    $result['empty_cost']++;
            }

            $result['goods_num'] = count($tmp);
        }
        return $result;
    }
}