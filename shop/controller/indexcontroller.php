<?php

namespace shop\controller;

use \app\service\appcountsrv;

class IndexController extends BaseController {
	public function index($request, $response) {

        \sprite\lib\Debug::log('user', $this->current_user);
        $response->current_user = $this->current_user;


        $this->layoutSmarty();
	}

}