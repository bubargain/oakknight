<?php
namespace app\dao;

use sprite\db\BaseDao;

abstract class YmallDao extends BaseDao {
	protected $usedMdb = false;

	public function getpdo($AUTO_SELECT_MDB_OR_SDB = false){
		if($AUTO_SELECT_MDB_OR_SDB){
			if($this->usedMdb){
				$this->switchToMdb();
				return $this->_pdo;
			}
		}
		return $this->_pdo;
	}
	
	
	
	public function switchToMdb(){
		parent::__construct($pdoconn_or_conntype=self::MASTER);
		return $this;
	}
	public function switchToSdb(){
		parent::__construct();
		return $this;
	}

	//@see config.ini
	protected function getMdbCfgName() {
		
		return 'mobile_mdb';
	}

	//@see config.ini
	protected  function getSdbCfgName() {
		
		return 'mobile_sdb';
	}
}