<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class StoreController extends BaseController {
	// 店铺列表
	public function index($request, $response) {
		$response->title = '店铺列表';
		$response->list = \app\dao\StoreDao::getSlaveInstance ()->getList ();
		$this->layoutSmarty ( 'index' );
	}
	// 添加店铺
	public function add($request, $response) {
		$response->title = '添加店铺';
		// 保存新增
		if ($request->type == 'saveStore') {
			if ($request->store_name) {
				$state = intval ( $request->state );
				$store_id = $request->store_id ? intval ( $request->store_id ) : 1;
				$close_reason = trim ( $request->close_reason );
				// 若店铺关闭，则须填写关闭原因
				if (! $state && empty ( $close_reason )) {
					$this->showError ( '关闭店铺请填写原因' );
				}
				// 若店铺关闭，则关闭原因置空
				if ($state && ! empty ( $close_reason )) {
					$close_reason = '';
				}
				// 获取表单变量
				$params = array (
						'store_id' => $store_id,
						'store_name' => trim ( $request->store_name ),
						'address' => trim ( $request->address ),
						'zipcode' => trim ( $request->zipcode ),
						'tel' => trim ( $request->tel ),
						'credit_value' => intval ( $request->credit_value ),
						'praise_rate' => floatval ( $request->praise_rate ),
						'state' => $state,
						'close_reason' => $close_reason,
						'add_time' => 0,
						'end_time' => 0,
						'last_update' => 0,
						'sort_order' => intval ( $request->sort_order ),
						'description' => trim ( $request->description ),
						'auto_closed_time' => intval ( $request->auto_closed_time ),
						'if_codpay' => intval ( $request->if_codpay ) 
				);
				// 保存
				$result = \app\dao\StoreDao::getMasterInstance ()->save ( $request->store_id, $params, $request->isEdit );
				if (! $result) {
					$this->showError ( '保存信息失败' );
				}
				header ( "Location: index.php?_c=store&_a=index" );
			} else {
				$this->showError ( '提交信息不完整或有误' );
			}
		} else {
			$this->layoutSmarty ( 'add.form' );
		}
	}
	// 修改店铺
	public function edit($request, $response) {
		$response->title = '修改店铺';
		$store_id = intval ( $request->store_id );
		// 获取记录
		$info = \app\dao\StoreDao::getSlaveInstance ()->find ( $store_id );
		$response->info = $info;
		$response->isEdit = true;
		$this->layoutSmarty ( 'add.form' );
	}
	// 查看店铺
	public function detail($request, $response) {
		$response->title = '查看店铺';
		$store_id = intval ( $request->store_id );
		// 获取记录
		$info = \app\dao\StoreDao::getSlaveInstance ()->find ( $store_id );
		$response->info = $info;
		$this->layoutSmarty ( 'detail' );
	}
	// 删除店铺
	public function delete($request, $response) {
		$store_id = intval ( $request->store_id );
		$result = \app\dao\StoreDao::getMasterInstance ()->delete ( $store_id );
		if ($result) {
			header ( "Location: index.php?_c=store&_a=index" );
		} else {
			$this->showError ( '删除店铺失败' );
		}
	}
}