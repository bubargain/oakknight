<?php

namespace touch\controller;

class addresscontroller extends BaseController {
	public function index($request, $response) {
		$user_id = $this->current_user ['user_id'];
		$size = 100;
		$page = $request->get ( 'page', 1 );
		$page = $page < 1 ? 1 : $page;
		$limit = ($page - 1) * $size . ',' . $size;
		try {
			$response->list = \app\service\AddressSrv::getList ( $user_id, $limit );
		} catch ( \Exception $e ) {
			$this->showError ( $e->getMessage () );
		}
		if ($request->isSel) {
			$response->title = '选择地址';
			$response->goods_id = $request->goods_id;
			$this->layoutSmarty ( 'list' );
		} else {
			$response->title = '收货地址管理';
			$this->layoutSmarty ( 'index' );
		}
	}
	public function add($request, $response) {
		$response->title = '新增收货地址';
		if ($this->isPost ()) {
			try {
				$info ['user_id'] = $this->current_user ['user_id'];
				$info ['consignee'] = $request->post ( 'consignee' );
				$info ['region_id'] = $request->post ( 'region_id', 0 );
				$info ['region_name'] = $request->post ( 'region_name' );
				$info ['address'] = $request->post ( 'address' );
				$info ['phone_mob'] = $request->post ( 'phone_mob' );
				
				if (! $info ['consignee'] || ! $info ['address'] || ! $info ['phone_mob']) {
					throw new \Exception ( '请完善地址信息', 3000 );
				}
				if (! preg_match ( '/^1[0-9]{10}$/', $info ['phone_mob'] )) {
					throw new \Exception ( '手机号码错误', 3000 );
				}
				try {
					if ($request->addr_id) {
						\app\service\AddressSrv::edit ( $request->addr_id, $info );
						$this->renderJson ( array (
								'ret' => array (
										'status' => 200,
										'data' => '修改地址成功',
										'addr_id' => $request->addr_id 
								) 
						) );
					} else {
						$addr_id = \app\service\AddressSrv::add ( $info );
						if ($request->goods_id) {
							$this->renderJson ( array (
									'ret' => array (
											'status' => 201,
											'data' => '新增地址成功',
											'goods_id' => $request->goods_id,
											'addr_id' => $addr_id
									) 
							) );
						} else {
							$this->renderJson ( array (
									'ret' => array (
											'status' => 200,
											'data' => '新增地址成功',
											'addr_id' => $addr_id 
									) 
							) );
						}
					}
				} catch ( \Exception $e ) {
					throw $e;
				}
			} catch ( \Exception $e ) {
				$this->renderJson ( array (
						'ret' => array (
								'status' => $e->getCode (),
								'data' => $e->getMessage () 
						) 
				) );
			}
		} else {
			if ($request->addr_id) {
				$response->address = \app\dao\AddressDao::getSlaveInstance ()->find ( $request->addr_id );
			}
			$response->region_options = \app\dao\RegionDao::getSlaveInstance ()->getList ();
			$response->goods_id = $request->goods_id;
			$this->layoutSmarty ( 'add' );
		}
	}
	public function detail($request, $response) {
		$response->title = "地址详情";
		if ($request->addr_id) {
			try {
				$response->info = \app\dao\AddressDao::getSlaveInstance ()->find ( $request->addr_id );
			} catch ( \Exception $e ) {
				$this->showError ( $e->getMessage () );
			}
		} else {
			$this->showError ( '获取地址失败' );
		}
		if ($request->reBack) {
			$response->refer = "index.php?_c=address&_a=index";
		} else {
			$response->refer = $this->get_refer ();
		}
		$this->layoutSmarty ( 'detail' );
	}
	public function delete($request, $response) {
		if ($request->addr_id) {
			try {
				\app\service\AddressSrv::delete ( $request->addr_id, $this->current_user ['user_id'] );
				header ( "Location:index.php?_c=address&_a=index" );
			} catch ( \Exception $e ) {
				$this->showError ( $e->getMessage () );
			}
		} else {
			$this->showError ( '获取地址失败' );
		}
	}
	public function seladdress($request, $response) {
		if ($request->addr_id) {
			try {
				$this->renderJson ( array (
						'ret' => array (
								'status' => 200,
								'data' => '已选择该地址',
								'addr_id' => $request->addr_id 
						) 
				) );
			} catch ( \Exception $e ) {
				$this->renderJson ( array (
						'ret' => array (
								'status' => 300,
								'data' => $e->getMessage () 
						) 
				) );
			}
		} else {
			$this->showError ( '获取地址失败' );
		}
	}
	public function setdefault($request, $response) {
		if ($request->addr_id) {
			try {
				\app\service\AddressSrv::setDefault ( $request->addr_id, $this->current_user ['user_id'] );
				$this->renderJson ( array (
						'ret' => array (
								'status' => 200,
								'data' => '成功设为默认地址' 
						) 
				) );
			} catch ( \Exception $e ) {
				$this->renderJson ( array (
						'ret' => array (
								'status' => 300,
								'data' => $e->getMessage () 
						) 
				) );
			}
		} else {
			$this->showError ( '获取地址失败' );
		}
	}
}