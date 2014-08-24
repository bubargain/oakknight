<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service;
use \app\dao\VerifyCodeDao;
use \app\dao\VerifyPhoneDao;
use \app\common\util\MobileMessage;


class VerifySrv extends BaseSrv {
    const USER_SEND_TIMES = 150;
    /**
     * @param $phone
     * @param $type : login, reg, pwd
     * @param $code
     * @param bool $once 使用一次标识
     * @return bool
     * @desc 校验手机验证码
     */
    public function check($phone, $type, $code, $once = true) {
        $info = VerifyCodeDao::getSlaveInstance()->getValid( $phone, $type );
        if(!$info || $info['code'] != $code)
            throw new \Exception('您填写的验证码有误哦，重新输入吧', 4000);

        if($once)
            VerifyCodeDao::getMasterInstance()->delete($info['id']);
    }

    /**
     * @param $phone
     * @param $type
     * @return array|bool
     */
    public function make($phone, $type) {
      
    	
    	$_time = time();
        try{
            if( $info = VerifyPhoneDao::getSlaveInstance()->find( array( 'phone'=>$phone) ) ) {
                if( date('Ymd', $info['utime']) != date('Ymd', $_time))
                   $info['days'] = 0;

                if( $info['days'] >= self::USER_SEND_TIMES )
                    throw new \Exception('您发送请求太快了，请休息一下稍后再试。', 4002);
            }
            else {
                $info['id'] = VerifyPhoneDao::getMasterInstance()->add(array('phone'=>$phone, 'ctime'=>$_time, 'utime'=>$_time) );
                $info['days'] = 0;
                $info['times'] = 0;
            }

            $data = VerifyCodeDao::getSlaveInstance()->getValid( $phone, $type );

            try{
                VerifyCodeDao::getMasterInstance()->beginTransaction();

                if(!$data) {
                    $data = array('phone'=>$phone, 'type'=>$type, 'code'=>self::code(), 'ctime'=>time());
                    VerifyCodeDao::getMasterInstance()->add($data);
                }

                VerifyPhoneDao::getMasterInstance()->edit($info['id'], array('times'=>$info['times'] + 1, 'days'=>$info['days'] + 1, 'utime'=>$_time));

                //取消发送手机验证码
                self::send($phone, $type, $data['code']);

                VerifyCodeDao::getMasterInstance()->commit();
            }
            catch(\Exception $e) {
                VerifyCodeDao::getMasterInstance()->rollBack();
                throw $e;
            }
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

    public function send($phone, $type, $code) {

        $msg = self::getMessage($type, $code);
        try{
            $mobileMessage = new MobileMessage();
            $mobileMessage->send($phone, $msg);

        }
        catch(\Exception $e) {
            throw $e;
        }
    }


    /*
     * @author daniel ma
     * @date  2014/08/24
     * @update :return data format
     */
    private function getMessage($type, $code) {
        $list = array(
            'login'=>'登录验证码：',
            'reg'=>'注册验证码：',
            'pwd'=>'忘记密码验证码：',
            'default'=>'忘记密码验证码：',
        );

        $msg = isset($list[$type]) ? $list[$type] : $list['default'];
       // return str_replace('#CODE#', $code, $msg);
        return array($code,'5');
    }

    /**
     * @return int
     * @desc
     */
    private function code() {
        return rand(1000, 9999);
    }
}