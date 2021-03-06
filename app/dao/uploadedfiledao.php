<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class UploadedFileDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_uploaded_file';
	}
	public function getPKey() {
		return 'file_id';
	}
}