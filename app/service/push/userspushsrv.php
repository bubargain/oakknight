<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\push;
use app\dao\UserInfoDao;
use app\service\BaseSrv;
use app\dao\PushDao;

class UsersPushSrv extends BaseSrv {
    var $params = array();
    public function init($info) {
        $params = json_decode($info['extra'], true);
        preg_match_all('/[0-9]{11}/', $params['users'], $ret);
        $params['users'] = $ret[0];
        $this->params = $params;
    }

    public function getPusherCnt() {
        $sql = "select count(*) from ym_user_info where push_token>'' and user_name in( '".implode("','", $this->params['users']) ."')" ;
        return UserInfoDao::getSlaveInstance()->getpdo()->getOne( $sql );
    }

    public function getAllCnt() {
        return count( $this->params['users']);
    }

    public function getPusher($page = 1, $size = 5000) {
        if( $page <= 0 )
            $page = 1;

        $limit = ($page - 1) * $size . ',' . $size;
        $sql = "select push_token from ym_user_info where push_token>'' and user_name in( '".implode("','", $this->params['users']) ."')" . " limit ".$limit;
        return UserInfoDao::getSlaveInstance()->getpdo()->getRows($sql);
    }

    public function notify($id) {
        PushDao::getMasterInstance()->edit($id, array('status'=>1));
    }
}