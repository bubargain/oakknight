<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class CmsTextlinkDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_cms_textlink';
	}
	public function getPKey() {
		return 'id';
	}
	public function getList($sort = 'sort ASC, id ASC', $params = array(), $limit = '0,9') {
		$sql = "SELECT a.ukey, b.* FROM ym_cms_location AS a RIGHT JOIN ym_cms_textlink AS b ON a.loc_id = b.loc_id WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		return $this->_pdo->getRows ( $sql );
	}
	public function getListCnt($params) {
		$sql = "SELECT COUNT(b.id) FROM ym_cms_location AS a RIGHT JOIN ym_cms_textlink AS b ON a.loc_id = b.loc_id WHERE " . self::makeSql ( $params );
		return $this->_pdo->getOne ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
	// 获取与该菜单换位的菜单
	public function getAdjacentGcategory($where, $order, $limit) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . $where . " ORDER BY " . $order . " LIMIT " . $limit;
		return $this->_pdo->getRow ( $sql );
	}
	// 获取商品菜单的子类数量
	public function getChildCount($cate_id) {
		$sql = "SELECT COUNT(*) FROM " . self::getTableName () . " WHERE `parent_id`=?";
		return $this->_pdo->getOne ( $sql, array (
				$cate_id 
		) );
	}
	// 获取同父类的相邻菜单的排序值+1
	public function getAdjacentSortOrder($parent_id, $loc_id) {
		$sql = "SELECT MAX(`sort`) FROM `ym_cms_textlink` WHERE `parent_id` =? and loc_id=?";
		$sort = intval ( $this->_pdo->getOne ( $sql, array($parent_id, $loc_id) ) );
		return $sort + 1;
	}
	/**
	 * 递归，获取所有子菜单ID集
	 *
	 * @param int $parent_id
	 *        	父类id
	 * @return array $cate_ids 所有子菜单ID集
	 */
	public function getChildCate_ids($parent_id) {
		$ids [] = $parent_id;
		$sql = "SELECT `id` FROM `ym_cms_textlink` WHERE `parent_id`=?";
		$list = $this->_pdo->getRow ( $sql, array (
				$parent_id 
		) );
		foreach ( $list as $key => $val ) {
			$child_count = self::getChildCount ( $list [$key] );
			if ($child_count > 0) {
				$ids [] = self::getChildCate_ids ( intval ( $list [$key] ) );
			}
			$ids [] = $list [$key];
		}
		return $ids;
	}
	public function getAllBySort($params = array(), $sort = 'sort ASC') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort;
		return $this->_pdo->getRows ( $sql );
	}
}