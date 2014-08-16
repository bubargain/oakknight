<?php

namespace touch\controller;

use \app\service\OrderSrv;
use \app\dao\PaymentDao;
/*
 * product related behavior @author : daniel
 */
class paymentController extends BaseController {
	private $alipay_config = Array ();
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
		
		$this->alipay_config ['partner'] = '2088101989241025';
		
		// 安全检验码，以数字和字母组成的32位字符
		// 如果签名方式设置为“MD5”时，请设置该参数
		$this->alipay_config ['key'] = '1312145';
		
		// 商户的私钥（后缀是.pen）文件相对路径
		// 如果签名方式设置为“0001”时，请设置该参数
		$this->alipay_config ['private_key_path'] = ROOT_PATH . '/app/service/payment/alipay/ali-key/rsa_private_key.pem';
		
		// 支付宝公钥（后缀是.pen）文件相对路径
		// 如果签名方式设置为“0001”时，请设置该参数
		$this->alipay_config ['ali_public_key_path'] = ROOT_PATH . '/app/service/payment/alipay/ali-key/alipay_public_key.pem';
		
		// ↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
		
		// 签名方式 不需修改
		$this->alipay_config ['sign_type'] = "0001";
		
		// $alipay_config['sign_type'] ="RSA";
		
		// 字符编码格式 目前支持 gbk 或 utf-8
		$this->alipay_config ['input_charset'] = 'utf-8';
		
		// ca证书路径地址，用于curl中ssl校验
		// 请保证cacert.pem文件在当前文件夹目录中
		$this->alipay_config ['cacert'] = ROOT_PATH . '/app/service/payment/alipay/ali-key/cacert.pem';
		
		// 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
		$this->alipay_config ['transport'] = 'http';
	}
	
	/**
	 * 根据id显示订单支付完成提示页
	 *
	 * @param $request->id :
	 *        	string orderId
	 * @param
	 *        	$response
	 */
	public function payForm($request, $response) {
		try {
			$order = \app\dao\OrderDao::getSlaveInstance ()->find ( $request->id );
			if (! $order || $order ['order_status'] != OrderSrv::UNPAY_ORDER)
				throw new \Exception ( '不存在或不可支付状态', 5000 );
			
			$info = PaymentDao::getSlaveInstance ()->find ( array (
					'payment_code' => $request->type,
					'enabled' => 1 
			) );
			$payment = "\\app\\service\\payment\\" . $info ['payment_code'] . '\\' . $info ['payment_code'] . 'paymentSrv';
			
			if (! class_exists ( $payment ))
				throw new \Exception ( "no payment called $payment " );
			
			$paymentSrv = new $payment ( $info );
			
			$form = $paymentSrv->getPayForm ( $order, $this->alipay_config );
			// 统计埋点
			self::userLog ( array (
					'type' => 'touchpayment',
					'action' => 'payform',
					'item_id' => $request->id,
					'user_id' => $order ['buyer_id'] 
			) );
			echo $form;
		} catch ( \Exception $e ) {
			$this->renderString ( $e->getMessage () );
		}
	}
	
	/**
	 * 根据商品id及数量，用户信息生成订单
	 *
	 * @param
	 *        	$request
	 * @param
	 *        	$response
	 */
	public function webnotify($request, $response) {
		try {
			\sprite\lib\Log::customLog ( 'notify_' . date ( 'Ymd' ) . '.log', 'start|______|' . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'] . '|______|' . serialize ( $_POST ) . '|______|E_HTTP_CLIENT_IP=' . getenv ( 'HTTP_CLIENT_IP' ) . ',E_HTTP_X_FORWARDED_FOR=' . getenv ( 'HTTP_X_FORWARDED_FOR' ) . ',E_REMOTE_ADDR=' . getenv ( 'REMOTE_ADDR' ) . ',S_REMOTE_ADDR=' . $_SERVER ['REMOTE_ADDR'] . "\n\n" );
			
			$info = PaymentDao::getSlaveInstance ()->find ( array (
					'payment_code' => 'alipay',
					'enabled' => 1 
			) );
			$paymentSrv = new \app\service\payment\alipay\AlipayPaymentSrv ( $info );
			try {
				$ret = $paymentSrv->verifyNotify ( $_POST, $this->alipay_config ); // 通过校验
				\sprite\lib\Log::customLog ( 'notify_' . date ( 'Ymd' ) . '.log', 'end|______|' . $ret . "\n\n" );
                echo $ret;
			} catch ( \Exception $e ) {
				echo $e->getMessage ();
			}
		} catch ( \Exception $e ) {
			$this->renderString ( $e->getMessage () );
		}
	}
	public function webcallback($request, $response) {
        try {
            $orderSrv = new OrderSrv();
            $order = $orderSrv->info($request->out_trade_no, 'order_sn');

            $response->title = '成功购买';
            $response->order = $order;
            $this->layoutSmarty ( 'success' );
        } catch ( \Exception $e ) {
            $this->showError($e->getMessage ());
        }
	}
	public function userLog($info) {
		if (! isset ( $info ['user_id'] )) {
			$info ['user_id'] = $this->current_user ['user_id'] ? $this->current_user ['user_id'] : 0;
		}
		if (! isset ( $info ['uuid'] )) {
			$info ['uuid'] = isset ( $this->current_user ['clientid'] ) ? $this->current_user ['clientid'] : '';
		}
		parent::userLog ( $info );
	}
}
