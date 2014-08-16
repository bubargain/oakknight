<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class GoodsImageDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_goods_image';
	}
	public function getPKey() {
		return 'image_id';
	}

    public function getAll($goods_id) {
        $sql = "select * from ".self::getTableName()." where goods_id=? and is_del=0 order by sort_order desc, image_id desc ";
        return $this->_pdo->getRows($sql, array($goods_id));
    }
}