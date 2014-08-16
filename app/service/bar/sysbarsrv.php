<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\bar;

use app\dao\BarDao;
use app\service\BaseSrv;
use \sprite\cache\CacheManager;

class SysBarSrv extends BaseSrv {

    public function getAll( $type ) {
        //$cache = CacheManager::getInstance();
        $_time = time();
        $sql = 'select * from ym_bar where start_time<=? and end_time>? and `type`=?';
        $tmp = BarDao::getSlaveInstance()->getpdo()->getRows($sql, array($_time, $_time, $type));
        $list = array();
        foreach($tmp as $r) {
            $list[$r['bar_id']] = $r;
        }
        return $list;
    }
}