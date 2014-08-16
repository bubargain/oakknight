<?php
namespace www\controller;

use sprite\mvc\controller;
use \stdClass;

//www base controller
class BaseController extends Controller {
	public function __construct($request, $response) {
		parent::__construct($request, $response);

	}

    public function userLog($info) {
        $info['user_id'] = isset($info['user_id']) ? $info['user_id'] : 0;
        $info['uuid'] = isset($info['uuid']) ? $info['uuid'] : '';
        $info['info'] = $info['info'] ? serialize($info['info']) : '';
        $info['ctime'] = time();
        return \app\dao\UserLogDao::getMasterInstance()->add(
            $info
        );
    }
}