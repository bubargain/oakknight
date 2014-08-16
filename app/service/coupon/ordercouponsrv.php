<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\coupon;
use app\dao\couponDao;
use app\service\BaseSrv;
use app\dao\UserCouponDao;

class OrderCouponSrv extends BaseSrv {

    public function getList($user_id) {
        $params = array();
        $params['user_id'] = $user_id;
        $params['state'] = 'order';

        $limit = '0, 10';
        return UserCouponDao::getSlaveInstance()->getList($params, $limit);
    }
}