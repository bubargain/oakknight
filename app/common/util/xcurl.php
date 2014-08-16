<?php
namespace app\common\util;

/**
 * 一个可以取得cookie的curl
 * @author liweiwei@yoka.com
 * @date 2012-07-27
 */


class XCurl {
	const TIME_OUT = 15;
	private $response_info = null;

	public function __construct() {
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_TIMEOUT, self::TIME_OUT);
	}
	
	public function exec($url) {
		curl_setopt($this->ch, CURLOPT_URL, $url);
        $r = curl_exec($this->ch);
        $this->response_info = curl_getinfo($this->ch);
		return $r;
	}
	
	/**
	 * 
	 * @param [string] $url
	 */
	public function get_content($url) {
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$content = $this->exec($url);
		curl_close($this->ch);
		return $content;
	}
	
	/**
	 *
	 * @param [string] $url
	 */
	public function get_with_cookie($url) {
		$file = '/tmp/cookie-'. rand(0, 100) .'.txt';
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_COOKIEFILE, $file);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $file);
		
		$content = $this->exec($url);
		curl_close($this->ch);
		$cookie = file_get_contents($file);
		
		return array($content, $cookie);
	}
	
	
	/**
	 * 返回结果有cookie
	 * @param str $url
	 * @return [array[header, html]]
	 */
	public function get_with_header($url) {
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_HEADER, 1);
		$content = $this->exec($url);
		curl_close($this->ch);
		return explode("\r\n\r\n", $content, 2); 
	}
	
	public function set_option($option, $value) {
		curl_setopt($this->ch, $option, $value);
	}
	
	public function set_options(array $options) {
		curl_setopt_array($this->ch, $options);
	}
	
	/*
	 * 正确解析cookie比较复杂，需要pecl http_parse_headers
	 * 
	 */
	public static function get_cookie_string_from_header($header) {
		preg_match_all("/set\-cookie:([^\r\n]*)/i", $header, $m);
		if (empty($m[1]))
			return '';
		
		$cookies = array();
		
		foreach ($m[1] as $v) {
			$tmp = explode(';', $v, 2);
			$cookies[] = $tmp[0];
		}

		$cookieStr = implode(';', $cookies);
		
		return $cookieStr;
	}

    public function get_info(){
        return $this->response_info;
    }
}