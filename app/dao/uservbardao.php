<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UserVbarDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_user_vbar';
	}
	public function getPKey() {
		return 'id';
	}

    //getList getListCnt

    public function getListCnt($params) {
        $sql = "select count(*) from ".self::getTableName()." where 1";
        if($params['bar_id'])
            $sql .= " and bar_id=".intval($params['bar_id']);

        return $this->_pdo->getOne($sql);
    }

    public function getList($params, $limit = '0, 20') {
        $sql = "select b.*, u.* from ym_vbar b inner join ym_user_vbar u on b.bar_id=u.bar_id where 1";
        if($params['bar_id'])
            $sql .= " and b.bar_id=".intval($params['bar_id']);

        $sql .= " limit " . $limit;
        return $this->_pdo->getRows($sql);
    }

    public function getMyBars( $user_id, $start, $end ) {
        $sql = "select b.*, u.* from ym_vbar b inner join ym_user_vbar u on b.bar_id=u.bar_id where u.user_id=? and b.start_time<=? and b.end_time>? order by b.utime desc";
        return $this->_pdo->getRows($sql, array($user_id, $start, $end));
    }
}