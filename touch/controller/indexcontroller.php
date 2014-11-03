<?php

namespace touch\controller;

use \sprite\cache\CacheManager;

class indexcontroller extends BaseController {
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
	}
	public function index($request, $response) {
		/*$cache = CacheManager::getInstance ();
		
		$key = 'touch_index_page';
		
		$ret = $cache->get ( $key );
		
		if (! $ret) {
			*/
			// memcache 赋值
			//$ret ['focuseMap_imageLink'] = self::getImageLinks ( 'focusMap', 4 );
			$ret ['top_goods'] = self::topGoods ( 4 );
			//$ret ['getTextLinks'] = self::getTextLinks ( 'giveHer' );
			//$ret ['giveHer_imageLink'] = self::getImageLinks ( 'giveHer' );
			//$ret ['giveHim_textLink'] = self::getTextLinks ( 'giveHim' );
			//$ret ['giveHim_imageLink'] = self::getImageLinks ( 'giveHim' );
		//	$cache->set ( $key, $ret, 1, 5 * 60 );
		//}
		$response->title = 'OAK&KNIGHT';
		$response->cdn_ymall = CDN_YMALL;
		$response->currency_rate = EUROTORMB;
		//$response->focusMap_imageLink = $ret ['focuseMap_imageLink'];
		$response->live_deals = $ret ['top_goods'];
		//var_dump($ret ['top_goods']);die();
		//$response->giveHer_textLink = $ret ['getTextLinks'];
		//$response->giveHer_imageLink = $ret ['giveHer_imageLink'];

        //$this->layoutSmarty ( 'index' );
       $action_template = $this->_controller .'/index.html';
		$smarty =  new \sprite\mvc\SmartyView($this->_response);
		$smarty->render(strtolower($action_template));
		
	}
	public function about($request, $response) {
		$response->title = '联系我们';
		$this->layoutSmarty ( 'about' );
	}
	
	// 页头的广告图片
	public function getImageLinks($ukey, $len = 2) {
		$info = \app\dao\CmsLocationDao::getSlaveInstance ()->findByField ( 'ukey', $ukey );
		if (! $info [0] ['status']) {
			return array ();
		}
		$list = \app\dao\CmsImagelinkDao::getSlaveInstance ()->getAllBySort ( array (
				'loc_id=' . $info [0] ['loc_id'],
				'status=1' 
		), $len );
		if (! $list) {
			return $list;
		}
		foreach ( $list as $key => $val ) {
			$extra = json_decode ( $val ['extra'], true );
			if ($extra ['goods_id']) { // 如果是商品而非上传的图片
				$goods_ids [] = $extra ['goods_id'];
				$list [$key] ['goods_id'] = $extra ['goods_id'];
				$list [$key] ['brand_name'] = $extra ['brand_name'];
				$list [$key] ['cate_name'] = $extra ['cate_name'];
			}
		}
		if ($goods_ids) {
			$list = self::formatGoods ( $list, $goods_ids );
		}
		return $list;
	}
	public function getTextLinks($ukey) {
		$info = \app\dao\CmsLocationDao::getSlaveInstance ()->findByField ( 'ukey', $ukey );
		if (! $info [0] ['status']) {
			return array ();
		}
		$ret = \app\dao\CmsTextlinkDao::getSlaveInstance ()->getAllBySort ( array (
				'loc_id=' . $info [0] ['loc_id'],
				'status=1' 
		) );
		foreach ( $ret as $val ) {
			if ($val ['sort'] == 1) {
				$list ['left'] = $val;
			}
			if ($val ['sort'] == 2) {
				$list ['right'] = $val;
			}
		}
		return $list;
	}
	private function formatGoods($list, $goods_ids) {
		if (! $goods_ids)
			return $list;
		
		$goods = \app\dao\GoodsDao::getSlaveInstance ()->getInfoByGoodsIds ( $goods_ids );
		$counts = \app\dao\GoodsStatisticsDao::getSlaveInstance ()->getStatisticsByGoodsIds ( $goods_ids );
		$likes = array ();
		if ($this->has_login) {
			$_tmp = \app\dao\LoveDao::getSlaveInstance ()->getMyListByGoodsIds ( $goods_ids, $this->current_user ['user_id'] );
			if ($_tmp) {
				foreach ( $_tmp as $r ) {
					$likes [$r ['goods_id']] = true;
				}
			}
		}
		// 获取角标信息
		$type_arr = \app\service\GoodsSrv::getSaleType ();
		//
		foreach ( $list as $k => $row ) {
			$list [$k] ['liked'] = isset ( $likes [$row ['goods_id']] ) ? true : false;
			$list [$k] ['wishes'] = isset ( $counts [$row ['goods_id']] ) ? $counts [$row ['goods_id']] ['wishes'] : 0;
			$list [$k] ['tags'] = '';
			$list [$k] ['sale_type_info'] = $goods [$row ['goods_id']] ['sale_type'] ? $type_arr [$goods [$row ['goods_id']] ['sale_type']] : array ();
			if (isset ( $goods [$row ['goods_id']] )) {
				$tmp = explode ( ' ', $goods [$row ['goods_id']] ['tags'] );
				$list [$k] ['tags'] = $tmp [0];
			}
			if (! $row ['url'])
				$list [$k] ['url'] = 'index.php?_c=goods&_a=detail&id=' . $row ['goods_id'];
		}
		
		return $list;
	}
	
	//展示商品
	public function topGoods($len=4) {
		$searchSrv = new \app\service\SearchSrv ();
		$ret = $searchSrv->search ( array ('status'=>24), '', 1, $len );
	
		// 获取角标信息
		$type_arr = \app\service\GoodsSrv::getSaleType ();
		//如果用户登录，则优先展示没like过的商品
		if ($this->has_login && $ret ['list']) {
			foreach ( $ret ['list'] as $k => $r ) {
				$ret ['list'] [$k] ['url'] = 'index.php?_c=goods&_a=detail&id=' . $r ['goods_id'];
				$ret ['list'] [$k] ['liked'] = false;
				// $ret ['list'] [$k] ['sale_type_info'] = $ret ['list'] [$row
				// ['goods_id']] ['sale_type'] ? $type_arr [$goods [$row
				// ['goods_id']] ['sale_type']] : array ();
				$ids [] = $r ['goods_id'];
			}
			
			$_tmp = \app\dao\LoveDao::getSlaveInstance ()->getMyListByGoodsIds ( $ids, $this->current_user ['user_id'] );
			if ($_tmp) {
				foreach ( $_tmp as $r ) {
					$_t [$r ['goods_id']] = true;
				}
				foreach ( $ret ['list'] as $k => $v ) {
					$ret ['list'] [$k] ['liked'] = isset ( $_t [$v ['goods_id']] ) ? true : false;
				}
			}
		}
		return $ret ['list'];
	}
}