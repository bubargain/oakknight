<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class CategoryMenuController extends BaseController {
	// 分类菜单列表
	public function index($request, $response) {
		$response->title = '分类菜单列表';
		$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getList ( 'ym_cms_textlink', array (), '0,100', '`sort` ASC, `id` ASC ' );
		$response->list = $this->getCategoryMap ( $list );
		$this->layoutSmarty ( 'index' );
	}
	// 新增分类
	public function add($request, $response) {
		$response->title = '添加分类';
		// 保存
		if ($request->type == 'saveGcategory') {
			if ($request->title) {
				// 设置新分类的层级
				$id = intval ( $request->id );
				$parent_id = intval ( $request->parent_id );
				// 修改
				if ($id) {
					$info = \app\dao\CmsTextlinkDao::getSlaveInstance ()->find ( $id );
					// 同级别修改时
					if ($info ['parent_id'] == $parent_id) {
						$sort = intval ( $info ['sort'] );
					} else {
						// 跨级别修改时,取同级最大sotr+1
						$sort = intval ( \app\dao\CmsTextlinkDao::getSlaveInstance ()->getAdjacentSortOrder ( $parent_id ) );
					}
				} else {
					// 新增分类时,取所选级最大sotr+1
					$sort = intval ( \app\dao\CmsTextlinkDao::getSlaveInstance ()->getAdjacentSortOrder ( $parent_id ) );
				}
				// 获取表单变量
				$params = array (
						'loc_id' => 0,
						'title' => addslashes ( trim ( $request->title ) ),
						'parent_id' => $parent_id,
						'url' => addslashes ( trim ( $request->url ) ),
						'sort' => $sort,
						'status' => intval ( $request->status ) 
				);
				// 保存
				$result = $this->save ( $id, $params, $request->isEdit );
				if (! $result) {
					$this->showError ( '保存信息失败' );
				}
				header ( "Location: index.php?_c=categorymenu&_a=index" );
			} else {
				$this->showError ( '提交信息不完整或有误' );
			}
		} else {
			// 获取商品分类列表
			$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getList ( 'ym_cms_textlink', array (), '0,100', '`id` ASC' );
			$response->list = $this->getCategoryMap ( $list );
			$response->parent_id_str = $this->getOptionList ( $list );
			$this->layoutSmarty ( 'add.form' );
		}
	}
	// 修改分类
	public function edit($request, $response) {
		$response->title = '修改分类';
		$id = intval ( $request->id );
		// 获取记录
		$info = \app\dao\CmsTextlinkDao::getSlaveInstance ()->find ( $id );
		$response->info = $info;
		$response->isEdit = true;
		// 获取分类列表
		$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getList ( 'ym_cms_textlink', array (), '0,100', '`id` ASC' );
		$response->parent_id_str = $this->getOptionList ( $list, $info ['parent_id'] );
		$this->layoutSmarty ( 'add.form' );
	}
	// 保存记录
	public function save($id, $params, $isEdit) {
		// 判断是否选择二级分类作为父类，是则禁止
		$info = \app\dao\CmsTextlinkDao::getSlaveInstance ()->find ( intval ( $params ['parent_id'] ) );
		if ($info ['parent_id']) {
			$this->showError ( '分类不能超过两级' );
		}
		// 修改而非新增
		if ($isEdit) {
			// 验证所选主分类是否为自己的子类
			$child_ids = $this->array_multi2single ( \app\dao\CmsTextlinkDao::getSlaveInstance ()->getChildCate_ids ( $id ) );
			if (in_array ( $params ['parent_id'], $child_ids )) {
				$this->showError ( '无法作为本身或子类的分类' );
			}
			$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->edit ( $id, $params );
			if (! $result) {
				$this->showError ( '修改分类失败' );
			}
		} else {
			$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->add ( $params );
			if (! $result) {
				$this->showError ( '添加分类失败' );
			}
		}
		header ( "Location: index.php?_c=categorymenu&_a=index" );
	}
	// 删除商品分类
	public function delete($request, $response) {
		$id = intval ( $request->id );
		// 判断该商品分类是否还有子类
		$childCount = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getChildCount ( $id );
		if ($childCount) {
			$this->showError ( '请先删除该分类的子类' );
		}
		$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->delete ( $id );
		if ($result) {
			header ( "Location: index.php?_c=categorymenu&_a=index" );
		} else {
			$this->showError ( '删除分类失败' );
		}
	}
	// 商品分类的树形结构
	public function getCategoryMap($list) {
		$list = $this->getMenuTree ( $list, 0 );
		if ($list) {
			for($i = 0; $i < count ( $list ); $i ++) {
				$list [$i] ['space'] = intval ( $list [$i] ['parent_id'] ) ? '&nbsp;&nbsp;&nbsp;&nbsp;∟&nbsp;&nbsp;' : '&nbsp;&nbsp;';
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
	// 修改商品分类排序
	public function changeSortOrder($request, $response) {
		$id = intval ( $request->id );
		$parent_id = intval ( $request->parent_id );
		$type = trim ( $request->type );
		$sort = intval ( $request->sort );
		if ($type == 'up') {
			if ($sort == 1) {
				$this->showError ( '已经是第一位了' );
			}
			// 获取与该分类换位的分类
			$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getAdjacentGcategory ( "parent_id = $parent_id AND sort < $sort", "sort DESC", "1" );
			// 交换sort
			$result = $this->exchangeSortOrder ( $id, $sort, $list ['id'], $list ['sort'] );
		}
		if ($type == 'down') {
			// 获取与该分类换位的分类
			$list = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getAdjacentGcategory ( "parent_id = $parent_id AND sort > $sort", "sort ASC", "1" );
			if (! $list) {
				$this->showError ( '已经是最后一位了' );
			}
			// 交换sort
			$result = $this->exchangeSortOrder ( $id, $sort, $list ['id'], $list ['sort'] );
		}
		if (! $result) {
			$this->showError ( '更改排序失败' );
		}
		header ( "Location: index.php?_c=categorymenu&_a=index" );
	}
	// 互换sort
	public function exchangeSortOrder($id, $sort_order, $nid, $nsort) {
		$params = array (
				'sort' => $nsort 
		);
		$result = \app\dao\CmsTextlinkDao::getMasterInstance ()->edit ( $id, $params );
		if ($result) {
			$params = array (
					'sort' => $sort_order 
			);
			return \app\dao\CmsTextlinkDao::getMasterInstance ()->edit ( $nid, $params );
		} else {
			return false;
		}
	}
	// 构造商品分类下拉框
	public function getOptionList($list, $parent_id = 0) {
		$optionList = "<option value=0>--作为一级分类--</option>";
		foreach ( $list as $val ) {
			// 默认选中所属的父类
			if (intval ( $val ['id'] ) == intval ( $parent_id )) {
				$optionList .= "<option value=" . $val ['id'] . " selected='selected'>" . $val ['title'] . "</option>";
			} else {
				$optionList .= "<option value=" . $val ['id'] . ">" . $val ['title'] . "</option>";
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
	 * @return array $arrTree 排序后的数组
	 */
	public function getMenuTree($arrCat, $parent_id = 0) {
		static $arrTree = array (); // 使用static代替global
		if (empty ( $arrCat ))
			return FALSE;
		foreach ( $arrCat as $key => $value ) {
			if ($value ['parent_id'] == $parent_id) {
				$arrTree [] = $value;
				unset ( $arrCat [$key] ); // 注销当前节点数据，减少已无用的遍历
				$this->getMenuTree ( $arrCat, $value ['id'] );
			}
		}
		return $arrTree;
	}
}