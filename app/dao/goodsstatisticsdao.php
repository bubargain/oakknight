<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class GoodsStatisticsDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_goods_statistics';
	}
	public function getPKey() {
		return 'goods_id';
	}
	
	/**
	 * 降低统计数值
	 * 
	 * @param
	 *        	$id
	 * @param
	 *        	$filed
	 * @param
	 *        	$num
	 * @return mixed
	 */
	function decrement($id, $filed, $num) {
		$sql = "UPDATE " . self::getTableName () . " SET `$filed`=`$filed` - $num WHERE goods_id=$id";
		return $this->_pdo->exec ( $sql );
	}
	
	/**
	 * 增加统计数值
	 * 
	 * @param
	 *        	$id
	 * @param
	 *        	$filed
	 * @param
	 *        	$num
	 * @return mixed
	 */
	function increment($id, $filed, $num) {
		$sql = "UPDATE " . self::getTableName () . " SET `$filed`=`$filed` + $num WHERE goods_id=$id";
		return $this->_pdo->exec ( $sql );
	}
	// 根据goods_id获取喜欢数列表
	public function getStatisticsByGoodsIds($goods_ids) {
		$list = array ();
		if (! is_array ( $goods_ids ) || count ( $goods_ids ) < 1) {
			return $list;
		}
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE goods_id IN (" . implode ( ',', $goods_ids ) . ")";
		$result = $this->_pdo->getRows ( $sql );
		foreach ( $result as $val ) {
			$list [$val ['goods_id']] ['wishes'] = $val ['wishes'];
		}
		return $list;
    }


    // 根据goods_id获取库存列表
    public function getInfoByGoodsIds($goods_ids) {
        $list = array();
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE goods_id IN (".implode(',', $goods_ids).")";
        $result = $this->_pdo->getRows ( $sql );
        foreach ($result as $val){
            $list[$val['goods_id']] = $val;
        }
        return $list;
    }
}