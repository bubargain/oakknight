<?php
namespace sprite\lib;

use \Exception;

/**
 * 按权重随机取元素
 *
 */
class Roll {
	
	/**
	 * 按权重随机选择数组中的一个元素，数组中每个元素含权重列
	 * @param array $ary 随机选择的数组
	 * @param string $key 指明数组元素中权重列名，默认为weight
	 * @return array $ary[$k]
	 */
	public static function select(array $ary, $key='weight') {
		$weight = array();
		foreach ($ary as $k=>$v) {
			if (isset($v[$key]))
				$weight[$k] = $v[$key];
		}
		
		$k = self::rollKey($weight);
		return $ary[$k];
	}
	
	/**
	 * 给定一个数组，value是权重，按权重随机返回key
	 * @param array $weight
	 * @return roll key
	 */
	private static function rollKey(array $weight) {
		$roll = rand(1, array_sum($weight));
	
		$tmpW = 0;
		foreach ($weight as $k=>$v) {
			$min = $tmpW;
			$tmpW += $v;
			$max = $tmpW;
			if ($roll > $min && $roll <= $max) {
				return $k;
			}
		}
		throw new Exception('权重选择出错');
	}	
	
}