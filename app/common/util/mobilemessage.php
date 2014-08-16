<?php
/**
 * @author wanjilong@yoka.com
 * @date 2013-06-26
 * @desc depend on curl
 * http://chufa.lmobile.cn/submitdata/service.asmx/g_Submit
 */
namespace app\common\util;

class MobileMessage {
    const URL = "http://chufa.lmobile.cn/submitdata/service.asmx/g_Submit";

    const S_NAME = "dlbjkmfs";      //提交账户
    const S_PWD = "87654321";       //提交账户密码
    const S_CORPID = "";            //企业代码
    const S_PRDID = "1011818";      //产品编号



    public function send($mobile, $msg) {

        if(!$mobile || !$msg)
            throw new \Exception('phone and message must be needed!', 101);

        if(!self::isMobile($mobile))
            throw new \Exception('phone is wrong!', 102);

        if(strlen($msg)>300)
            throw new \Exception('msg must by less 300!', 103);

        $data = "sname=" . self::S_NAME .
            "&spwd=" . self::S_PWD .
            "&scorpid=" . self::S_CORPID .
            "&sprdid=" . self::S_PRDID .
            "&sdst=" . $mobile .
            "&smsg=" . rawurlencode($msg);


        $cs = curl_init();
        curl_setopt($cs, CURLOPT_URL, self::URL . "?" . $data);
        curl_setopt($cs, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($cs, CURLOPT_FAILONERROR, true);
        curl_setopt($cs, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cs, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($cs, CURLOPT_TIMEOUT, 30);
        $ret_code = curl_exec($cs);
        if(!$ret_code)
            throw new \Exception('没有返回值,可能短信网关无法连接!', 400000);

        curl_close($cs);

        $ret = simplexml_load_string($ret_code);

        $state = intval($ret->State);
        $msg = trim($ret->MsgState);

        if($state != 0)
            throw new \Exception($msg, $state);

        return true;
    }

    private function isMobile($phone) {
        return preg_match('/^1[0-9]{10}$/', $phone);
    }
}