<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class SettingDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_setting';
	}
	public function getPKey() {
		return 'ukey';
	}
	public function save($params, $key = '') {
		if ($key) {
			return $this->edit ( $key, $params );
		} else {
			try {
				$this->add ( array_merge ( $params, array (
						'ctime' => time () 
				) ) );
			} catch ( \Exception $e ) {
				return false;
			}
			return true;
		}
	}
}