<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class SearchAliaDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_search_alia';
	}
	public function getPKey() {
		return 'ukey';
	}
}