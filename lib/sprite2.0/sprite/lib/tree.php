<?php
namespace sprite\lib;

/**
 * @author liweiwei
 * 树结构
 *
 */
class Tree {
	
	/**
	 * 从数组创建一颗树
	 * @param array $items
	 * @return multitype:|Ambigous <>
	 */
	public static function buildTree(array $items) {
	
		$childs = array();
		if (empty($items))
			return $childs;
	
		foreach($items as &$item) {
			$childs[$item['parent_id']][] = &$item;
		}
		unset($item);
	
		foreach($items as &$item) {
			if (isset($childs[$item['id']])) {
				$item['childs'] = $childs[$item['id']];
			}
		}
	
		return $childs[0];
	}
	
	/**
	 * 按结点名取子树
	 * @param array $tree
	 * @param int $id
	 * @param obj $subTree
	 * @return multitype:
	 */
	public static function getSubTree(array $tree, $id, &$subTree) {
		foreach ($tree as $item) {
			if (empty($item['id']))
				continue;
			if ($item['id'] == $id)
				$subTree = $item;
			if (isset($item['childs']))
				self::getSubTree($item['childs'], $id, $subTree);
		}
		
		return array();
	}
	
	//注意数据规模
	/**
	 * 取得树的指定结点的上边部分
	 * @param array $items
	 * @param unknown_type $id
	 * @return multitype:|Ambigous <unknown>
	 */
	public static function getParentTree(array $items, $id) {
		$parent = array();
		if (empty($items) || empty($id))
			return $parent;
	
		foreach($items as &$item) {
			$parent[$item['id']] = &$item;
		}
		unset($item);
		
		foreach($items as &$item) {
			if (isset($parent[$item['parent_id']])) {
				$item['parent'] = $parent[$item['parent_id']];
			}
		}

		return $parent[$id];
	}
	
	/**
	 * @param tree $tree
	 * @param array $rows
	 * @return multitype:
	 */
	public static function parentTree2Array($tree, &$rows) {
		if (empty($tree))
			return array();
		if (isset($tree['parent'])) {
			$item = $tree['parent'];
			unset($tree['parent']);
			$rows[$tree['id']] = $tree;
			self::parentTree2Array($item, $rows);
		} else {
			$rows[$tree['id']] = $tree;
		}		
	}
	
	/**
	 * @param unknown_type $tree
	 * @param unknown_type $rows
	 * @return multitype:
	 */
	public static function subTree2Array($tree, &$rows) {
		if (empty($tree))
		return array();
		if (isset($tree['childs'])) {
			$item = $tree['childs'];
			unset($tree['childs']);
			$rows[$tree['id']] = $tree;
			self::subTree2Array($item, $rows);
		} else {
			$rows[$tree['id']] = $tree;
		}
	}
			
	
}