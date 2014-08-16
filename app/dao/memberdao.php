<?php
namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;


class MemberDao extends YmallDao {
	protected static $_master; //单例的主库dao  getMasterInstance();
	protected static $_slave;  //单例的从库dao  getSlaveInstance();
	
	public function getTableName() {
		return 'ecm_member';
	}
	public function getPKey() {
		return 'user_id';
	}
}