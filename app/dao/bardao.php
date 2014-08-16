<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class BarDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_bar';
	}
	public function getPKey() {
		return 'bar_id';
	}

    public function getListCnt($params) {
        $sql = "select count(*) from ".self::getTableName()." where 1";
        if($params['type'])
            $sql .= " and type=".intval($params['type']);

        return $this->_pdo->getOne($sql);
    }

    public function getList($params, $limit = '0, 20') {
        $sql = "select * from ".self::getTableName()." where 1";
        if($params['type'])
            $sql .= " and type=".intval($params['type']);

        $sql .= " order by bar_id desc limit " . $limit;
        $list = $this->_pdo->getRows($sql);

        $_time = time();
        foreach($list as $k=>$v) {
            if($_time < $v['start_time']) {
                $list[$k]['status'] = 1;
                $list[$k]['status_str'] = '未开启';
            }
            else {
                if($_time >= $v['end_time']) {
                    $list[$k]['status'] = 3;
                    $list[$k]['status_str'] = '已结束';
                }
                else {
                    $list[$k]['status'] = 2;
                    $list[$k]['status_str'] = '进行中';
                }
            }
        }
        return $list;
    }

    public function getInfoByIds($ids) {
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE bar_id IN (" . implode ( ',', $ids ) . ")";
        $result = $this->_pdo->getRows ( $sql );
        $ret = array ();
        $_time = time();
        foreach ( $result as $val ) {
            if($_time < $val['start_time']) {
                $val['status'] = 1;
                $val['status_str'] = '未开启';
            }
            else {
                if($_time >= $val['end_time']) {
                    $val['status'] = 3;
                    $val['status_str'] = '已结束';
                }
                else {
                    $val['status'] = 2;
                    $val['status_str'] = '进行中';
                }
            }
            $ret[$val['bar_id']] = $val;

        }
        return $ret;
    }
}