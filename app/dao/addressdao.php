<?php
namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;


class AddressDao extends YmallDao {
	protected static $_master; //单例的主库dao  getMasterInstance();
	protected static $_slave;  //单例的从库dao  getSlaveInstance();
	
	public function getTableName() {
		return 'ym_address';
	}
	public function getPKey() {
		return 'addr_id';
	}

    public function getDefault($user_id) {
        $sql = 'SELECT * FROM '.self::getTableName()." WHERE user_id=? ORDER BY is_default DESC, addr_id DESC LIMIT 0, 1";
        return $this->_pdo->getRow($sql, array($user_id));
    }

    public function myList($user_id, $limit) {
        $sql = 'SELECT * FROM '.self::getTableName()." WHERE user_id=? ORDER BY addr_id DESC LIMIT ".$limit;
        return $this->_pdo->getRows($sql, array($user_id));
    }
}