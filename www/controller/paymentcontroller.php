<?php 
namespace www\controller;

use \app\service\GoodsSrv;
use \app\service\OrderSrv;
use \app\dao\PaymentDao;
use \app\common\util\MobileMessage;
/*
 * product related behavior
 * @author : daniel
 */
class PaymentController extends AppBaseController
{
	private $alipay_config=Array();

	
    public function __construct($request, $response) {
        parent::__construct($request, $response);
        
        $this->alipay_config['partner']		= '2088101989241025';

		//安全检验码，以数字和字母组成的32位字符
		//如果签名方式设置为“MD5”时，请设置该参数
		 $this->alipay_config['key']			= '1312145';
		
		//商户的私钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		 $this->alipay_config['private_key_path']	= ROOT_PATH.'/app/service/payment/alipay/ali-key/rsa_private_key.pem';
		
		//支付宝公钥（后缀是.pen）文件相对路径
		//如果签名方式设置为“0001”时，请设置该参数
		 $this->alipay_config['ali_public_key_path']= ROOT_PATH.'/app/service/payment/alipay/ali-key/alipay_public_key.pem';
	
		//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
		
		
		//签名方式 不需修改
		 $this->alipay_config['sign_type']    = "0001";
		 
		
		//$alipay_config['sign_type'] ="RSA";
		
		//字符编码格式 目前支持 gbk 或 utf-8
		 $this->alipay_config['input_charset']= 'utf-8';
		
		//ca证书路径地址，用于curl中ssl校验
		//请保证cacert.pem文件在当前文件夹目录中
		 $this->alipay_config['cacert']    = ROOT_PATH.'/app/service/payment/alipay/ali-key/cacert.pem';
		
		//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		 $this->alipay_config['transport']    = 'http';
    	
    }

    /**
     * @param $request
     * @param $response
     * @desc 根据id显示订单支付完成提示页
     */
    public function payForm($request, $response) {
        try{ //&uid=%@&accesstoken=%@"
            //校验登录状态，web 需要传递过来
            if(!$request->uid || !$request->accesstoken)
                throw new \Exception('您的礼物店账号已在另一设备上成功登录，请注意账号安全。【YMALL礼物店】', 5000);

            $user_info = \app\dao\UserInfoDao::getSlaveInstance()->find($request->uid);
            if(!$user_info || $user_info['token'] != $request->accesstoken )
                throw new \Exception('您的礼物店账号已在另一设备上成功登录，请注意账号安全。【YMALL礼物店】', 5000);

            $order = \app\dao\OrderDao::getSlaveInstance()->find($request->id);
            if(!$order || $order['order_status'] != OrderSrv::UNPAY_ORDER)
                throw new \Exception('不存在或不可支付状态', 5000);

            $info = PaymentDao::getSlaveInstance()->find(array('payment_code'=>$request->type, 'enabled'=>1));
            $payment = "\\app\\service\\payment\\". $info['payment_code'].'\\'.$info['payment_code'].'paymentSrv';

            if (!class_exists($payment))
                throw new \Exception("no payment called $payment ");

            $paymentSrv = new $payment($info);


            $form = $paymentSrv->getPayForm($order,$this->alipay_config);

            //统计埋点
            self::userLog( array('type'=>'payment', 'action'=> 'payform', 'item_id'=>$request->id, 'user_id'=>$order['buyer_id']));

            echo $form;
           
        }
        catch(\Exception $e) { $this->renderString($e->getMessage()); }
    }

    /**
     * @param $request： $type 支付类型 ;$order_sn
     * @param $response
     * @desc 支付宝异步通知订单状态接口
     */
    public function webnotify($request, $response) {
        try{
            \sprite\lib\Log::customLog(
                'notify_'.date('Ymd').'.log',
                'start|______|'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'|______|'.serialize($_POST).'|______|E_HTTP_CLIENT_IP='.getenv('HTTP_CLIENT_IP').',E_HTTP_X_FORWARDED_FOR='.getenv('HTTP_X_FORWARDED_FOR').',E_REMOTE_ADDR='.getenv('REMOTE_ADDR').',S_REMOTE_ADDR='.$_SERVER['REMOTE_ADDR']."\n\n"
            );

            if($request->type == 'appalipay' ) //手机支付
		    {
		   	      $info = PaymentDao::getSlaveInstance()->find(array('payment_code'=>'appalipay', 'enabled'=>1));
	          	  $payment = "\\app\\service\\payment\\". $info['payment_code'].'\\'.$info['payment_code'].'paymentSrv';

                if (!class_exists($payment))
                    throw new \Exception("no payment called $payment ");
	
                $paymentSrv = new $payment($info);
	
                try{
                    $ret = $paymentSrv->verifyNotify($_POST);//通过校验
                    try{//可能存在多次支付
                        $orderSrc = new OrderSrv();//更改状态
                        $orderSrc->pay($ret);
                    }catch(\Exception $e) {
                        //发送短息提醒运营人员，可能出现异常
                        try{
                            $mobileMessage = new MobileMessage();
                            $msg = '支付宝二次支付通知，请确认订单状态 sn:'.$ret['order_sn'] . '【YMALL礼物店】';
                            $mobileMessage->send('15901159157', $msg);
                            //$mobileMessage->send('18610485690', $msg);
                        }catch(\Exception $e){}

                        \sprite\lib\Log::customLog(
                            'notify_'.date('Ymd').'.log',
                            'pay|___pay-error___| code: '.$e->getCode().', message: '.$e->getMessage()."\n\n"
                        );
                    }

                    $result = $paymentSrv->verifyResult(true);
                    echo $result;
                    \sprite\lib\Log::customLog(
                        'notify_'.date('Ymd').'.log',
                        'end|______|'.$result."\n\n"
                    );
                }
                catch(\Exception $e) {
                    $result = $paymentSrv->verifyResult(false);
                    //echo $result;
                    \sprite\lib\Log::customLog(
                        'notify_'.date('Ymd').'.log',
                        'end|___exception___| code: '.$e->getCode().', message: '.$e->getMessage()."\n\n"
                    );
                }
		    }
		    else
		    {
	           	 $info = PaymentDao::getSlaveInstance()->find(array('payment_code'=>'alipay', 'enabled'=>1));
	          	  $paymentSrv = new \app\service\payment\alipay\AlipayPaymentSrv($info);
	          	  try{
	
	          	      $ret = $paymentSrv->verifyNotify($_POST,$this->alipay_config);//通过校验
	          	      \sprite\lib\Log::customLog(
	                    'notify_'.date('Ymd').'.log',
	                    'end|______|'.$ret."\n\n"
	           	     );
	            	    echo $ret;
	           	 }
	           	 catch(\Exception $e) {
	               		 \sprite\lib\Log::customLog(
	                  	  'notify_'.date('Ymd').'.log',
                          'end|___exception___| code: '.$e->getCode().', message: '.$e->getMessage()."\n\n"
	               		 );
	                //echo $e->getMessage();
	           	 }
		    }
        }
        catch(\Exception $e) { $this->renderString($e->getMessage()); }
    }

    
    
    public function webcallback($request, $response){

    	echo $request->result;
    		//$alipay= new \app\service\payment\alipay\AlipayPaymentSrv();
    		//echo $alipay->verifyResult($this->alipay_config);
    	
	}

}
