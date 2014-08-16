<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class SearchLogDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_search_log';
	}
	public function getPKey() {
		return 'id';
	}
	
	/**
	 * 取得top 100 统计日志
	 * 
	 * @param
	 *        	$params
	 * @param int $top        	
	 */
	public function getSearchTop($params, $top = 100) {
		$sql = "select count(*) as num, `keyword` from " . self::getTableName () . " where 1=1";
		
		if ($params ['start_time']) {
			$sql .= ' and ctime>=' . $params ['start_time'];
		}
		
		if ($params ['end_time']) {
			$sql .= ' and ctime<' . $params ['end_time'];
		}
		
		if (isset ( $params ['from'] )) {
			$sql .= " and `from`='{$params['from']}'";
		}
		
		if ($params ['keyword']) {
			$sql .= " and `keyword`='{$params['keyword']}'";
		}
		$sql .= ' group by `keyword` order by num desc limit ' . intval ( $top );
		return $this->_pdo->getRows ( $sql );
	}

    public function getSearchMenuTop($params, $top = 100) {
        $sql = "select count(*) as num, `keyword`, params from " . self::getTableName () . " where 1=1";

        if ($params ['start_time']) {
            $sql .= ' and ctime>=' . $params ['start_time'];
        }

        if ($params ['end_time']) {
            $sql .= ' and ctime<' . $params ['end_time'];
        }

        if (isset ( $params ['from'] )) {
            $sql .= " and `from`='{$params['from']}'";
        }

        if ($params ['keyword']) {
            $sql .= " and `keyword`='{$params['keyword']}'";
        }
        $sql .= ' group by `params` order by num desc limit ' . intval ( $top );
        return $this->_pdo->getRows ( $sql );
    }


	
	/**
	 * 取得日志
	 * 
	 * @param
	 *        	$params
	 * @param int $start        	
	 * @param int $len        	
	 */
	public function getSearchLog($params, $start = 0, $len = 100000) {
		$sql = "select * from " . self::getTableName () . " where 1=1";
		
		if ($params ['start']) {
			$sql .= ' and ctime>=' . $params ['start'];
		}
		
		if ($params ['end']) {
			$sql .= ' and ctime<' . $params ['end'];
		}
		
		if (isset ( $params ['from'] )) {
			$sql .= " and `from`='{$params['from']}'";
		}
		
		if ($params ['keyword']) {
			$sql .= " and `keyword`='{$params['keyword']}'";
		}
		
		$sql .= ' limit ' . intval ( $start ) . ', ' . intval ( $len );
		
		return $this->_pdo->getRows($sql);
    }
}