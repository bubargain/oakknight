<?php

namespace app\service;

use \app\dao\CmsTextlinkDao;
use \app\service\BaseSrv;

class MenuSrv extends BaseSrv {
	public function getList($local = 4) {
        return CmsTextlinkDao::getSlaveInstance ()->getList('sort ASC, id ASC',array('b.status = 1 and b.loc_id = '.intval($local)), '0, 1000');
	}

    public function getMenuMap($local = 4) {
        $list = $this->getList($local);
        return self::format($list, true);
    }

    public function getAllMenuMap($local) {
        $list = CmsTextlinkDao::getSlaveInstance ()->getList('sort ASC, id ASC',array('b.loc_id = '.intval($local)), '0, 1000');
        return self::format($list, false);
    }


    private function format( $list, $ex ) {
        $menu = array();
        foreach($list as $r) {
            $maps[$r['parent_id']][$r['id']] = $r['id'];
            $info[$r['id']] = $r;
        }

        foreach($maps[0] as $_one) {
            $row = $info[$_one];

            $row['alt'] = $row['alt'] ? $row['alt'] : trim($row['title']);
            if($row['utype'] == 'web') {
                $row['type'] = 'web';
                $row['weburl'] = $row['url'];
            }
            else {
                if($ex)
                    $row['url'] = self::str2arr ( $row['url'], $_one );

                $row['type'] = isset($maps[$_one]) ? 'list' : 'node';
            }

            if(isset($maps[$_one])) {
                /*
                $row['list'][0] = $row;
                $row['list'][0]['type'] = 'node';
                */
                foreach($maps[$_one] as $_two) {
                    $_row = $info[$_two];

                    $_row['alt'] = $_row['alt'] ? $_row['alt'] : trim($_row['title']);
                    if($_row['utype'] == 'web') {
                        $_row['type'] = 'web';
                        $_row['weburl'] = $_row['url'];
                    }
                    else {
                        if($ex)
                            $row['url'] = self::str2arr ( $row['url'], $_one );

                        $_row['type'] = isset($maps[$_two]) ? 'list' : 'node';
                    }

                    $row['list'][] = $_row;
                }
            }
            $menu[] = $row;
        }
        return $menu;
    }

    private function str2arr($str, $id) {
        $urlarr = parse_url ( $str );
        parse_str ( $urlarr ['query'], $arr );
        $arr ['id'] = $id;
        return $arr;
    }
}