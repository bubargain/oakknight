<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\payment\appalipay;
use \app\service\payment\BasePaymentSrv;

class AppAlipayPaymentSrv extends BasePaymentSrv {

    var $_gateway   = '';
    /* 支付方式唯一标识 */
    var $_code      = '';
    public function __construct($config) {
        parent::__construct($config);

        $this->_config['partner'] = array('partner'=>'2088101989241025', 'seller'=>'shopadmin@yoka.com');
    }

    /**
     *    验证支付结果
     */
    public function verifyNotify($POST) {
        $notify_data = 'notify_data=' . $POST['notify_data'];
        $sign = $POST['sign'];
        try{
            $this->verifySign($sign, $notify_data);

        }catch (\Exception $e) {throw $e; }

        $notify_data = null;

        $buffer = $POST['notify_data'];
        \sprite\lib\Log::customLog ( 'notify_' . date ( 'Ymd' ) . '.log', 'verifyNotify|______|' . $buffer . "\n\n" );

        $xml = simplexml_load_string($buffer);
        $array = json_decode(json_encode((array) $xml), 1);

        $notify_data = array($xml->getName() => $array);

        if(!isset($notify_data["notify"]) || $notify_data['notify']['trade_status'] != 'TRADE_FINISHED')
            throw new \Exception('认证失败', 5000);

        return array(
            'order_sn'=>$notify_data['notify']['out_trade_no'],
            'out_trade_sn'=>$notify_data['notify']['trade_no'],
            'discount'=>$notify_data['notify']['discount'],
            'price'=>$notify_data['notify']['price'],
            'quantity'=>$notify_data['notify']['quantity'],
            'trade_status'=>$notify_data['notify']['trade_status'],
            'total_fee'=>$notify_data['notify']['total_fee'],
        );
    }

    /**
     *    将验证结果反馈给网关
     */
    public function verifyResult($result) {
		if($result) {
			return 'success';
		}
		else {
			return 'fail';
		}
	}

    public function getPayForm($order) {
        $strOrderInfo = "partner=" . "\"" . $this->_config['partner'] . "\"";
        $strOrderInfo .= "&";
        $strOrderInfo .= "seller=" . "\"" . $this->_config['seller'] . "\"";
        $strOrderInfo .= "&";
        $strOrderInfo .= "out_trade_no=" . "\"" . $this->getOutTradeNo() . "\"";
        $strOrderInfo .= "&";
        $strOrderInfo .= "subject=" . "\"" . $this->subject() . "\"";

        $strOrderInfo .= "&";
        $strOrderInfo .= "body=" + "\"\"";
        $strOrderInfo .= "&";
        $strOrderInfo .= "total_fee=" + "\"".$order['order_amount']."\"";
        $strOrderInfo .= "&";
        $strOrderInfo .= "notify_url=" + "\"".SITE_URL ."/index.php?_c=payment&_a=notify&type=appalipay\"";

        return $strOrderInfo;
    }


    /**
     *    获取签名字符串
     *
     */
    private function getSign($params) {
        try{ //读取私钥文件
            $priKey = file_get_contents(ROOT_PATH .'/app/service/payment/appalipay/key/rsa_private_key.pem');

            //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
            $res = openssl_get_privatekey($priKey);

            //调用openssl内置签名方法，生成签名$sign
            openssl_sign($params, $sign, $res);

            //释放资源
            openssl_free_key($res);

            //base64编码
            $sign = base64_encode($sign);

            return $sign;
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

    /**
     *    验证签名是否可信
     */
    private function verifySign($sign, $notify_data) {
        try{
            //读取支付宝公钥文件
            $pubKey = file_get_contents(ROOT_PATH .'/app/service/payment/appalipay/key/alipay_public_key.pem');

            //转换为openssl格式密钥
            $res = openssl_get_publickey($pubKey);

            //调用openssl内置方法验签，返回bool值
            $result = (bool)openssl_verify($notify_data, base64_decode($sign), $res);

            //释放资源
            openssl_free_key($res);

            return $result;
        }
        catch(\Exception $e) {
            throw new \Exception('认证失败', 5000);
        }
    }



}