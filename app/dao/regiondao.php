<?php
namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;


class RegionDao extends YmallDao {
	protected static $_master; //单例的主库dao  getMasterInstance();
	protected static $_slave;  //单例的从库dao  getSlaveInstance();
	
	public function getTableName() {
		return 'ym_region';
	}
	
	public function getPKey() {
		return 'region_id';
	}

    public function getList($pid = 0) {
        $sql = "select * from ".self::getTableName()." where parent_id=? order by sort_order desc ";
        return $this->_pdo->getRows($sql, array($pid));

    }
}