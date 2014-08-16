<?php

namespace touch\controller;

use sprite\lib\Debug;

class UserController extends BaseController {
	public function index($request, $response) {
		$this->layoutSmarty ();
	}

    public function ajaxPaying($request, $response) {
        try{
            if ($this->has_login) {
                $ret = array('user_id'=>$this->current_user['user_id'], 'paying'=>0);
                $ret['paying'] = \app\dao\OrderDao::getSlaveInstance ()->getCntByStatus( $this->current_user ['user_id'], 10 );
            }
            else {
                $ret = array('user_id'=>0, 'paying'=>0);
            }
            $ret['status'] = 200;
            Debug::log('key', $ret);
            $this->renderJson( $ret );
        }
        catch(\Exception $e) {
            $this->renderJson(array('status'=>500, 'code'=>$e->getCode(), 'message'=>$e->getMessage() ) );
        }
    }
}