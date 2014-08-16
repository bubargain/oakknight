<?php
namespace sprite\db;

use \sprite\lib\Config;
use \sprite\lib\Roll;
use \PDOException;

class PDOManager{
	private static $_pdomap = array();
	
	//singleton connection 
	public static function getConnect($cfgName){
		$conn = '';
		if (isset(self::$_pdomap[$cfgName]))
			$conn = self::$_pdomap[$cfgName];
		
		if (empty($conn) || !self::isValid($conn))
			self::$_pdomap[$cfgName]  = self::getNewConnect($cfgName);
		
		return self::$_pdomap[$cfgName];
	}
	
	
	/**
	 * 根据配置段取一个数据库链接
	 * @param string $sectionName
	 * @return \sprite\db\PDOext
	 */
	public static function getNewConnect($sectionName) {
		$cfg = $_SERVER[$sectionName];

		if ( is_array($cfg) && !isset($cfg['dsn']) ) { //判断是不是要按权重选择的配置 
			$cfg = Roll::select($cfg);
		}

		return new PDOext($cfg['dsn'], $cfg['user'], $cfg['password']);
	}
	
	
	public static function isValid($conn) {
		try {
			$conn->ping();
		} catch (PDOException $e) {
			return false;
		}
		
		return true;
	}
	
}