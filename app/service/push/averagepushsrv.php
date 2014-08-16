<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\push;
use app\service\BaseSrv;
use app\dao\PushDao;
use app\dao\PushTokenDao;

class AveragePushSrv extends BaseSrv {
    var $params = array();
    public function init($info) {
        $this->params = json_decode($info['extra'], true);
    }

    public function getPusherCnt() {
        $a = explode(',', $this->params['limit']);
        return intval($a[1]);
    }

    public function getAllCnt() {
        $a = explode(',', $this->params['limit']);
        return intval($a[1]);
    }


    public function getPusher($page = 1, $size = 5000) {
        $where = $this->makeSql();
        $sql = "select distinct(push_token) from ym_push_token where 1 ".$where ;

        return PushTokenDao::getSlaveInstance()->getpdo()->getRows($sql);
    }

    public function notify($id) {
        PushDao::getMasterInstance()->edit($id, array('status'=>1));
    }

    public function makeSql() {
        $sql = ' ';
        if($this->params['limit'])
            $sql .= ' limit '.$this->params['limit'];

        return $sql;
    }
}