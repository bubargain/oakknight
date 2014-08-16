<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\common\util\subpages;

class WishGoodsController extends BaseController {
	// 喜欢商品列表
	public function index($request, $response) {
		$response->title = '喜欢商品列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->goods_id) {
			$params ['goods_id'] = "goods_id = " . intval ( $request->goods_id );
			$response->goods_id = intval ( $request->goods_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&goods_id=' ) === false) {
				$extUrl .= "&goods_id=" . intval ( $request->goods_id );
			}
		}
		if ($request->user_name) {
			// 根据user_name获取user_id
			$user = \app\dao\UserDao::getSlaveInstance ()->findByField ( 'user_name', trim ( $request->user_name ) );
			$user_id = $user [0] ['user_id'] ? $user [0] ['user_id'] : 0;
			$params ['user_id'] = "user_id = " . $user_id;
			$response->user_name = $request->user_name;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&user_id=' ) === false) {
				$extUrl .= "&user_id=" . intval ( $request->user_id );
			}
		}
		if ($request->user_id) {
			$params ['user_id'] = "user_id = " . intval ( $request->user_id );
			$response->user_id = intval ( $request->user_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&user_id=' ) === false) {
				$extUrl .= "&user_id=" . intval ( $request->user_id );
			}
		}
		if ($request->is_delete || $request->is_delete === '0') {
			$params ['is_delete'] = "is_delete = " . intval ( $request->is_delete );
			$response->is_delete = intval ( $request->is_delete );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&is_delete=' ) === false) {
				$extUrl .= "&is_delete=" . intval ( $request->is_delete );
			}
		}
		if ($request->start_time || $request->end_time) {
			$_default = strtotime ( date ( "Y-m-d" ) );
			$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $_default;
			$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : $_default + 24 * 3600;
			$params ['ctime'] = "ctime >= " . $start_time . " AND ctime < " . $end_time;
			$response->start_time = date ( 'Y-m-d', $start_time );
			$response->end_time = date ( 'Y-m-d', $end_time - 1 );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&start_time=' ) === false) {
				$extUrl .= "&start_time=" . trim ( $request->start_time ) . "&end_time=" . trim ( $request->end_time );
			}
		}
		$total = \app\dao\WishGoodsDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] ) . $extUrl;
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\WishGoodsDao::getSlaveInstance ()->getList ( $params, $limit );
		}
		$response->list = self::getList ( $list );
		$response->page = $page->GetPageHtml ();
		$this->layoutSmarty ( 'index' );
	}
	public function getList($list) {
		$arr = $goodsList = $ret = array ();
		foreach ( $list as $val ) {
			$arr ['goods_id'] [] = $val ['goods_id'];
			$arr ['user_id'] [] = $val ['user_id'];
		}
		$goodsList = \app\dao\GoodsDao::getSlaveInstance ()->getInfoByGoodsIds ( $arr ['goods_id'] );
		$userList = \app\dao\UserDao::getSlaveInstance ()->getInfoByUserIds ( $arr ['user_id'] );
		foreach ( $list as $key => $val ) {
			$ret [$key] ['goods_id'] = $val ['goods_id'];
			$ret [$key] ['user_id'] = $val ['user_id'];
			$ret [$key] ['ctime'] = $val ['ctime'];
			$ret [$key] ['is_delete'] = $val ['is_delete'];
			$ret [$key] ['goods_name'] = $goodsList [$val ['goods_id']] ['goods_name'];
			$ret [$key] ['user_name'] = $userList [$val ['user_id']] ['user_name'];
		}
		return $ret;
	}

    public function downList($request, $response) {
        $response->title = '商品实时统计报表';
        if(self::isPost()) {
            if($request->start_time)
                $params['start'] = strtotime($request->start_time . '00:00:00');

            if($request->end_time)
                $params['end'] = strtotime($request->end_time . '23:59:59') + 1;

            if($request->goods_id)
                $params['goods_id'] = $request->goods_id;

            $params['is_delete'] = 0;

            $wishes = \app\dao\WishGoodsDao::getSlaveInstance()->GroupByGoods($params);
            foreach($wishes as $g) {
                $maps[$g['goods_id']] = $g['cnt'];
            }

            if(!$wishes)
                $this->showError('暂无喜欢数据');

            $goodsDao = \app\dao\GoodsDao::getSlaveInstance();
            if($request->all) {
                $params = array('status'=>12);
                $list = $goodsDao->getList($params, '0,99999', 'goods_id asc');
            }
            else {
                $list = $goodsDao->getInfoByGoodsIds(array_keys($maps));
            }

            ob_start();
            echo '<table border="1">';
            echo '<tr><th>商品id</th><th>SKU</th><th>上新时间(ctime)</th><th>商品名称</th><th>喜欢数(净)</th><th>市场价</th><th>销售价</th><th>成本价</th><th>库存</th></tr>';
            foreach($list as $item) {
                $wish_cnt = isset($maps[$item['goods_id']]) ? $maps[$item['goods_id']] : 0;
                echo '<tr><td>'.$item['goods_id'].'</td><td>'.$item['sku'].'</td><td>'.date('Y/m/d H:i:s', $item['ctime']).'</td><td>'.$item['goods_name'].'</td><td>'.$wish_cnt.'</td><td>'.$item['market_price']
                    .'</td><td>'.$item['price'].'</td><td>'.$item['cost_price'].'</td><td>'.$item['stock'].'</td></tr>';
            }
            echo "</table>";

            $result = ob_get_clean();

            self::makeExcel($result, '商品实时统计报表'.date('Y/m/d'));
        }
        else {
            $this->layoutSmarty();
        }
    }


}