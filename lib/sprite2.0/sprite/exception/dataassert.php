<?php
namespace sprite\exception;

/**
 * 
 * 对数据异常断言
 */
class DataAssert {

	
	/**
	 * Enter description here ...
	 * @param mixed $var 待断言数据
	 * @param $e 异常
	 * @throws BizException
	 */
	public static function assertNotEmpty($var, $e) {
		if (empty($var))
			throw $e;
	}
	
	public static function assertTrue($x, $e) {
		if (!$x)
			throw $e;
	}
	
	public static function assertFalse($x, $e) {
		if ($x)
			throw $e;
	}
	
	public static function assertNull($x, $e) {
		if ($x !== NULL)
			throw $e;
	}
	
	public static function assertNotNull($x, $e) {
		if ($x === NULL)
			throw $e;
	}
	
	public static function assertIsA($x, $y, $e) {
		if ($x !== $y)
			throw $e;
	}
	
	public static function assertNotA($x, $y, $e) {
		if ($x === $y)
			throw $e;
	}
	
	public static function assertEqual($x, $y, $e) {
		if ($x != $y)
			throw $e;
	}
	
	public static function assertNotEqual($x, $y, $e) {
		if ($x == $y)
			throw $e;
	}
		
}