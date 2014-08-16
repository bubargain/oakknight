<?php
namespace sprite\lib;

/**
 * @author liweiwei
 *
 */
class Cookie {

	private $_domain;
	private $_path;
	
	public function __construct($domain, $path) {
		$this->_domain = $domain;
		$this->_path = $path;
	}

	/**
	 * set cookie
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @param unknown_type $livetime
	 * @param unknown_type $path
	 * @param unknown_type $domain
	 * @return boolean
	 */
	public function set($key, $value, $livetime=604800, $path=null, $domain=null) {
		if ($path === null)
			$path = $this->_path;
		if ($domain === null)
			$domain = $this->_domain;
		if (@setcookie($key, $value, time() + $livetime, $path, $domain))
			$_COOKIE[$key] = $value;
		return true;		
	}

	/**
	 * @param unknown_type $key
	 * @param unknown_type $path
	 * @param unknown_type $domain
	 * @return boolean
	 */
	public function clear($key, $path=null, $domain=null) {
		if ($path === null)
			$path = $this->_path;
		if ($domain === null)
			$domain = $this->_domain;
		if (@setcookie($key, 1, 1, $path, $domain))
			unset($_COOKIE[$key]);
		return true;
	}
	
	/**
	 * 设置cookie域名
	 * @param string $domain
	 */
	public function setDomain($domain=null) {
		if ($domain)
			$this->_domain = $domain;
	}
}