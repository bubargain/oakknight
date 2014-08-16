<?php

namespace touch\controller;

use app\dao\RegionDao;
use app\dao\AddressDao;

class orderController extends BaseController {
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
	}
	public function order($request, $response) {
		$this->layoutSmarty ( 'order' );
	}
	
	// 用户中心
	public function index($request, $response) {
		$this->checkLogin ();
		$response->title = '用户中心';
		$response->user_name = $this->current_user ['user_name'];
		//
		$unpayCnt = 0;
		$response->unpayCnt = \app\dao\OrderDao::getSlaveInstance ()->getCntByStatus ( $this->current_user ['user_id'], 10 );
		// 优惠券数量
		$response->couponCnt = \app\service\coupon\UserCouponSrv::getListCnt ( array (
				'user_id' => $this->current_user ['user_id'],
				'state' => 'valid' 
		) );		
		$this->layoutSmarty ( 'index' );
	}
	
	// 订单确认
	public function confirm($request, $response) {
		$this->checkLogin ( 'index.php?_c=order&_a=confirm&id=' . $request->id . '&reBack=1' );
		try {
			$goods_id = $request->id;
			$quantity = 1;
			
			$user_id = $this->current_user ['user_id'];
			$orderSrv = new \app\service\OrderSrv ();
			$order = $orderSrv->preOrder ( $user_id, $goods_id, $quantity );
			$order ['coupon_json'] = $order ['coupon'] ? json_encode ( $order ['coupon'] ) : json_encode ( array () );
			
			$response->region_options = RegionDao::getSlaveInstance ()->getList ();
			// 判断用户是否有可用优惠劵
			
			$response->title = '订单确认';
			$response->order = $order;
			
			$response->address = $request->addr_id ? AddressDao::getSlaveInstance ()->find ( $request->addr_id ) : AddressDao::getSlaveInstance ()->getDefault ( $user_id );
			
			if ($request->reBack) {
				$response->refer = "index.php?_c=goods&_a=detail&id=" . $goods_id . "&reBack=1";
			} else {
				$response->refer = "javascript:window.history.go(-1);";
			}
			$this->layoutSmarty ( 'buy' );
		} catch ( \Exception $e ) {
			$message = ($e->getCode () == 50002) ? '太火爆，卖完了！' : $e->getMessage ();
			$this->showError ( $message );
		}
	}
	
	// 订单提交
	public function submit($request, $response) {
		$this->checkLogin ();
		try {
			$post ['goods_id'] = $request->id;
			$post ['quantity'] = $request->quantity;
			$post ['user_id'] = $this->current_user ['user_id'];
			$post ['type'] = 'touch';
			
			if (! $post ['goods_id'])
				throw new \Exception ( '请传商品id', 50000 );
			
			$post ['address'] ['user_id'] = $post ['user_id'];
			$post ['addr_id'] = $request->post ( 'addr_id', 0 );
			
			$post ['address'] ['consignee'] = $request->consignee;
			$post ['address'] ['region_id'] = $request->region_id;
			$post ['address'] ['region_name'] = $request->region_name;
			$post ['address'] ['address'] = $request->address;
			$post ['address'] ['phone_mob'] = $request->phone_mob;
			
			$post ['ucpn_id'] = $request->post ( 'ucpn_id', 0 );
			
			self::checkAddress ( $post ['address'] );
			
			$orderSrv = new \app\service\OrderSrv ();
			$order = $orderSrv->submit ( $post );
			
			// 订单生成后支付
			header ( "Location:index.php?_c=payment&_a=payForm&type=alipay&id=" . $order ['order_id'] );
		} catch ( \Exception $e ) {
			$this->showError ( $e->getMessage () );
		}
	}
	// 取消订单
	public function cancel($request, $response) {
		$this->checkLogin ();
		try {
			$ret = \app\service\OrderSrv::cancel ( $request->order_id, $this->current_user ['user_id'], '买家取消订单' );
			$this->renderJson ( array (
					'ret' => array (
							'status' => 200,
							'data' => '订单取消成功' 
					) 
			) );
		} catch ( \Exception $e ) {
			$this->renderJson ( array (
					'ret' => array (
							'status' => 10000,
							'data' => '取消订单操作内部错误' 
					) 
			) );
		}
	}
	// 订单列表
	public function orderList($request, $response) {
		$this->checkLogin ();
		$status = $request->status ? $request->status : 'unpay';
		$ret = self::getPageByStatus ( $status );
		$response->params = \app\service\OrderSrv::orders ( $this->current_user ['user_id'], $ret ['status'], 100 );
		$this->layoutSmarty ( $ret ['html'] );
	}
	
	// 订单详情
	public function orderDetail($request, $response) {
		$this->checkLogin ();
		$response->refer = $this->getBackUrl ( 'order_detail', '_c=order&_a=orderDetail', $request->reBack );
		$order_id = $request->order_id;
		if (! $order_id) {
			$this->showError ( '订单id有误' );
		}
		$info = \app\service\OrderSrv::info ( $order_id );
		if (! $info) {
			$this->showError ( '获取订单详情失败' );
		}
		$response->info = $info;
		//var_dump($info);
		$ret = self::getPageByStatus ( $request->status, '_detail' );
		$this->layoutSmarty ( $ret ['html'] );
	}
	private function checkAddress($info) {
		if (! $info ['consignee'] || ! $info ['phone_mob'] || ! $info ['address'] || ! $info ['region_name'])
			throw new \Exception ( '收货信息不完整，请完善', 5000 );
		
		if (! preg_match ( '/1[0-9]{10}/', $info ['phone_mob'] ))
			throw new \Exception ( '手机号码不正确', 5000 );
	}
	// 根据不同的订单状态，加载不同的订单列表页面
	private function getPageByStatus($status, $type = '') {
		switch ($status) {
			// 待付款订单
			case 'unpay' :
				$status = 10;
				$html = 'unpay';
				break;
			// 待发货订单
			case 'payed' :
				$status = 11;
				$html = 'payed';
				break;
			// 已发货订单
			case 'shipped' :
				$status = 12;
				$html = 'shipped';
				break;
			// 已完成
			case 'finished' :
				$status = 14;
				$html = 'finished';
				break;
			// 已取消订单
			case 'closed' :
				$status = 100;
				$html = 'closed';
				break;
			default :
				$this->showError ( '订单URL出错了' );
		}
		if ($type) {
			$html .= $type;
		}
		return array (
				'status' => $status,
				'html' => $html 
		);
	}
}