<?php

namespace admin\controller;

use \app\dao\SettingDao;

class SaleTypeController extends BaseController {
	public function index($request, $response) {
		$response->title = '商品类型添加';

        $ukey = 'sale_type_arr';
        $info = SettingDao::getSlaveInstance()->find($ukey);

        $list = unserialize($info['uvalue']);

        $response->list = $list;
        $response->CDN_YMALL = CDN_YMALL;
        $this->layoutSmarty();
	}

    public function add($request, $response) {
        $ukey = 'sale_type_arr';
        $data = SettingDao::getSlaveInstance()->find($ukey);
        $list = unserialize($data['uvalue']);

        if(self::isPost()) {
            $info = self::formatPost($request);

            $exist = false;
            foreach($list as $r) {
                if($r['key'] == $info['key'])
                    $exist = true;
            }

            if($exist)
                $this->showError('key 已经重复，请重置');

            $list[] = $info;
            $data['uvalue'] = serialize($list);

            if(!$data['ukey']) {
                $data['ukey'] = $ukey;
                SettingDao::getMasterInstance()->add($data);
            }
            else {
                SettingDao::getMasterInstance()->edit($ukey, $data);
            }
            header ( "Location: index.php?_c=saleType" );
        }
        else {
            $max = 0;
            foreach($list as $r) {
                $max = $r['key'] > $max ? $r['key'] : $max;
            }

            $response->info = array('key'=>++$max);
            $this->layoutSmarty('edit');
        }
    }

	public function edit($request, $response) {
        $ukey = 'sale_type_arr';
        $data = SettingDao::getSlaveInstance()->find($ukey);
        $list = unserialize($data['uvalue']);

        if(self::isPost()) {
            $info = self::formatPost($request);

            $ukey = 'sale_type_arr';
            $data = SettingDao::getSlaveInstance()->find($ukey);
            $list = unserialize($data['uvalue']);
            foreach($list as $k=>$r) {
                if($r['key'] == $info['key'])
                    $list[$k] = $info;
            }

            $data['uvalue'] = serialize($list);

            SettingDao::getMasterInstance()->edit($ukey, $data);

            header ( "Location: index.php?_c=saleType" );
        }
        else {
            $key = $request->key;
            foreach($list as $r) {
                if($r['key'] == $key)
                    $info = $r;
            }

            if(!$info)
                $this->showError('不存在');

            $response->info = $info;
            $this->layoutSmarty('edit');
        }
	}

    private function formatPost($request) {
        $info = array();

        $info['title'] = $request->post('title', '');
        $info['key'] = $request->post('key', 0);

        $info['big']['x'] = $request->post('b_x', 0);
        $info['big']['y'] = $request->post('b_y', 0);
        $info['big']['img'] = $request->post('b_img', '');

        $info['small']['x'] = $request->post('s_x', 0);
        $info['small']['y'] = $request->post('s_y', 0);
        $info['small']['img'] = $request->post('s_img', '');

        if(empty($info['big']['x']) || empty($info['big']['y']) || empty($info['big']['img'])
            || empty($info['small']['x']) || empty($info['small']['y']) || empty($info['small']['img'])
            || empty($info['title']) || empty($info['key'])
        )
            $this->showError('请完善信息');

        return $info;
    }
}