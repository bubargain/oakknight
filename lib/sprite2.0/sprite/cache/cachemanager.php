<?php
namespace sprite\cache;

use \sprite\cache\Memcacheext;

class CacheManager{
	private static $_map = array();

	//singleton connection 
	public static function getInstance($cfgName = 'default'){
		if(!isset(self::$_map[$cfgName]))
            self::$_map[$cfgName]  = self::getNewConnect($cfgName);

		return self::$_map[$cfgName];
	}
	
	
	/**
	 * 根据配置段取一个数据库链接
	 * @param string $sectionName
	 * @return \sprite\db\PDOext
	 */
	public static function getNewConnect($sectionName) {
		$cfg = $_SERVER['memcache'][$sectionName];
        if(!$cfg['server'])
            throw new \Exception('服务器配置错误', 500);

		$cacheObj = new Memcacheext();
        $cacheObj->addServers($cfg['server']);

		return $cacheObj;
	}
}