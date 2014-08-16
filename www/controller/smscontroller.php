<?php
namespace www\controller;
use \app\dao\UserDao;
use \sprite\cache\Cache;

class SmsController extends AppBaseController
{
    /**
     * @param $request
     * @param $response
     * @desc 校验用户验证码
     */
    public function verify($request, $response) {
        try{
            $verifySrv = new \app\service\VerifySrv();
            $verifySrv->check($request->user_name, $request->type, $request->code);

            switch($request->type) {
                case 'login':
                	//清空密码
                	\app\dao\UserDao::getMasterInstance()->edit(array('user_name'=>$request->user_name),array('password'=>''));
                	$info = self::getLoginToken($request->user_name);
                    break;
                case 'reg':
                	$userBehavior =new \app\service\UserSrv();
					$data = $userBehavior->addUserByUserName($request->user_name, $this->header['clientid']);
                    $info = self::getLoginToken($request->user_name);
                    break;
                default:
                    $info = array('message'=>'ok');
                    break;
            }
            $this->result($info);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage() );
        }
	}

    /**
     * @param $request
     * @param $response
     *
     */
    public function login($request, $response) {

        try{
            $verifySrv = new \app\service\VerifySrv();
            $data = $verifySrv->make($request->user_name, $request->type);
            $this->result($data);
        }
        catch(\Exception $e) {
        	if($e->getCode() != 20001 )
            	$this->error($e->getCode(), $e->getMessage());
            else 
            	$this->error($e->getCode(), $e->getMessage(),'该手机号未注册');
        }
    }

    private function getLoginToken($phone) {
        $userSrv = new \app\service\UserSrv();
        return $userSrv->setTokenAfterLogin($phone);//phone => accesstoken, uid,
    }




}

