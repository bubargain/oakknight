<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\coupon;
use app\dao\couponDao;
use app\service\BaseSrv;
use app\dao\UserCouponDao;

class UserCouponSrv extends BaseSrv {

    public function getList($params, $limit = '0, 9', $order) {
        if(!$order) {
            $order = $params['state'] == 'history' ? 'c.end_time desc' : 'c.from_time asc';
        }

        return UserCouponDao::getSlaveInstance()->getList($params, $limit, $order);
    }

    public function getListCnt($params) {
        return UserCouponDao::getSlaveInstance()->getListCnt($params);
    }

    public function send($info) {
        //id,cpn_id,coupon_sn,state,amount,order_id,user_id,ctime,utime,

        $info['coupon_sn'] = self::createSn($info['cpn_id']);
        $info['state'] = 1;
        $info['order_id'] = 0;
        $info['ctime'] = $info['utime'] = time();

        try{
            $info['id'] = UserCouponDao::getMasterInstance()->add($info);
            return $info;
        }catch (\Exception $e) {
            throw $e;
        }
    }

    private function createSn($cpn_id) {
        $cpn_id = $cpn_id % 10000;
        return date('dHis') . str_pad ( $cpn_id , 5 , 0, STR_PAD_LEFT ) . rand(5, 15);
    }
}