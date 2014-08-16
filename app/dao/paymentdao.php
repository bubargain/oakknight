<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class PaymentDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_payment';
	}
	public function getPKey() {
		return 'payment_id';
	}
}