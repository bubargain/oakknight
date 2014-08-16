<?php

namespace shop\controller;

use \app\service\member;

class LoginController extends BaseController {
	public function index($request, $response) {
        $refer = $this->referer($request);
        if($this->has_login)
            header("Location: $refer");

        if(!$this->isPost()) {
            //显示登陆页
            //$this->layoutSmarty();
            $this->renderSmarty();
        }
        else {
            //登陆处理
            try{
                //var_dump($request->user_name,$request->pwd );
                $info = \app\dao\UserDao::getSlaveInstance()->find(array('user_name'=>$request->user_name));
                if(!$info || md5($request->pwd) != $info['password'])
                    throw new \Exception('账户或密码错误',4001);

                if(!self::isShoper($info['user_id']))
                    throw new \Exception('账户不是商家',4002);

                //写cookie
                $user_info = array('user_id'=>$info['user_id'], 'user_name'=>$info['user_name']);

                $cookie_user_info = base64_encode(serialize($user_info));
                setcookie('user_info', $cookie_user_info, time () + 15 * 60 );

                header("Location: $refer");
                exit();
            }
            catch(\Exception $e) {
                $response->warn = $e->getMessage();
                $this->renderSmarty();
            }
        }
	}

	public function logout() {
        setcookie('user_info', '', time () - 3600 );
		header ( 'Location: index.php?_c=login' );
	}

    private function isShoper($user_id) {
        return true;//return \app\dao\StoreDao::getSlaveInstance()->find($user_id) ? true : false;
    }

    private function referer($request) {
        $refer = 'index.php';
        if(isset($request->refer)) {
            $refer = $request->refer;
        }

        $search  = array('_c=login');
        $refer = str_replace($search, '', $refer);

        return $refer;
    }
}