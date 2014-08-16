<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\push;
use app\service\BaseSrv;
use app\dao\PushDao;
use app\dao\PushTokenDao;

class AllPushSrv extends BaseSrv {
    public function init($info) {}

    public function getAllCnt() {
        return PushTokenDao::getSlaveInstance()->getListCnt();
    }

    public function getPusherCnt() {
        return self::getAllCnt();
    }



    public function getPusher($page = 1, $size = 5000) {
        if( $page <= 0 )
            $page = 1;

        $limit = ($page - 1) * $size . ',' . $size;
        return PushTokenDao::getSlaveInstance()->getList($limit);
    }

    public function notify($id) {
        PushDao::getMasterInstance()->edit($id, array('status'=>1));
    }
}