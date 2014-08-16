<?php
namespace sprite\mvc;

use \Exception;

/**
 * @author liweiwei
 * http request
 *
 */
class Request {
	
	private static $_instance;
	private $allowModify = false;
	private static $getfilter="'|\\b(and|or)\\b.+?(>|<|=|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	private static $postfilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	private static $cookiefilter="\\b(and|or)\\b.{1,6}?(=|>|<|\\bin\\b|\\blike\\b)|\\/\\*.+?\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT|UPDATE.+?SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE).+?FROM|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
	const REPLACEMENT = '';
	
	private function __construct() {}
	
	/**
	 * 单例
	 * @return \sprite\mvc\Request
	 */
	public static function getInstance() {
		if (!self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * 返回 $_GET[$index] | default
	 * @param string $index
	 * @param string $default 没有取到的时候的默认值
	 * @return unknown
	 */
	public function get($index, $default='') {
		if (isset($_GET[$index])) {
			return preg_replace("/".self::$getfilter."/is", self::REPLACEMENT, $_GET[$index]);
		} else {
			return $default;
		}
	}

	/**
	 * 返回 $_POST[$index] | default
	 * @param string $index
	 * @param string $default 没有取到的时候的默认值
	 * @return unknown
	 */
	public function post($index, $default='') {
		if (isset($_POST[$index])) {
			return preg_replace("/".self::$postfilter."/is", self::REPLACEMENT, $_POST[$index]);
		} else {
			return $default;
		}
	}
	
	/**
	 * 返回 $_REQUEST[$index] | default
	 * @param string $index
	 * @param string $default 没有取到的时候的默认值
	 * @return unknown
	 */
	public function getRequest($index, $default='') {
      if (isset($_REQUEST[$index])) {
        $tmp = preg_replace("/".self::$getfilter."/is", self::REPLACEMENT, $_REQUEST[$index]);
        return preg_replace("/".self::$postfilter."/is", self::REPLACEMENT, $tmp);
      } else {
        return $default;
      }
	}
	
	/**
	 * $_COOKIE[$index]
	 * @param string $index
	 * @return Ambigous <string, unknown>
	 */
	public function cookie($index, $default = '') {
		if (isset($_COOKIE[$index])) {
			return preg_replace("/".self::$cookiefilter."/is", self::REPLACEMENT, $_COOKIE[$index]);
		} else {
			return $default;
		}
	}
	
	/**
	 * 设置$_GET可以被修改，特殊情况下使用
	 * @param array $get
	 */
	public function ModifyGet(array $get) {
		$this->allowModify = true;
		
		foreach ($get as $k=>$v)
			$this->$k = $v;
		
		$this->allowModify = false;
	}
	
	/**
	 * 用对象属性方式直接调用
	 * @param string $key
	 * @return Ambigous <\sprite\mvc\unknown, unknown, string>
	 */
	public function __get($key) {
		return $this->getRequest($key);
	}

	/**
	 * 用对象属性方式直接调用
	 * @param string $k key
	 * * @param string $v value
	 * @return Ambigous <\sprite\mvc\unknown, unknown, string>
	 */
	public function __set($k, $v) {
		if ($this->allowModify)
			$this->$k = $v;
		else
			throw new Exception('set value to const!');
		
	}

    public function setArgv($query = '') {
        parse_str($query, $_argv);
        if(!$_argv)
            return false;

        $this->allowModify = true;
        foreach($_argv as $k=>$v) {
            $this->$k = $v;
        }
    }
}
