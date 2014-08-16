<?php

namespace www\controller;

use \app\service\MenuSrv;

class MenuController extends AppBaseController {
	// 菜单维护接口
    /**
     * < ver 2.0.2
     * @desc 不支持二级菜单
     */
	public function menu() {
		try {
            $srv = new MenuSrv();
            $list = $srv->getList(4);
			$menu = self::formatMenu( $list );
            $this->result( $menu );
		} catch ( \Exception $e ) {
			$this->error( $e->getCode (), $e->getMessage () );
		}
	}

    /**
     * ver 2.0.2+
     * @desc 不支持二级菜单
     */
    public function index() {
        try {
            $srv = new MenuSrv();
            $menu = $srv->getMenuMap(14);
            $this->result( $menu );
        } catch ( \Exception $e ) {
            $this->error( $e->getCode (), $e->getMessage () );
        }
    }

    private function formatMenu( $list ) {
        $menu = array();
        foreach($list as $r) {
            $maps[$r['parent_id']][$r['id']] = $r['id'];
            $info[$r['id']] = $r;
        }

        foreach($maps[0] as $_one) {
            if($this->header['appversion'] == '2.0.0' && $info[$_one]['utype'] == 'web')
                continue;

            $row = $info[$_one];

            $row['alt'] = $row['alt'] ? $row['alt'] : trim($row['title']);
            if($row['utype'] == 'web') {
                $row['type'] = 'web';
                $row['weburl'] = $row['url'];
            }
            else {
                $row['url'] = self::str2arr ( $row['url'], $_one );
                $row['type'] = isset($maps[$_one]) ? 'list' : 'node';
            }

            if(isset($maps[$_one])) {
                /**/
                $row['list'][0] = $row;
                $row['list'][0]['type'] = 'node';

                foreach($maps[$_one] as $_two) {
                    $_row = $info[$_two];
                    if($this->header['appversion'] == '2.0.0' && $_row['utype'] == 'web')
                        continue;

                    $_row['alt'] = $_row['alt'] ? $_row['alt'] : trim($_row['title']);
                    if($_row['utype'] == 'web') {
                        $_row['type'] = 'web';
                        $_row['weburl'] = $_row['url'];
                    }
                    else {
                        $_row['url'] = self::str2arr ( $_row['url'], $_two );
                        $_row['type'] = isset($maps[$_two]) ? 'list' : 'node';
                    }

                    $row['list'][] = $_row;
                }
            }
            $menu[] = $row;
        }
        return $menu;
    }

	public function str2arr($str, $id) {
		$urlarr = parse_url ( $str );
		parse_str ( $urlarr ['query'], $arr );
		$arr ['id'] = $id;
		return $arr;
	}
}