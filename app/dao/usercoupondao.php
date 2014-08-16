<?php

namespace app\dao;

use app\dao\YmallDao;
/* coupon:state 0：未开始；1：进行中； 2:已使用，未支付； 3:已使用，已支付； 4:已过期  */

class UserCouponDao extends YmallDao {
	protected static $_master;
	protected static $_slave;

    public function getTableName() {
		return 'ym_user_coupon';
	}

	public function getPKey() {
		return 'ucpn_id';
	}

    public function getListCnt($params) {
        $sql = "select count(*) from ".self::getTableName()." u inner join ym_coupon c on c.cpn_id=u.cpn_id where 1";
        $sql .= self::makeSql($params);
        return $this->_pdo->getOne($sql);
    }

    public function getList($params = array(), $limit = '0, 20', $sort = 'c.end_time desc') {
        $sql = "select u.*, c.* from ".self::getTableName()." u inner join ym_coupon c on c.cpn_id=u.cpn_id where 1";
        $sql .= self::makeSql($params);
        $sql .= " order by " . $sort . " limit " . $limit;

        $list = $this->_pdo->getRows($sql);

        $_time = time();
        foreach($list as $k=>$v) {
            if($_time < $v['from_time']) {
                $list[$k]['state'] = 0;
                $list[$k]['state_str'] = '未开启';
            }
            else {
                if($v['state'] == 2 || $v['state'] == 3) {
                    $list[$k]['state_str'] = '已使用';
                }
                elseif($v['state'] == 1) {
                    $list[$k]['state_str'] = '进行中';
                }
                if($_time >= $v['end_time'] && $v['state'] == 1) {
                    $list[$k]['state'] = 4;
                    $list[$k]['state_str'] = '已结束';
                }
            }
        }
        return $list;
    }

    public function getSumInfo($params) {
        $ret = array(
            'count'=>0, 'order_count'=>0,'money'=>0, 'amount'=>0,'order_amount'=>0, 'goods_amount'=>0,
            'pay_order_count'=>0,'pay_money'=>0, 'pay_amount'=>0,'pay_order_amount'=>0, 'pay_goods_amount'=>0
        );
        $sql = "select count(*) order_count, sum(c.money) money, sum(u.amount) amount, sum(u.order_amount) order_amount, sum(u.goods_amount) goods_amount from ".self::getTableName()." u inner join ym_coupon c on c.cpn_id=u.cpn_id where 1";
        $sql .= self::makeSql($params);

        $sql .= ' and u.state = 2';
        $info = $this->_pdo->getRow($sql);
        if($info)
            $ret = array_merge($ret, $info);

        $sql = "select count(*) pay_order_count,sum(c.money) pay_money, sum(u.amount) pay_amount, sum(u.order_amount) pay_order_amount, sum(u.goods_amount) pay_goods_amount from ".self::getTableName()." u inner join ym_coupon c on c.cpn_id=u.cpn_id where 1";
        $sql .= self::makeSql($params);

        $sql .= ' and u.state = 3';
        $info = $this->_pdo->getRow($sql);
        if($info)
            $ret = array_merge($ret, $info);

        return $ret;
    }

    private function makeSql($params) {
        $sql = '';
        if($params['user_id'])
            $sql .= " and u.user_id=".intval($params['user_id']);

        if($params['user_name'])
            $sql .= " and u.user_name='".$params['user_name']."'";

        if($params['coupon_sn'])
            $sql .= " and u.coupon_sn=".$params['coupon_sn'];

        if($params['cpn_id'])
            $sql .= " and u.cpn_id=".intval($params['cpn_id']);

        if($params['state'] == 'history') {
            $sql .= " and (u.state>1 or c.end_time<".time().')';
        }
        elseif($params['state'] == 'valid') {
            $sql .= " and u.state=1 and c.from_time<=".time() . " and c.end_time>".time();
        }
        elseif($params['state'] == 'order') {
            $sql .= " and u.state=1 and c.from_time<=".time() . " and c.end_time>".time();
        }
        return $sql;
    }

    public function getInfo($id) {
        $sql = "select c.*, u.* from ".self::getTableName()." u inner join ym_coupon c on c.cpn_id=u.cpn_id where ucpn_id = ?";
        $info = $this->_pdo->getRow( $sql, array($id) );
        if(!$info)
            return array();

        $_time = time();

        if($_time < $info['from_time']) {
            $info['state'] = 0;
            $info['state_str'] = '未开启';
        }
        else {//title coupon_sn order_amount amount state state_str from_time end_time
            if($info['state'] == 2 || $info['state'] == 3) {
                $info['state_str'] = '已使用';
            }
            elseif($info['state'] == 1) {
                $info['state_str'] = '进行中';
            }
            if($_time >= $info['end_time'] && $info['state'] == 1) {
                $info['state'] == 4;
                $info['state_str'] = '已结束';
            }
        }

        return $info;
    }
}