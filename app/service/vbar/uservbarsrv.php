<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\vbar;

use app\dao\VbarDao;
use app\dao\UserVbarDao;
use app\service\BaseSrv;
use \sprite\cache\CacheManager;

class UserVbarSrv extends BaseSrv {
    /**
     * @param $user_id
     * @return array
     */
    public function push($user_id) {
        $_time = time();

        $cache = CacheManager::getInstance();

        $key = 'app_bar_'.$user_id;
        $ret = $cache->get($key);
        if(!$ret) {
            $ret = array('list'=>array(), 'utime'=>$_time, 'count'=>0);

            if($user_id > 0)
                $list = self::getValidUserBars($user_id, $_time, $_time);

            $sys = self::getValidSystemBars($_time, $_time);

            $len = count($list) + count($sys);
            $i = $j = $j_time = $i_time = 0;
            while($len--) {
                $i_time = isset($list[$i]) ? $list[$i]['utime'] : 0;
                $j_time = isset($sys[$j]) ? $sys[$j]['utime'] : 0;

                if($i_time > $j_time) {
                    $ret['list'][] = $list[$i++];
                }
                else {
                    $ret['list'][] = $sys[$j++];
                }
            }
            if( $ret['list'] ) {
                $ret['utime'] = $ret['list'][0]['utime'];
                $ret['count'] = count($ret['list']);
            }

            $cache->set($key, $ret, 1, 1*60);
        }
        return $ret;
    }

    private function getValidSystemBars( $start, $end ) {
        $list = VbarDao::getSlaveInstance()->getSysBars($start, $end);

        foreach($list as $k=>$row) {
            $list[$k] = self::formatShowBar($row);
        }
        return $list;
    }

    private function getValidUserBars( $user_id, $start, $end ) {
        $list = UserVbarDao::getSlaveInstance()->getMyBars($user_id, $start, $end);

        foreach($list as $k=>$row) {
            $list[$k] = self::formatShowBar($row);
        }
        return $list;
    }

    public function formatShowBar($bar) {
        $info = array();
        $info['img'] = CDN_YMALL . $bar['img'];
        $info['search'] = unserialize($bar['search']);
        $info['bar_id'] = $bar['bar_id'];
        $info['url'] = $bar['url'];
        $info['title'] = $bar['title'];
        $info['utime'] = $bar['utime'];

        $info['target'] = $bar['url'] ? 2 : 1;
        if($info['target'] == 1 && $info['search']['goods_id'])
            $info['target'] = 3;

        return $info;
    }

    public function makeInfo($bar, $user_id, $user_name) {
        $info = array();
        $info['user_id'] = $user_id;
        $info['user_name'] = $user_name;
        $info['bar_id'] = $bar['bar_id'];
        $info['utime'] = time();
        $info['id'] = UserVbarDao::getMasterInstance()->add($info);
        return $info;
    }
}