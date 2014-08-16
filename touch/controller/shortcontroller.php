<?php

namespace touch\controller;

class ShortController extends BaseController {

    public function index($request, $response) {
        $_short = $request->get('key', '');
        if(!$_short) {
            self::redirect('/');
        }

        $_url = \app\common\util\short::get($_short);
        if(!$_url)
            self::redirect('/');

        //user_id,uuid,type,action,item_id,info,
        $info = array();
        $info['user_id'] = 0;
        $info['type'] = 'proxy';
        $info['action'] = 'tuiguang';
        $info['info'] = array('key'=>$_short);

        $this->userLog($info);
        self::redirect($_url);
	}
}