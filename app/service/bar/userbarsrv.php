<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\bar;

use app\dao\BarDao;
use app\dao\UserBarDao;
use app\service\BaseSrv;
use \app\service\bar\SysBarSrv;
use \sprite\cache\CacheManager;

class UserBarSrv extends BaseSrv {

    public function set($id, $num = 1) {
        $info = UserBarDao::getMasterInstance()->find($id);
        if(!$info)
            return false;

        $_d = array('left'=>$info['left'] - $num, 'utime'=>time() );

        UserBarDao::getMasterInstance()->edit($id, $_d);

        $info['left'] = $_d['left'];
        $info['utime'] = $_d['utime'];

        return $info;
    }

    /**
     * @param $user_id
     * @param $uuid
     * @return array
     */
    public function push($user_id, $uuid) {
        if($user_id)
            $info = self::getUserPush($user_id);

        if(!$info)
            $info = self::getUUIDPush($uuid);

        return $info;
    }

    /**
     * @param $uuid
     * @return array
     * @desc 根据设备号取得 系统 bar
     */
    public function getUUIDPush($uuid) {

        $SysBarSrv = new SysBarSrv();
        $sys = $SysBarSrv->getAll( 1 );
        if(!$sys)
            return array();

        $list = self::getAllBuyUUID($uuid, array_keys($sys));

        foreach($list as $r) {//删除过期bar
            $tasks[$r['bar_id']] = $r;
        }
        unset($list);

        $info = $bar = array();
        foreach($sys as $r) {//增加新添bar
            if(!$tasks[$r['bar_id']])
                $tasks[$r['bar_id']] = self::makeInfo($r, 0, $uuid);

            if(!$tasks[$r['bar_id']])
                continue;

            $tmp = $tasks[$r['bar_id']];

            if(!$info || ( $tmp['left']>0 && $tmp['end_time'] > time() && $info['start_time'] < $tmp['start_time'] ) )
                $info = $tasks[$r['bar_id']];
        }
        if($info) {
            $info = array_merge($info, $sys[$info['bar_id']]);
        }
        return $info;
    }

    public function getUserPush($user_id) {
        $_time = time();
        $sql = "select * from ym_user_bar where user_id=? and `type`>1 and `left`>0 and start_time<=? and end_time>? order by start_time desc limit 1";

        $info = UserBarDao::getSlaveInstance()->getpdo()->getRow($sql, array($user_id, $_time, $_time));

        if(!$info)
            return false;

        $bar = BarDao::getSlaveInstance()->find($info['bar_id']);

        return array_merge($info, $bar);
    }

    /**
     * @param $uuid
     * @param $bar_ids
     * @return mixed
     * @desc 根据设备号及bar_id 列表返回系统bar信息
     */
    public function getAllBuyUUID($uuid, $bar_ids) {
        $dao = UserBarDao::getMasterInstance();
        $sql = "select * from ".$dao->getTableName()." where uuid=? and bar_id in(".implode(',', $bar_ids).")";
        return $dao->getpdo()->getRows($sql, array($uuid));
    }

    public function makeInfo($bar, $user_id, $uuid) {
        $info = array();
        $info['bar_id'] = $bar['bar_id'];
        $info['type'] = $bar['type'];
        $info['times'] = $bar['times'];
        $info['left'] = $bar['times'];
        $info['start_time'] = $bar['start_time'];
        $info['end_time'] = $bar['end_time'];
        $info['user_id'] = $user_id;
        $info['uuid'] = $uuid;
        $info['utime'] = $info['ctime'] = time();

        $info['id'] = UserBarDao::getMasterInstance()->add($info);
        return $info;
    }
}