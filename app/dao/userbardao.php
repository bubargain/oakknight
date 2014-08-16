<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UserBarDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_user_bar';
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
        $sql = "select * from ".self::getTableName()." where 1";
        if($params['bar_id'])
            $sql .= " and bar_id=".intval($params['bar_id']);

        $sql .= " limit " . $limit;
        return $this->_pdo->getRows($sql);
    }



        //$params['bar_id'] = $request->bar_id;
}