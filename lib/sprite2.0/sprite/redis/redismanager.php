<?php
namespace sprite\redis;

use \sprite\lib\Config;
use \sprite\lib\Roll;
use \sprite\redis\Redisext;

class RedisManager{
	private static $_map = array();
	
	//singleton connection 
	public static function getConnect($cfgName){
		$conn = '';

        self::$_map[$cfgName]  = self::getNewConnect($cfgName);
        /*
		if (isset(self::$_map[$cfgName]))
			$conn = self::$_map[$cfgName];
		var_dump('===========', $conn);
		if (empty($conn) || !self::isValid($conn))
			self::$_map[$cfgName]  = self::getNewConnect($cfgName);
		*/
		return self::$_map[$cfgName];
	}
	
	
	/**
	 * 根据配置段取一个数据库链接
	 * @param string $sectionName
	 * @return \sprite\db\PDOext
	 */
	public static function getNewConnect($sectionName) {
		$cfg = $_SERVER[$sectionName];

		if ( is_array($cfg) && !isset($cfg['host']) ) { //判断是不是要按权重选择的配置
			$cfg = Roll::select($cfg);
		}

		return new Redisext($cfg['host'], $cfg['port'], $cfg['password']);
	}
	
	
	public static function isValid($conn) {
        try {
			$t = $conn->PING();
            var_dump($t); die('ok');
            if($t === false) {
                return false;
            }
		} catch (Exception $e) {
			return false;
		}
		
		return true;
	}

}