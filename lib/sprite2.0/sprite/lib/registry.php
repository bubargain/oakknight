<?php
namespace sprite\lib;

/**
 * 注册类，可用于存取全局信息
 *
 */
class Registry {

	private static $_table; //存放全局信息的变量
	
	private function __construct() {}	
	
	public static function get($key) {
		return isset(self::$_table[$key])?self::$_table[$key]:'';
	}
	
	public static function set($key, $value) {
		if (!isset(self::$_table[$key]))
			self::$_table[$key] = $value;
	}

}
?>
