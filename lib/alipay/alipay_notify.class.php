<?php
/* *
 * 类名：AlipayNotify
 * 功能：支付宝通知处理类
 * 详细：处理支付宝各接口通知返回
 * 版本：3.2
 * 日期：2011-03-25
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考

 *************************注意*************************
 * 调试通知返回时，可查看或改写log日志的写入TXT里的数据，来检查通知返回是否正常
 */

require_once("alipay_core.function.php");
require_once("alipay_rsa.function.php");
require_once("alipay_md5.function.php");

class AlipayNotify {
    /**
     * HTTPS形式消息验证地址
     */
	var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
	/**
     * HTTP形式消息验证地址
     */
	var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
	var $alipay_config;

	function __construct($alipay_config){
		$this->alipay_config = $alipay_config;
	}
    function AlipayNotify($alipay_config) {
    	$this->__construct($alipay_config);
    }
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyNotify(){
		//$data='{"service":"alipay.wap.trade.create.direct","sign":"Z\/40ekdBFz5cCKwitn6N4HT4hY+q1mN\/HtRjpAEDJoIKsotB5yK3X0TnMqC4YkfXgVtGm6rUpGXRL7Ur7TgiKdFfyd6GOTj02FDJqaOPAnTjIbkI6eYxvFkrtQyPLsl2hCPeM7ZgqGhjXWpsRqnhxNha2RlGICiPsMOSd4leiYI=","sec_id":"0001","v":"1.0","notify_data":"ib9H8vEDjLr0h+c7+XpHgNK4TScqTn4++aarHQMCx4HbOQjpYV2Lk5cvpdPZZ\/Qy0E2b9XeMMNHTL7K0RiKvz+wzri58BWUwBNvekYyX0oxVG+2ZjJArcX8\/o9L6fB0dMf2jhv00w7RqEVNfuYdbbEWg5f54fc8ILzsvtbs99epaULrTC+V8wPIIztunt1W8qaV7gCZ5TLChn56nRbAcMPY0YOpnjZ6Z7RblslnsIoenQyy3vdASYTqNCPrioqYuVAHydLxg\/wSD8JcR8wrVAOHVkFzotoyqCiUFaxiWUMrTiuN02AIsfkX0zQYUVoihMiqbGkmEqLG726JbSGzUdD+Edrme\/sTOS\/sZOErGbks8t2luS3PtR58+MPHzxVJXKMDZrbQbjzuG9TWOCC9l91k+M2E7Vv5xyFgqv7sL\/BhcEysyFOiWTTk2IW9X5HoAtah9p0ZQy7ZCtt2FtZgCOdgkpIaXYTD0KT\/WmirM0MgCerLyKvm7xa0KQns1nnJOoAEtx4YlzE1ID\/wY0Qz6zTQMZFjK22wr68uzrKHmKZMFbN\/5iwrV45hi32\/rVpxg84E20q1V+a5pjZPk025pNIHWf73br\/Ny6623l+v3qgEkAp9ylUNTqf9xlvjVXa2asAt34ICh8I\/7VgX1Cg+jztGiaPPQT8aCCZiO9TQtfTV+AYbXiifYXxDr7AHbsW3+8tGy9qbULOqAQtJJrbII\/uObfLJThhjhQjbcea0Q2CFhJAr800ZXabuu\/avG8q35HLi1PTIOHg2sN\/tzmXL2O4\/UOWUvQYh0pu5INiwfcIp+6l9RsNjkT2cVeQS1WNQVnrx0hYCjiEPszLSDNdej75WiHUASCONfRQ5XIQ9Cg2BmHwi6\/epbivyWxA2uYOOFl8qvYHpojWuuCxKE1c0wRX3CbW0bZR6vdDwE7HrzSiaF\/Jqm0IjJFIuJcHxaFgJhn4n\/RoKkEvhxQ8u5Fi\/RYUasN4bOddUjciIf+uKjxpJg5XnR+ek\/qoiXxYZzAdqnh7AilMMA+JS7wERx7zMe01JdvB2uNP1HGGCTwB2cwBSmHKwBijVhoCNF0hNTk4TZwmXZfE2uG19+0vMSeonq\/ZE22KGtXhAQQOhR6PWV7r8XzY8gZlyhsTM5rCQFXqFyEQ52yA5gfQRRbEF\/88knVUa4OboR0u2FG6lW091ULHWLX2fdknFYHgVgXjWGFz0V\/z260\/z\/5vts\/Se5h+6SnNc7KPxcpMGPAAj64lkqAHEY6U2a\/1c8taSEgCuOgXyoJvhtQnmBG7Ds\/IbafTAxA00uv11iYOPrY+FHyGMwe4NK6g8sUUtDukGRAjzlwDeMZO2hrJxf1Bi1QNXWZLVgBw=="}';
	    //$_POST=json_decode($data,true);
		
		if(empty($_POST)) {//判断POST来的数组是否为空
			return false;
		}
		else {
			
			//对notify_data解密
			$decrypt_post_para = $_POST;
			if ($this->alipay_config['sign_type'] == '0001') {
				$decrypt_post_para['notify_data'] = rsaDecrypt($_POST['notify_data'], $this->alipay_config['private_key_path']);
				//file_put_contents(LOG_PATH.'/aa.txt',"\n".$decrypt_post_para['notify_data']."\n",FILE_APPEND);
			}
			
			//notify_id从decrypt_post_para中解析出来（也就是说decrypt_post_para中已经包含notify_id的内容）
			$doc = new DOMDocument();
			$doc->loadXML($decrypt_post_para['notify_data']);
			$notify_id = $doc->getElementsByTagName( "notify_id" )->item(0)->nodeValue;
			
			//获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
			$responseTxt = 'true';
			if (! empty($notify_id)) {$responseTxt = $this->getResponse($notify_id);}
			
			//生成签名结果
			$isSign = $this->getSignVeryfy($decrypt_post_para, $_POST["sign"],false);
			
			var_dump($isSign,$responseTxt);
			//file_put_contents(LOG_PATH.'/aa.txt',"\n".$isSign."  : ".$responseTxt  ."\n",FILE_APPEND);
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_POST);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if (preg_match("/true$/i",$responseTxt) && $isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
	function verifyReturn(){
		if(empty($_GET)) {//判断GET来的数组是否为空
			return false;
		}
		else {
			//生成签名结果
			$isSign = $this->getSignVeryfy($_GET, $_GET["sign"],true);
			
			//写日志记录
			//if ($isSign) {
			//	$isSignStr = 'true';
			//}
			//else {
			//	$isSignStr = 'false';
			//}
			//$log_text = "return_url_log:isSign=".$isSignStr.",";
			//$log_text = $log_text.createLinkString($_GET);
			//logResult($log_text);
			
			//验证
			//$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
			//isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
			if ($isSign) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	/**
     * 解密
     * @param $input_para 要解密数据
     * @return 解密后结果
     */
	function decrypt($prestr) {
		return rsaDecrypt($prestr, trim($this->alipay_config['private_key_path']));
	}
	
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
	function getSignVeryfy($para_temp, $sign, $isSort) {
		
		
		//var_dump($para_temp,$sign);
		
		//除去待签名参数数组中的空值和签名参数
		$para = paraFilter($para_temp);
		
		if($isSort) {
			//对待签名参数数组排序
			$para = argSort($para);
		}
		
		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$prestr = createLinkstring($para);
		
		//var_dump($sign);
		
		$isSgin = false;
		switch (strtoupper(trim($this->alipay_config['sign_type']))) {
			case "MD5" :
				$isSgin = md5Verify($prestr, $sign, $this->alipay_config['key']);
				break;
			case "RSA" :
				$isSgin = rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
				break;
			case "0001" :
				$isSgin = rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
				break;
			default :
				$isSgin = false;
		}
		
		return $isSgin;
	}

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
	function getResponse($notify_id) {
		$transport = strtolower(trim($this->alipay_config['transport']));
		$partner = trim($this->alipay_config['partner']);
		$veryfy_url = '';
		if($transport == 'https') {
			$veryfy_url = $this->https_verify_url;
		}
		else {
			$veryfy_url = $this->http_verify_url;
		}
		$veryfy_url = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
		$responseTxt = getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);
		
		return $responseTxt;
	}
}
?>
