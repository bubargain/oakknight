<?php

/**
 * @author wanjilong@yoka.com
 * @desc
 */
namespace app\service\payment\alipay;

use \app\service\payment\BasePaymentSrv;

require_once (ROOT_PATH . "/lib/alipay/alipay_submit.class.php");
require_once (ROOT_PATH . "/lib/alipay/alipay_notify.class.php");
require_once (ROOT_PATH . "/lib/alipay/alipay_core.function.php");
require_once (ROOT_PATH . "/lib/alipay/alipay_rsa.function.php");
class AlipayPaymentSrv extends BasePaymentSrv {
	var $_gateway = '';
	/* 支付方式唯一标识 */
	var $_code = '';
	public function __construct($config) {
		parent::__construct ( $config );
		
		$this->_config ['partner'] = array (
				'partner' => '2088101989241025',
				'seller' => 'shopadmin@yoka.com' 
		);
	}
	
	/**
	 * 验证支付结果,参照 支付宝 无线支付接口
	 */
	public function verifyNotify($_POST, $alipay_config) {
		
		// file_put_contents(LOG_PATH.'/aa.txt',json_encode($_POST));
		
		// $notify_data = 'notify_data=' . $POST['notify_data'];
		// $notify_data = null;
		// $alipayNotify = new \AlipayNotify($alipay_config);
		// $verify_result = $alipayNotify->verifyNotify();
		// file_put_contents(LOG_PATH.'/aa.txt',"\nSTART\n",FILE_APPEND);
		// file_put_contents(LOG_PATH.'/aa.txt',json_encode($_POST),FILE_APPEND);
		// die();
		// if($verify_result) {//验证成功
		// ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 请在这里加上商户的业务逻辑程序代
		
		// ——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
		
		// 解密（如果是RSA签名需要解密，如果是MD5签名则下面一行清注释掉）
		$notify_data = rsaDecrypt ( $_POST ['notify_data'], $alipay_config ['private_key_path'] );
		
		// file_put_contents(LOG_PATH.'/aa.txt',$notify_data."\n",FILE_APPEND);
		// var_dump($notify_data);die();
		// 获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
		
		// 解析notify_data
		// 注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
		$doc = new \DOMDocument ();
		$doc->loadXML ( $notify_data );
		
		\sprite\lib\Log::customLog ( 'notify_' . date ( 'Ymd' ) . '.log', 'verifyNotify|______|' . $notify_data . "\n\n" );
		
		if (! empty ( $doc->getElementsByTagName ( "notify" )->item ( 0 )->nodeValue )) {
			// 商户订单号
			$out_trade_no = $doc->getElementsByTagName ( "out_trade_no" )->item ( 0 )->nodeValue;
			// 支付宝交易号
			$trade_no = $doc->getElementsByTagName ( "trade_no" )->item ( 0 )->nodeValue;
			// 交易状态
			$trade_status = $doc->getElementsByTagName ( "trade_status" )->item ( 0 )->nodeValue;
			
			$ret = Array ();
			$ret ['order_sn'] = $out_trade_no;
			$ret ['out_trade_sn'] = $trade_no;
			$ret ['paymemt_name'] = $this->_config ['paymemt_name'];
			$ret ['paymemt_code'] = $this->_config ['paymemt_code'];
			$ret ['total_fee'] = $doc->getElementsByTagName ( "total_fee" )->item ( 0 )->nodeValue;
			// $ret['total_fee'] = 561.0;
			// file_put_contents(LOG_PATH.'/aa.txt',$ret."\n",FILE_APPEND);
			
			// file_put_contents(LOG_PATH.'/aa.txt',json_encode($ret),FILE_APPEND);
			if ($trade_status == 'TRADE_FINISHED') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				
				// 注意：
				// 该种交易状态只在两种情况下出现
				// 1、开通了普通即时到账，买家付款成功后。
				// 2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。
				
				// 调试用，写文本函数记录程序运行情况是否正常
				// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
				
				// file_put_contents(LOG_PATH.'/aa.txt',$ret,FILE_APPEND);
				
				$orderSrc = new \app\service\OrderSrv (); // 更改状态
				$orderSrc->pay ( $ret );
				// file_put_contents(LOG_PATH.'/aa.txt',"END\n",FILE_APPEND);
				return "success"; // 请不要修改或删除
			} else if ($trade_status == 'TRADE_SUCCESS') {
				// 判断该笔订单是否在商户网站中已经做过处理
				// 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
				// 如果有做过处理，不执行商户的业务程序
				
				// 注意：
				// 该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。
				
				// 调试用，写文本函数记录程序运行情况是否正常
				// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
				
				return "success"; // 请不要修改或删除
			}
		} 

		else {
			// 验证失败
			return "fail";
			
			// 调试用，写文本函数记录程序运行情况是否正常
			// logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
		}
		/*
		 * $buffer = decrypt($POST['notify_data']); $xml =
		 * simplexml_load_string($buffer); $array =
		 * json_decode(json_encode((array) $xml), 1); $notify_data =
		 * array($xml->getName() => $array); if(!isset($notify_data["notify"]))
		 * throw new \Exception('认证失败', 5000); return array(
		 * 'order_sn'=>$notify_data['out_trade_no'],
		 * 'discount'=>$notify_data['discount'], 'price'=>$notify_data['price'],
		 * 'quantity'=>$notify_data['quantity'],
		 * 'trade_status'=>$notify_data['trade_status'],
		 * 'total_fee'=>$notify_data['total_fee'], );
		 */
	}
	
	/**
	 * 将验证结果反馈给网关
	 */
	public function verifyResult($alipay_config) {
		$alipayNotify = new \AlipayNotify ( $alipay_config );
		$verify_result = $alipayNotify->verifyReturn ();
		if ($verify_result) {
			echo 'success';
		} else {
			echo 'fail';
		}
	}
	public function getPayForm($order, $alipay_config) {
		
		// 返回格式
		$format = "xml";
		// 必填，不需要修改
		
		// 返回格式
		$v = "2.0";
		// 必填，不需要修改
		
		// 请求号
		$req_id = date ( 'Ymdhis' );
		// 必填，须保证每次请求都是唯一
		
		// **req_data详细信息**
		
		if ($_SERVER ['SERVER_NAME'] == 'mobile.ymall.com' || $_SERVER ['SERVER_NAME'] == 'm.ymall.com' ) {
			// 服务器异步通知页面路径
			$notify_url = $_SERVER ['ROOT_DOMAIN'] . "/api/payment/webnotify";
			// 需http://格式的完整路径，不允许加?id=123这类自定义参数
			// 页面跳转同步通知页面路径
			// 需http://格式的完整路径，不允许加?id=123这类自定义参数
			$call_back_url = $_SERVER ['ROOT_DOMAIN'] . "/api/payment/webcallback";
		} else {
			// 服务器异步通知页面路径
			$notify_url = TOUCH_YMALL . "/api/payment/webnotify";
			// 需http://格式的完整路径，不允许加?id=123这类自定义参数
			// $call_back_url = 'touch.ymall.com'. "/api/payment/webcallback";
			$call_back_url = TOUCH_YMALL . "/api/payment/webcallback";
		}
		
		// 卖家支付宝帐户
		$seller_email = "shopadmin@yoka.com";
		// 必填
		
		// 商户订单号
		$out_trade_no = $order ['order_sn'];
		// 商户网站订单系统中唯一订单号，必填
		
		// 订单名称
		$subject = $this->getSubject ( $order );
		// 必填
		
		// 付款金额
		$total_fee = $order ['order_amount'];
		// 必填
		
		// 请求业务参数详细
		$req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee></direct_trade_create_req>';
		// 必填
		
		/**
		 * *********************************************************
		 */
		
		// 构造要请求的参数数组，无需改动
		$para_token = array (
				"service" => "alipay.wap.trade.create.direct",
				"partner" => trim ( $alipay_config ['partner'] ),
				"sec_id" => trim ( $alipay_config ['sign_type'] ),
				"format" => $format,
				"v" => $v,
				"req_id" => $req_id,
				"req_data" => $req_data,
				"_input_charset" => trim ( strtolower ( $alipay_config ['input_charset'] ) ) 
		);
		
		// var_dump($para_token,$alipay_config);
		
		// 建立请求
		$alipaySubmit = new \AlipaySubmit ( $alipay_config );
		$html_text = $alipaySubmit->buildRequestHttp ( $para_token );
		
		// URLDECODE返回的信息
		$html_text = urldecode ( $html_text );
		// var_dump($html_text);
		// 解析远程模拟提交后返回的信息
		$para_html_text = $alipaySubmit->parseResponse ( $html_text );
		
		// 获取request_token
		$request_token = $para_html_text ['request_token'];
		
		// var_dump($request_token);die();
		
		/**
		 * ************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute*************************
		 */
		
		// 业务详细
		$req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
		// 必填
		
		// 构造要请求的参数数组，无需改动
		$parameter = array (
				"service" => "alipay.wap.auth.authAndExecute",
				"partner" => trim ( $alipay_config ['partner'] ),
				"sec_id" => trim ( $alipay_config ['sign_type'] ),
				"format" => $format,
				"v" => $v,
				"req_id" => $req_id,
				"req_data" => $req_data,
				"_input_charset" => trim ( strtolower ( $alipay_config ['input_charset'] ) ) 
		);
		
		// 建立请求
		$alipaySubmit = new \AlipaySubmit ( $alipay_config );
		$html_text = $alipaySubmit->buildRequestForm ( $parameter, 'get', '确认' );
		// echo $call_back_url;die();
		return $html_text;
	}
}