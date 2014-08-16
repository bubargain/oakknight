<?php

namespace touch\controller;

class testcontroller extends BaseController {
	public function getGoods_ids($user_id) {
		try {
			$xml_array = simplexml_load_file ( 'data.xml' );
			//
			$default_goods_ids = explode ( ',', strval ( $xml_array->default ) );
			// 遍历
			for($i = 0; $i < count ( $xml_array->user ); $i ++) {
				// user_id
				$user = $xml_array->user [$i]->attributes ();
				if (strval ( $user [0] ) == $user_id) {
					// 找到该user_id对应的goods_ids
					$goods_ids = explode ( ',', strval ( $xml_array->user [$i]->items ) );
				}
			}
			if (! $goods_ids) {
				$this->showError ( "您输入的网址不正确，请联系礼物店管理员QQ：303617068", "http://www.ymall.com" );
			}
			return array_merge ( $default_goods_ids, $goods_ids );
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
		}
	}
	public function goods($request, $response) {
		$response->notice = "第一步： 请选出您感兴趣的商品";
		$response->btn = "下一步";
		if (! $request->user_id) {
			$this->showError ( '获取用户失败', "http://www.ymall.com" );
		}
		$goods_ids = self::getGoods_ids ( $request->user_id );
		$goodsList = \app\dao\GoodsDao::getSlaveInstance ()->getInfoByGoodsIds ( $goods_ids );
		$response->user_id = $request->user_id;
		$response->cdn_ymall = CDN_YMALL;
		$response->goodsList = $goodsList;
		$this->renderSmarty ( 'goods' );
	}
	public function goods_likes($request, $response) {
		$response->notice = "第二步： 哪些商品您可能会购买";
		$response->btn = "完成测试";
		$goods_ids = $request->goods_ids;
		if (! is_array ( $goods_ids ) || count ( $goods_ids ) < 1) {
			$this->showError ( '请至少选择一个商品' );
		}
		if ($request->type == 'buy') { // 保存购买的商品
			self::save ( $request->user_id, $goods_ids, 'buy' );
			header ( "Location:index.php?_c=test&_a=success" );
		} else { // 保存喜欢的商品
			$response->user_id = $request->user_id;
			self::save ( $request->user_id, $goods_ids, 'like' );
			// 展示喜欢的商品
			$goodsList = \app\dao\GoodsDao::getSlaveInstance ()->getInfoByGoodsIds ( $goods_ids );
			$response->type = "buy";
			$response->cdn_ymall = CDN_YMALL;
			$response->goodsList = $goodsList;
			$this->renderSmarty ( 'goods' );
		}
	}
	public function goods_detail($request, $response) {
		if (! $request->id) {
			$this->showError ( '获取商品失败', "http://www.ymall.com" );
		}
		$info = \app\service\GoodsSrv::info ( intval ( $request->id ) );
		if ($info) {
			$params = array (
					'title' => $info ['goods_name'],
					'goods_name' => $info ['goods_name'],
					'more_property' => $info ['more_property'],
					'description' => $info ['description'],
					'image_list' => $info ['images'] 
			);
			//
			$response->params = $params;
			$this->renderSmarty ( 'goods_detail' );
		} else {
			self::showError ( '您要查看的商品已经穿越了～' );
		}
	}
	public function save($user_id, $goods_ids, $type) {
		try {
			foreach ( $goods_ids as $val ) {
				$params = array (
						'user_id' => $user_id,
						'goods_id' => $val,
						'action' => $type,
						'ctime' => time () 
				);
				\app\dao\UserActionDao::getMasterInstance ()->add ( $params );
			}
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
			exit ();
		}
	}
	public function success($request, $response) {
		$response->notice = "非常感谢您的回答！我们会更加努力，来满足您的需求。酬金会在次日冲到你的手机账户，感谢参与！";
		$this->renderSmarty ( 'success' );
	}
	public function test($request, $response) {
		// $this->renderSmarty ( 'test' );
		$this->renderSmarty ( 'success' );
	}
}