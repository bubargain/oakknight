<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;
/* coupon:state 0：未开始；1：进行中； 2:已使用，未支付； 3:已使用，已支付； 4:已过期  */

class CouponDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_coupon';
	}
	public function getPKey() {
		return 'cpn_id';
	}

    public function getListCnt($params) {
        $sql = "select count(*) from ".self::getTableName()." where 1";

        return $this->_pdo->getOne($sql);
    }

    public function getList($params, $limit = '0, 20') {
        $sql = "select * from ".self::getTableName()." where 1";
        $sql .= " order by cpn_id desc limit " . $limit;
        $list = $this->_pdo->getRows($sql);

        $_time = time();
        foreach($list as $k=>$v) {
            if($_time < $v['from_time']) {
                $list[$k]['state'] = 0;
                $list[$k]['state_str'] = '未开启';
            }
            else {
                if($_time >= $v['end_time']) {
                    $list[$k]['state'] = 4;
                    $list[$k]['state_str'] = '已结束';
                }
                else {
                    $list[$k]['state'] = 1;
                    $list[$k]['state_str'] = '进行中';
                }
            }
        }
        return $list;
    }

    public function getInfoByIds($ids) {
        $sql = "SELECT * FROM " . self::getTableName () . " WHERE cpn_id IN (" . implode ( ',', $ids ) . ")";
        $result = $this->_pdo->getRows ( $sql );
        $ret = array ();
        $_time = time();
        foreach ( $result as $val ) {
            if($_time < $val['from_time']) {
                $val['state'] = 0;
                $val['state_str'] = '未开启';
            }
            else {
                if($_time >= $val['end_time']) {
                    $val['state'] = 4;
                    $val['state_str'] = '已结束';
                }
                else {
                    $val['state'] = 1;
                    $val['state_str'] = '进行中';
                }
            }
            $ret[$val['bar_id']] = $val;

        }
        return $ret;
    }

}