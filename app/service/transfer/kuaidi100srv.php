<?php

namespace app\service\transfer;

use app\service\BaseSrv;

/**
 *
 *
 * Check order transfer info with public api
 * 
 * @author daniel
 *        
 */
class Kuaidi100Srv extends BaseSrv {
	private $kuaidi100key;
	private $callbackurl;
	public function __construct() {
		$this->kuaidi100key = "UtPNSIYh699";
		$this->callbackurl = $_SERVER ['APP_SITE_URL'] . "/api/transfer/callback";
	}
	
	/**
	 *
	 *
	 * 统一转义常见的快递公司中文名称
	 * 
	 * @param string $name        	
	 * @author :daniel ma
	 */
	public static function formatShippingName($name) {
		Switch ($name) {
			case "圆通快递" :
			case "圆通" :
				return "yuantong";
			case "顺丰快递" :
			case "顺丰" :
				return "shunfeng";
			case "申通" :
			case "申通快递" :
				return "shentong";
			default :
				return $name;
		}
	}
	
	/**
	 * 订阅服务
	 * 有新订单时，需要主动告知快递100
	 */
	public function postOrder($orderInfo) {
		try {
			$post_data = array ();
			$post_data ["schema"] = 'json';
			
			if (! isset ( $orderInfo ['from_region'] ))
				$orderInfo ['from_region'] = "北京朝阳";
			
			$orderInfo ["region_name"] = str_replace ( " ", '', $orderInfo ["region_name"] );
			$orderInfo ["region_name"] = str_replace ( "\t", '', $orderInfo ["region_name"] );
			
			// var_dump($this->kuaidi100key);
			
			// callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
			$post_data ["param"] = "{'company':'" . $this::formatShippingName ( $orderInfo ["shipping_name"] ) . "','number':'" . $orderInfo ["shipping_code"] . "','from':'" . $orderInfo ["from_region"] . "','to':'" . $orderInfo ["region_name"] . "','key':'" . $this->kuaidi100key . "','parameters':{'callbackurl':'" . $this->callbackurl . "'}}";
			
			$url = 'http://www.kuaidi100.com/poll';
			// var_dump($post_data);
			$o = "";
			foreach ( $post_data as $k => $v ) {
				$o .= "$k=" . urlencode ( $v ) . "&"; // 默认UTF-8编码格式
			}
			
			$post_data = substr ( $o, 0, - 1 );
			
			$ch = curl_init ();
			curl_setopt ( $ch, CURLOPT_POST, 1 );
			curl_setopt ( $ch, CURLOPT_HEADER, 0 );
			curl_setopt ( $ch, CURLOPT_URL, $url );
			curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
			$result = curl_exec ( $ch ); // 返回提交结果，格式与指定的格式一致（result=true代表成功）
				                          // var_dump($result);
		} catch ( Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	/**
	 *
	 *
	 * 从内部数据库读取物流信息
	 * 
	 * @param string $shipping_code
	 *        	:物流单号
	 * @param strings $shipping_name
	 *        	：物流公司名称
	 */
	public function innerQuery($shipping_code, $shipping_name) {
		$sql = "select content from ym_transfer_info where shipping_code ='" . $shipping_code . "' and shipping_name ='" . self::formatShippingName ( $shipping_name ) . "'";
		// echo $sql;
		$rs = \app\dao\UserDao::getSlaveInstance ()->getpdo ()->getRow ( $sql );
		if ($rs ['content'])
			return $rs ['content'];
		else {
			return '{"message":"ok","state":"0","status":"0"}'; // 如果没查到，返回100状态
		}
	}
	
	/**
	 *
	 *
	 * query transfer info 直接从网站读取
	 * 
	 * @param string $order_sn
	 *        	:
	 * @param string $com
	 *        	: company name
	 */
	public function query($order_sn, $com, $id = 'f90958e0663bed58') {
		/*
		 * $baseUrl ="http://api.kuaidi100.com/api"; $fullUrl = $baseUrl .
		 * "?id=" . $id . "&com=" . $com . "&nu=" . $order_sn ."&show=0";
		 */
		$baseUrl = "http://www.kuaidi100.com/query";
		$fullUrl = $baseUrl . "?type=" . $com . "&postid=" . $order_sn;
		
		if (function_exists ( 'curl_init' ) == 1) {
			
			$curl = curl_init ();
			
			curl_setopt ( $curl, CURLOPT_URL, $fullUrl );
			
			curl_setopt ( $curl, CURLOPT_HEADER, 0 );
			
			curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
			
			// curl_setopt ($curl,
			// CURLOPT_USERAGENT,$_SERVER['HTTP_USER_AGENT']);
			
			curl_setopt ( $curl, CURLOPT_TIMEOUT, 25 );
			
			$get_content = curl_exec ( $curl );
			// echo $get_content;
			
			curl_close ( $curl );
			
			if ($get_content)
				return $get_content; // 返回JSON 字符串
			else {
				throw new \Exception ( 'Didnt get response from Kuaidi100 server', 400 );
			}
		} else {
			return "not curl installed on server";
		}
	}
}