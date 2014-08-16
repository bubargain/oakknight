<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UserDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_user';
	}
	public function getPKey() {
		return 'user_id';
	}
	public function nameToIds($user_names) {
		if (! $user_names || ! is_array ( $user_names ))
			return array ();
		
		$sql = 'select * from ' . self::getTableName () . " where user_name in( '" . implode ( "','", $user_names ) . "')";
		$list = $this->_pdo->getRows ( $sql );
		$u = array ();
		foreach ( $list as $r ) {
			$u [$r ['user_id']] = $r ['user_name'];
		}
		return $u;
	}
	
	// 根据user_ids数组获取用户信息
	public function getInfoByUserIds($user_ids) {
		if (! $user_ids || ! is_array ( $user_ids ))
			return array ();
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE user_id IN (" . implode ( ',', $user_ids ) . ")";
		$result = $this->_pdo->getRows ( $sql );
		$ret = array ();
		foreach ( $result as $val ) {
			$ret [$val ['user_id']] = $val;
		}
    	return $ret;
    }

    public function getAllBrSearch($params) {
        $sql = 'select * from ' . self::getTableName () . " where 1";
        if($params['start_time'])
            $sql .= ' and ctime>='.$params['start_time'];

        if($params['end_time'])
            $sql .= ' and ctime<'.$params['end_time'];

        return $this->_pdo->getRows( $sql );
    }
}