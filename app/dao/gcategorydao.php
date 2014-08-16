<?php

namespace app\dao;

use sprite\db\SqlUtil;
use app\dao\YmallDao;

class GcategoryDao extends YmallDao {
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_gcategory';
	}
	public function getPKey() {
		return 'cate_id';
	}
	public function getList($order = 'sort_order ASC, cate_id ASC', $params = array()) {
		$sql = "SELECT * FROM " . $this->getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $order;
		return $this->_pdo->getRows ( $sql );
	}
	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}
	/**
	 * 根据id取得祖先信息
	 *
	 * @param
	 *        	$cate_id
	 */
	public function ancestor($cate_id, $if_show = true) {
		$sql = "select * from " . self::getTableName () . " where cate_id=?";
		if ($if_show) {
			$sql .= " and if_show=1";
		}
		
		$ret = array ();
		
		$sth = $this->_pdo->prepare ( $sql );
		while ( $cate_id ) {
			$sth->bindValue ( 1, $cate_id );
			// $this->_pdo->bindValue($sth, array($cate_id));
			$this->_pdo->execute ( $sth );
			$out = $sth->fetch ();
			if (! $out) {
				break;
			}
			
			$cate_id = $out ['parent_id'];
			$ret [] = $out;
		}
		$sth->closeCursor ();
		
		return array_reverse ( $ret );
	}
	// 获取与该分类换位的分类
	public function getAdjacentGcategory($where, $order, $limit) {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . $where . " ORDER BY " . $order . " LIMIT " . $limit;
		return $this->_pdo->getRow ( $sql );
	}
	// 获取商品分类的子类数量
	public function getChildCount($cate_id) {
		$sql = "SELECT COUNT(*) FROM " . self::getTableName () . " WHERE `parent_id`=?";
		return $this->_pdo->getOne ( $sql, array (
				$cate_id 
		) );
	}
	// 获取同父类的相邻分类的排序值
	public function getAdjacentSortOrder($parent_id) {
		$sql = "SELECT MAX(`sort_order`) FROM `ym_gcategory` WHERE `parent_id` = " . $parent_id;
		$sort_order = intval ( $this->_pdo->getOne ( $sql ) );
		return $sort_order + 1;
	}
	/**
	 * 递归，获取所有子分类ID集
	 *
	 * @param int $parent_id
	 *        	父类id
	 * @return array $cate_ids 所有子分类ID集
	 */
	public function getChildCate_ids($parent_id) {
		$ids [] = $parent_id;
		$sql = "SELECT `cate_id` FROM `ym_gcategory` WHERE `parent_id`=?";
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

    public function getInfoByIds($ids) {
        $list = array ();
        if (is_array ( $ids ) && count ( $ids ) > 0) {
            $sql = "SELECT * FROM " . self::getTableName () . " WHERE cate_id IN ( " . implode ( ',', $ids ) . ")";
            $tmp = $this->_pdo->getRows( $sql );
            foreach($tmp as $r) {
                $list[$r['cate_id']] = $r;
            }
        }
        return $list;
    }
}
