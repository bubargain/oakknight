<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class OrderExtmDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_order_extm';
	}
	public function getPKey() {
		return 'order_id';
	}

    // 根据订单id获取商品id
    public function getInfoByOrderIds($order_ids) {
        $list = array ();
        if (is_array ( $order_ids ) && count ( $order_ids ) > 0) {
            $sql = "SELECT * FROM " . self::getTableName () . " WHERE order_id IN ( " . implode ( ',', $order_ids ) . ")";
            $tmp = $this->_pdo->getRows( $sql );
            foreach($tmp as $r) {
                $list[$r['order_id']] = $r;
            }
        }
        return $list;
    }
}