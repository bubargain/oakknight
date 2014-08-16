<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class BrandDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_brand';
	}
	public function getPKey() {
		return 'brand_id';
	}
	public function getList($params=NULL, $limit = '0,9', $sort = 'brand_id DESC ') {
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
	// 保存记录
	public function save($brand_id, $params, $isEdit = false) {
		// 是否是修改而非新增
		if ($isEdit) {
			return $this->edit ( $brand_id, $params );
		} else {
			return $this->add ( $params );
		}
	}

    // 根据goods_ids数组获取商品信息
    public function getInfoByIds($brand_ids) {
        if (! $brand_ids || ! is_array ( $brand_ids ))
            return array ();
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE brand_id IN (" . implode ( ',', $brand_ids ) . ")";
        $result = $this->_pdo->getRows ( $sql );
        $ret = array ();
        foreach ( $result as $val ) {
            $ret[$val['brand_id']] = $val;
        }
        return $ret;
    }
}