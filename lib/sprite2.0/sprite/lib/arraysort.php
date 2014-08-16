<?php
namespace sprite\lib;

/**
 * @author liweiwei
 * 数组排序
 *
 */
class ArraySort {
	
	/**
	 * 按数组里的一个字段排序
	 * @param array $array
	 * @param string $field
	 */
	public static function sortByField(array $array, $field) {
		$tmp = array();
		foreach ($array as $v) {
			$tmp[] = $v[$field];
		}
		array_multisort($array, $tmp);
	}
	
	/**
	 * 把数组的key替换成为数组里的一个字段的key
	 * @param array $array
	 * @param string $field
	 * @return array 
	 */
	public static function indexByField(array $array, $field) {
		$out = array();
		foreach ($array as $v) {
			$out[$v[$field]] = $v;
		}
		return $out;
	}
	
	/**
	 * 把数组的key替换成为数组里的一个字段
	 * @param array $array
	 * @param string $field
	 * @return array 
	 */
	public static function indexByVar(array $array, $var) {
		$out = array();
		foreach ($array as $v) {
			$out[(string)$v->$var] = $v;
		}
		return $out;
	}
}