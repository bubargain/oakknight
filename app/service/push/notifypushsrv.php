<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\push;
use app\service\push\BasePushSrv;
use app\dao\PushDao;
use app\dao\PushTokenDao;
use app\dao\NotifyGoodsDao;

class NotifyPushSrv extends BasePushSrv {
    var $params = array();
    public function init($info) {
        $this->params = json_decode($info['extra'], true);
    }

    public function getPusherCnt() {
        $where = $this->makeSql();
        $sql = "select count(w.user_id)
        from ym_notify_goods w inner join ym_user_info u on w.user_id=u.user_id
        where u.push_token>'' and ".$where ;
        return NotifyGoodsDao::getSlaveInstance()->getpdo()->getOne($sql);
    }

    public function getAllCnt() {
        $where = $this->makeSql();
        $sql = "select count(w.user_id)
        from ym_notify_goods w
        where ".$where;
        return NotifyGoodsDao::getSlaveInstance()->getpdo()->getOne($sql);
    }


    public function getPusher($page = 1, $size = 5000) {
        $where = $this->makeSql();
        $sql = "select u.push_token
        from ym_user_info u inner join ym_notify_goods w on w.user_id=u.user_id
        where u.push_token>'' and ".$where ;

        if( $page <= 0 )
            $page = 1;

        $limit = ' limit ' . ($page - 1) * $size . ',' . $size;

        $sql .= $limit;
        return PushTokenDao::getSlaveInstance()->getpdo()->getRows($sql);
    }

    public function update($id, $data) {

    }

    public function makeSql() {
        $sql = ' 1=1 ';
        if($this->params['goods_id'])
            $sql .= ' and w.goods_id='.intval($this->params['goods_id']);
        if($this->params['start'])
            $sql .= ' and w.ctime>='.$this->params['start'];
        if($this->params['end'])
            $sql .= ' and w.ctime<'.$this->params['end'];

        return $sql;

    }

    public function batchDrop($goods_id) {
        return NotifyGoodsDao::getMasterInstance()->delete( array('goods_id'=>$goods_id) );
    }

    public function notify($id) {
        PushDao::getMasterInstance()->edit($id, array('status'=>1));
        self::batchDrop($this->params['goods_id']);
    }
}