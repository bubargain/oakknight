<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\push;
use app\service\BaseSrv;

class BasePushSrv extends BaseSrv {
    public function init($info) {}

    public function getPusherCnt() {
        return 2;
    }

    public function getPusher() {
        return array(
            '1234456',
            '1234456',
        );
    }
}