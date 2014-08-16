<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UserInfoDao extends YmallDao {
	protected static $_master; // 单例的主库dao getMasterInstance();
	protected static $_slave; // 单例的从库dao getSlaveInstance();
	public function getTableName() {
		return 'ym_user_info';
	}
	public function getPKey() {
		return 'user_id';
	}
	public function getList($params, $limit = '0,9', $sort = 'a.user_id desc ') {
		$sql = "SELECT * FROM ym_user AS a, ym_user_info AS b WHERE a.user_id = b.user_id " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function getListCnt($params) {
		$sql = "SELECT COUNT(*) FROM ym_user AS a, ym_user_info AS b WHERE a.user_id = b.user_id " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	public function getInfo($user_id) {
		$sql = "SELECT * FROM ym_user AS a, ym_user_info AS b WHERE a.user_id = b.user_id AND a.user_id =  " . $user_id;
		return $this->_pdo->getRow ( $sql );
	}
    public function getInfoByName($user_name) {
        $sql = "SELECT * FROM ym_user_info WHERE user_name = ? ";
        return $this->_pdo->getRow ( $sql , array($user_name));
    }
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return ' AND ' . implode ( ' AND ', $params );
		} else {
			return '';
		}
	}
	/**
	 * 降低统计数值
	 *
	 * @param
	 *        	$id
	 * @param $filed :数据库字段名        	
	 * @param
	 *        	$num
	 * @return mixed
	 */
	function decrement($id, $filed, $num) {
		$sql = "UPDATE " . self::getTableName () . " SET `$filed`=`$filed` - $num WHERE user_id=$id";
		return $this->_pdo->exec ( $sql );
	}

	/**
	 * 增加统计数值
	 *
	 * @param
	 *        	$id
	 * @param
	 *        	$filed:数据库字段名
	 * @param
	 *        	$num
	 * @return mixed
	 */
	function increment($id, $filed, $num) {
		$sql = "UPDATE " . self::getTableName () . " SET `$filed`=`$filed` + $num WHERE user_id=$id";
		return $this->_pdo->exec ( $sql );
	}

    public function getInfoByIds($user_ids) {
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE user_id IN (" . implode ( ',', $user_ids ) . ")";
        $result = $this->_pdo->getRows ( $sql );
        $ret = array ();
        foreach ( $result as $val ) {
            $ret[$val['user_id']] = $val;
        }
        return $ret;
    }
}