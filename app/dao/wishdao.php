<?php
namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;


class WishDao extends YmallDao {
	protected static $_master; //单例的主库dao  getMasterInstance();
	protected static $_slave;  //单例的从库dao  getSlaveInstance();
	
	public function getTableName() {
		return 'ym_wish';
	}
	
	public function getPKey() {
		return 'wish_id';
	}
	
	
}