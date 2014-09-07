<?php

namespace touch\controller;

class goodscontroller extends BaseController {
	public function detail($request, $response) {
		$goods_id = $request->id;
		/*$response->refer = $this->getBackUrl('goods_detail','_c=goods&_a=detail', $request->reBack);
		$_ = parse_url ( $response->refer ); // 需要处理非本站的外链，默认指定到首页
		if (isset ( $_ ['host'] ) && ! in_array ( $_ ['host'], array (
				'www.oakknight.com'
		) )) {
			 $response->refer = 'index.php';
		}
		unset ( $_ );
		*/
		if ($goods_id) {
			$info = \app\service\GoodsSrv::info ( intval ( $goods_id ) );
			if ($info) {
				$left = round ( $info ['market_price'] - $info ['price'] );
				if ($left > 0) {
					$info ['pricex'] = number_format ( $left, 2 );
				}
				$info ['liked'] = false;
				if ($this->has_login) {
					$loveInfo = \app\dao\LoveDao::getSlaveInstance ()->getInfo ( array (
							'is_delete = 0',
							'user_id = ' . $this->current_user ['user_id'],
							'goods_id = ' . $info ['goods_id'] 
					) );
					if ($loveInfo) {
						$info ['liked'] = true;
					}
				}
				$response->title = $info ['share_title'];
				$response->params = $info;
				
				//$this->layoutSmarty ( 'details' );
				$action_template = $this->_controller .'/details.html';
				$smarty =  new \sprite\mvc\SmartyView($this->_response);
				$smarty->render(strtolower($action_template));
				
			} else {
				self::showError ( '您要查看的商品已经穿越了～' );
			}
		} else {
			self::showError ( '获取商品id失败' );
		}
	}
	public function search($request, $response) {
		$sort = $request->get ( 'sort', '' );
		$page = $request->get ( 'page', 1 );
		$size = $request->get ( 'size', 10 );
		
		$params = array ();
		if ($request->cate_id)
			$params ['cate_id'] = intval ( $request->cate_id );
		
		if ($request->cate_name)
			$params ['cate_name'] = $request->cate_name;
		
		if ($request->tags)
			$params ['tags'] = $request->tags;
		
		if ($request->keyword)
			$params ['keyword'] = $request->keyword;
		
		if ($request->price) {
			$params ['price'] = $request->price;
		}
		$from = $request->get ( 'from', '' );
		
		if ($from == 'search') // 保护词转换
			$params = self::getAlia ( $params );
		$searchSrv = new \app\service\SearchSrv ();
		$ret = $searchSrv->search ( $params, $sort, $page, $size );
		if ($this->has_login && $ret ['list']) {
			foreach ( $ret ['list'] as $k => $r ) {
				$ret ['list'] [$k] ['liked'] = false;
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
		
		$ret ['cur_page'] = $page;
		$ret ['pages'] = ceil ( $ret ['count'] / $size );
		$ret ['prev'] = ($page <= 1) ? false : $page - 1;
		$ret ['next'] = ($ret ['pages'] <= $page) ? false : $page + 1;
		
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		$response->cur_url = $url;
		
		$params ['sort'] = $sort;
		$response->params = $params;
		$response->ret = $ret;
		$response->title = $request->tags ? $request->tags : '全部礼物';
		$this->layoutSmarty ( 'search' );
		
	}
}