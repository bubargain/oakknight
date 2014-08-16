<?php
namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;


class ContacterDao extends YmallDao {
	protected static $_master; //单例的主库dao  getMasterInstance();
	protected static $_slave;  //单例的从库dao  getSlaveInstance();
	
	public function getTableName() {
		return 'ym_contact_info';
	}
	public function getPKey() {
		return 'contact_id';
	}

    
}
