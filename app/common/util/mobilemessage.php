<?php
/**
 * @author wanjilong@yoka.com
 * @date 2013-06-26
 * @desc depend on curl
 * http://chufa.lmobile.cn/submitdata/service.asmx/g_Submit
 */

/**
 * @author  daniel ma
 * @data 2014-08-24
 * @desc sms interface provide by yuntongxun.com
 */
namespace app\common\util;
use \app\common\sms\CCPRestSDK;



class MobileMessage {


    /**
     * 发送模板短信
     * @param to 手机号码集合,用英文逗号分开
     * @param datas 内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
     * @param $tempId 模板Id
     */
    public function send($to,$datas,$tempId=5175)
    {
    	//测试期间，暂时关闭
    	
        // 初始化REST SDK
        $accountSid=$_SERVER['sms']['accountSid'];
        $accountToken=$_SERVER['sms']['accountToken'];
        $appId=$_SERVER['sms']['appId'];
        $serverIP=$_SERVER['sms']['serverIP'];
        $serverPort=$_SERVER['sms']['serverPort'];
        $softVersion=$_SERVER['sms']['softVersion'];


        $rest = new CCPRestSDK($serverIP,$serverPort,$softVersion);
        $rest->setAccount($accountSid,$accountToken);
        $rest->setAppId($appId);

        // 发送模板短信
        //echo "[O&K]Sending TemplateSMS to $to <br/>";
        $result = $rest->sendTemplateSMS($to,$datas,$tempId);
        if($result == NULL ) {
            //echo "result error!";
            throw new \Exception('没有返回值,可能短信网关无法连接!', 400000);

        }
        if($result->statusCode!=0) {
            throw new \Exception($result->statusMsg,400001);
            //echo "您的发送频率过快，请稍后再试";die();
            //echo "error msg :" . $result->statusMsg . "<br>";
            //TODO 添加错误处理逻辑
        }else{
           // echo "Sendind TemplateSMS success!<br/>";
            // 获取返回信息
            $smsmessage = $result->TemplateSMS;
            //echo "dateCreated:".$smsmessage->dateCreated."<br/>";
            //echo "smsMessageSid:".$smsmessage->smsMessageSid."<br/>";
            //TODO 添加成功处理逻辑
            return true;
        }
        
    	return true;
    }

    //旧的短信接口
    public function send_bk($mobile, $msg) {

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