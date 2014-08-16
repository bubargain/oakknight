<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class GcategoryController extends BaseController {
	// 商品分类列表
	public function index($request, $response) {
		$response->title = '商品分类列表';
		$list = \app\dao\GcategoryDao::getSlaveInstance ()->getList ();
		$response->list = $this->getCategoryMap ( $list );
		$this->layoutSmarty ( 'index' );
	}
	// 新增商品分类
	public function add($request, $response) {
		$response->title = '添加商品分类';
		// 保存
		if ($request->type == 'saveGcategory') {
			if ($request->cate_name) {
				// 设置新分类的层级
				$cate_id = intval ( $request->cate_id );
				$parent_id = intval ( $request->parent_id );
				// 修改
				if ($cate_id) {
					$info = \app\dao\GcategoryDao::getSlaveInstance ()->find ( $cate_id );
					// 同级别修改时
					if ($info ['parent_id'] == $parent_id) {
						$sort_order = intval ( $info ['sort_order'] );
					} else {
						// 跨级别修改时,取同级最大sort_order+1
						$sort_order = intval ( \app\dao\GcategoryDao::getSlaveInstance ()->getAdjacentSortOrder ( $parent_id ) );
					}
				} else {
					// 新增分类时,取所选级最大sort_order+1
					$sort_order = intval ( \app\dao\GcategoryDao::getSlaveInstance ()->getAdjacentSortOrder ( $parent_id ) );
				}
				$level = $this->getLevel ( $parent_id );
				// 获取表单变量
				$params = array (
						'store_id' => 0,
						'cate_name' => trim ( $request->cate_name ),
						'parent_id' => $parent_id,
						'sort_order' => $sort_order,
						'if_show' => intval ( $request->if_show ),
						'level' => $level 
				);
				// 保存
				$result = self::save ( $cate_id, $params, $request->isEdit );
				if (! $result) {
					$this->showError ( '保存信息失败' );
				}
				header ( "Location: index.php?_c=gcategory&_a=index" );
			} else {
				$this->showError ( '提交信息不完整或有误' );
			}
		} else {
			// 获取商品分类列表
			$list = \app\dao\GcategoryDao::getSlaveInstance ()->getList ( 'cate_id ASC', array (
					'parent_id = 0' 
			) );
			$response->list = self::getCategoryMap ( $list );
			$response->parent_id_str = self::getOptionList ( $list );
			$this->layoutSmarty ( 'add.form' );
		}
	}
	// 修改商品分类
	public function edit($request, $response) {
		$response->title = '修改商品分类';
		$cate_id = intval ( $request->cate_id );
		// 获取记录
		$info = \app\dao\GcategoryDao::getSlaveInstance ()->find ( $cate_id );
		$response->info = $info;
		$response->isEdit = true;
		// 获取商品分类列表
		$list = \app\dao\GcategoryDao::getSlaveInstance ()->getList ( 'cate_id ASC', array (
				'parent_id = 0' 
		) );
		$response->parent_id_str = self::getOptionList ( $list, $cate_id, $info ['parent_id'] );
		$this->layoutSmarty ( 'add.form' );
	}
	// 删除商品分类
	public function delete($request, $response) {
		$cate_id = intval ( $request->cate_id );
		// 判断该商品分类是否还有子类
		$childCount = \app\dao\GcategoryDao::getSlaveInstance ()->getChildCount ( $cate_id );
		if ($childCount) {
			$this->showError ( '请先删除该商品分类的子类' );
		}
		$result = \app\dao\GcategoryDao::getMasterInstance ()->delete ( $cate_id );
		if ($result) {
			header ( "Location: index.php?_c=gcategory&_a=index" );
		} else {
			$this->showError ( '删除商品分类失败' );
		}
	}
	// 修改商品分类排序
	public function changeSortOrder($request, $response) {
		$cate_id = intval ( $request->cate_id );
		$parent_id = intval ( $request->parent_id );
		$type = trim ( $request->type );
		$sort_order = intval ( $request->sort_order );
		if ($type == 'up') {
			if ($sort_order == 1) {
				$this->showError ( '已经是第一位了' );
			}
			// 获取与该分类换位的分类
			$list = \app\dao\GcategoryDao::getSlaveInstance ()->getAdjacentGcategory ( "parent_id = $parent_id AND sort_order < $sort_order", "sort_order DESC", "1" );
			// 交换sort_order
			$result = self::exchangeSortOrder ( $cate_id, $sort_order, $list ['cate_id'], $list ['sort_order'] );
		}
		if ($type == 'down') {
			// 获取与该分类换位的分类
			$list = \app\dao\GcategoryDao::getSlaveInstance ()->getAdjacentGcategory ( "parent_id = $parent_id AND sort_order > $sort_order", "sort_order ASC", "1" );
			if (! $list) {
				$this->showError ( '已经是最后一位了' );
			}
			// 交换sort_order
			$result = self::exchangeSortOrder ( $cate_id, $sort_order, $list ['cate_id'], $list ['sort_order'] );
		}
		if (! $result) {
			$this->showError ( '更改排序失败' );
		}
		header ( "Location: index.php?_c=gcategory&_a=index" );
	}
	// 商品分类的树形结构
	public function getCategoryMap($list) {
		$list = $this->getMenuTree ( $list, 0, 0 );
		if ($list) {
			for($i = 0; $i < count ( $list ); $i ++) {
				$list [$i] ['space'] = str_repeat ( '&nbsp;&nbsp;', intval ( $list [$i] ['level'] ) ) . (intval ( $list [$i] ['level'] ) > 1 ? '&nbsp;&nbsp;∟&nbsp;&nbsp;' : '');
				if ($i == 0) {
					$list [$i] ['noUp'] = true;
				}
				if ($i == count ( $list ) - 1) {
					$list [$i] ['noDown'] = true;
				}
			}
		}
		return $list;
	}
	// 获取分类的层级(父类的level+1)
	public function getLevel($parent_id) {
		$level = 0;
		if ($parent_id) {
			$info = \app\dao\GcategoryDao::getSlaveInstance ()->find ( $parent_id );
			$level = intval ( $info ['level'] ) + 1;
		}
		return $level;
	}
	// 保存记录
	public function save($cate_id, $params, $isEdit) {
		// 修改而非新增
		if ($isEdit) {
			// 验证所选主分类是否为自己的子类
			$child_ids = $this->array_multi2single ( \app\dao\GcategoryDao::getSlaveInstance ()->getChildCate_ids ( $cate_id ) );
			if (in_array ( $params ['parent_id'], $child_ids )) {
				$this->showError ( '无法作为本身或子类的分类' );
			}
			$result = \app\dao\GcategoryDao::getMasterInstance ()->edit ( $cate_id, $params );
			if (! $result) {
				$this->showError ( '修改分类失败' );
			}
		} else {
			$id = \app\dao\GcategoryDao::getMasterInstance ()->add ( $params );
			if (! $id) {
				$this->showError ( '添加商品分类失败' );
			}
		}
		header ( "Location: index.php?_c=gcategory&_a=index" );
	}
	// 构造商品分类下拉框
	public function getOptionList($list, $cate_id = 0, $parent_id = 0) {
		$optionList = "<option value=0>--作为主分类--</option>";
		foreach ( $list as $val ) {
			// 默认选中所属的父类
			if (intval ( $val ['cate_id'] ) == intval ( $parent_id )) {
				$optionList .= "<option value=" . $val ['cate_id'] . " selected='selected'>" . $val ['cate_name'] . "</option>";
			} else {
				$optionList .= "<option value=" . $val ['cate_id'] . ">" . $val ['cate_name'] . "</option>";
			}
		}
		return $optionList;
	}
	/**
	 * 递归无限级分类【先序遍历算】，获取任意节点下所有子孩子
	 *
	 * @param array $arrCate
	 *        	待排序的数组
	 * @param int $parent_id
	 *        	父级节点
	 * @param int $level
	 *        	层级数
	 * @return array $arrTree 排序后的数组
	 */
	public function getMenuTree($arrCat, $parent_id = 0, $level = 0) {
		static $arrTree = array (); // 使用static代替global
		if (empty ( $arrCat ))
			return FALSE;
		$level ++;
		foreach ( $arrCat as $key => $value ) {
			if ($value ['parent_id'] == $parent_id) {
				$value ['level'] = $level;
				$arrTree [] = $value;
				unset ( $arrCat [$key] ); // 注销当前节点数据，减少已无用的遍历
				self::getMenuTree ( $arrCat, $value ['cate_id'], $level );
			}
		}
		return $arrTree;
	}
	// 互换sort_order
	public function exchangeSortOrder($cate_id, $sort_order, $ncate_id, $nsort_order) {
		$params = array (
				'sort_order' => $nsort_order 
		);
		$result = \app\dao\GcategoryDao::getMasterInstance ()->edit ( $cate_id, $params );
		if ($result) {
			$params = array (
					'sort_order' => $sort_order 
			);
			return \app\dao\GcategoryDao::getMasterInstance ()->edit ( $ncate_id, $params );
		} else {
			return false;
		}
	}
}