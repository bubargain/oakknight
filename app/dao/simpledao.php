<?php
namespace app\dao;

use sprite\db\SqlUtil;

class SimpleDao extends YmallDAO {
	
	public function getpdo() {
		return $this->_pdo;
	}
	
	public function getTableName(){
		throw new \Exception('不支持基本的dao方法');
	}
	
	public function getPKey(){
		throw new \Exception('不支持基本的dao方法');
	}
}