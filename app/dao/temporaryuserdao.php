<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class TemporaryUserDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_temporary_user';
	}
	public function getPKey() {
		return 'user_id';
	}
}