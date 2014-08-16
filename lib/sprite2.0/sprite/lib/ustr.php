<?php
namespace sprite\lib;

/**
 * @author liweiwei
 * string 辅助类
 *
 */
class UString {

	/**
	 * @param string $str 输入字符串
	 * @param int $length 长度
	 * @param string $encoding 字符编码
	 * @return boolean
	 */
	public static function cut(&$str, $length, $encoding='utf-8') {
		if (mb_strlen($str, $encoding) > $length) {
			$str = mb_substr($str, 0, $length, $encoding);
			$str .= '...';
			return true;
		}		
		return false;
	}
}