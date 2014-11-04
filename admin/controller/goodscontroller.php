<?php

namespace admin\controller;

use app\dao\GoodsDao;
use app\dao\OrderGoodsDao;
use app\dao\OrderDao;
use app\service\OrderSrv;
use \stdClass;
use app\common\util\subpages;

class GoodsController extends BaseController {
	// 商品列表
	public function index($request, $response) {
		$response->title = '商品列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->goods_id) {
			$params ['goods_id'] = "`goods_id` = " . intval ( $request->goods_id );
			$response->goods_id = intval ( $request->goods_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&goods_id=' ) === false) {
				$extUrl .= "&goods_id=" . intval ( $request->goods_id );
			}
		}
		if ($request->goods_name) {
			$params ['goods_name'] = "`goods_name` LIKE '%" . trim ( $request->goods_name ) . "%'";
			$response->goods_name = trim ( $request->goods_name );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&goods_name=' ) === false) {
				$extUrl .= "&goods_name=" . trim ( $request->goods_name );
			}
		}
		if ($request->cate_id) {
			$params ['cate_id'] = "`cate_id` = " . intval ( $request->cate_id );
			$cate_id = intval ( $request->cate_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&cate_id=' ) === false) {
				$extUrl .= "&cate_id=" . intval ( $request->cate_id );
			}
		}
		if ($request->brand_id) {
			$params ['brand_id'] = "`brand_id` = " . intval ( $request->brand_id );
			$brand_id = intval ( $request->brand_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&brand_id=' ) === false) {
				$extUrl .= "&brand_id=" . intval ( $request->brand_id );
			}
		}
		if ($request->status || $request->status === '0') {
			$status = intval ( $request->status );
			// 待审核
			if ($status == 1) {
				$params ['status'] = "`status` & 0b00100 = 0 ";
			}
			// 已审核
			if ($status == 2) {
				$params ['status'] = "`status` & 0b00100 = 0b00100 ";
			}
			// 禁售
			if ($status == 3) {
				$params ['status'] = "`status` & 0b00010 = 0b00010 ";
			}
			// 可售
			if ($status == 4) {
				$params ['status'] = "`status` = 12 ";
			}
			// 已删除
			if ($request->status === '0') {
				$params ['status'] = "`status` & 0b00001 = 0b00001 ";
				// 对0进行处理
				$response->statusType = true;
				$status = 0;
			}
			$response->status = $status;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&status=' ) === false) {
				$extUrl .= "&status=" . $status;
			}
		}
		$total = \app\dao\GoodsDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] ) . $extUrl;
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\GoodsDao::getSlaveInstance ()->getList ( $params, $limit );
		}
		$response->list = $list;
		$response->gcategoryOptionList = self::getGcategoryOptionList ( $cate_id );
		$response->brandOptionList = self::getBrandOptionList ( $brand_id );
		$response->page = $page->GetPageHtml ();

		$this->layoutSmarty ( 'index' );
	}
	// 查看商品
	public function detail($request, $response) {
		$response->title = '查看商品';
		$goods_id = intval ( $request->goods_id );
		// 获取记录
		$info = \app\service\GoodsSrv::info ( $goods_id );
		// 构造status
		$info = array_merge ( $info, \app\dao\GoodsDao::status2Arr ( $info ['status'] ) );
		// 获取品牌名称
		$brandInfo = \app\dao\BrandDao::getSlaveInstance ()->find ( $info ['brand_id'] );
		$response->brand_name = $brandInfo ['brand_name'];
		$response->info = $info;
		$response->images = \app\dao\GoodsImageDao::getSlaveInstance ()->getAll ( $info ['goods_id'] );
		$response->cdn_ymall = CDN_YMALL;
		$this->layoutSmarty ( 'detail' );
	}
	// 修改商品状态
	public function status($request, $response) {
		switch ($request->status) {
			// 通过审核
			case 'approval' :
				$status = " 24 ";
				break;
			// 未通过审核
			case 'disapproval' :
				$status = " status & 0b01011 ";
				break;
			// 可售
			case 'closed' :
				$status = " status | 0b00010 ";
				break;
			// 禁售
			case 'open' :
				$status = " status & 0b01101 ";
				break;
		}
		if (! $request->goods_ids) {
			throw new \Exception ( '请选择操作商品ids', '40002' );
		}
		preg_match_all ( '/[0-9]+/', $request->goods_ids, $r );
		if (! $r [0]) {
			throw new \Exception ( '请选择操作商品ids', '40002' );
		}
		$where = ' goods_id IN(' . implode ( ',', $r [0] ) . ')';

        $sale_time = $request->sale_time ? strtotime( $request->sale_time ) : time();
		try {
			\app\dao\GoodsDao::getMasterInstance()->editStatus( $status, $where, $sale_time );
		} catch ( \Exception $e ) {
			echo $e->getMessage ();
		}
	}
	
	// 商品分类下拉框
	public function getGcategoryOptionList($cate_id = 0) {
		$list = \app\dao\GcategoryDao::getSlaveInstance ()->getList ();
		$str = "<option value=''>-所属分类-</option>";
		foreach ( $list as $val ) {
			if ($cate_id == $val ['cate_id']) {
				$str .= "<option value=" . $val ['cate_id'] . " selected='selected'>" . $val ['cate_name'] . "</option>";
			} else {
				$str .= "<option value=" . $val ['cate_id'] . ">" . $val ['cate_name'] . "</option>";
			}
		}
		return $str;
	}
	// 品牌下拉框
	public function getBrandOptionList($brand_id = 0) {
		$list = \app\dao\BrandDao::getSlaveInstance ()->getList ();
		$str = "<option value=''>-所属品牌-</option>";
		foreach ( $list as $val ) {
			if ($brand_id == $val ['brand_id']) {
				$str .= "<option value=" . $val ['brand_id'] . " selected='selected'>" . $val ['brand_name'] . "</option>";
			} else {
				$str .= "<option value=" . $val ['brand_id'] . ">" . $val ['brand_name'] . "</option>";
			}
		}
		return $str;
	}
	// 商品分类占比
	public function gcategoryRate($request, $response) {
		$response->title = '商品分类占比';
		// 获取搜索信息
		if ($request->cntType) {
			// 上架且通过审核且可售的商品
			$params ['cntType'] = "  `status` & 0b01100 = 0b01100 ";
			$response->cntType = $request->cntType;
		} else {
			// 未被删除的所有商品
			$params ['cntType'] = " `status` & 0b11111 <> 0b00001 ";
		}
		// 商品分类对应的商品数量
		$response->list = self::getRateList ( $request, $response, $params );
		$this->layoutSmarty ( 'gcategoryrate' );
	}

	public function getRateList($request, $response, $params) {
		$list = array ();
		// 按照分类统计商品数量
		$goodsList = array ();
		$goodsSum = 0;
		$ret = \app\dao\GoodsDao::getSlaveInstance ()->getAllGoods ( $params );
		if (! $ret) {
			return $list;
		}
		foreach ( $ret as $val ) {
			$goodsSum ++;
			$goodsList [$val ['cate_id_1']] ['cate_id_1_cnt'] ++;
			$goodsList [$val ['cate_id_2']] ['cate_id_2_cnt'] ++;
		}
		// 获取分类名称
		$gcategoryList = array ();
		$ret = \app\dao\GcategoryDao::getSlaveInstance ()->getList ();
		if (! $ret) {
			return $list;
		}
		foreach ( $ret as $val ) {
			$gcategoryList [$val ['cate_id']] ['parent_id'] = $val ['parent_id'];
			$gcategoryList [$val ['cate_id']] ['cate_name'] = $val ['cate_name'];
		}
		// 构造分类名称-统计数量数组
		foreach ( $goodsList as $key => $val ) {
			// 一级分类
			if ($gcategoryList [$key] ['parent_id'] == 0) {
				$list [$key] ['cate_name'] = $gcategoryList [$key] ['cate_name'];
				$list [$key] ['goods_cnt'] = $val ['cate_id_1_cnt'] ? $val ['cate_id_1_cnt'] : 0;
				$list [$key] ['goods_cnt_rate'] = round ( $list [$key] ['goods_cnt'] / $goodsSum, 4 ) * 100 . "%";
			} else {
				$list [$gcategoryList [$key] ['parent_id']] ['child_cate'] [$key] ['cate_name'] = $gcategoryList [$key] ['cate_name'];
				$list [$gcategoryList [$key] ['parent_id']] ['child_cate'] [$key] ['goods_cnt'] = $val ['cate_id_2_cnt'] ? $val ['cate_id_2_cnt'] : 0;
				$list [$gcategoryList [$key] ['parent_id']] ['child_cate'] [$key] ['goods_cnt_rate'] = round ( $list [$gcategoryList [$key] ['parent_id']] ['child_cate'] [$key] ['goods_cnt'] / $goodsSum, 4 ) * 100 . "%";
			}
		}
		return $list;
	}
	// 商品分类销售占比
	public function gcategorySaleRate($request, $response) {
		$response->title = '商品分类销售占比';
		// 设置默认时间
		$default_time = strtotime ( date ( 'Ymd' ) );
		$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $default_time;
		$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : strtotime ( date ( 'Ymd' ) ) + 24 * 3600;
		//
		$response->start_time = date ( 'Y-m-d', $start_time );
		$response->end_time = date ( 'Y-m-d', $end_time - 1 );
		$response->list = self::getSaleRateList ( $start_time, $end_time );
		//
		$this->layoutSmarty ( 'gcategorysalerate' );
	}
	public function getSaleRateList($start_time, $end_time) {
		$total_quantity = $total_amout = 0;
		$list = $gList = array ();
		// 获取分类名称
		$gcategory_params ['parent_id'] = " parent_id = 0";
		$gcategoryList = array ();
		$ret = \app\dao\GcategoryDao::getSlaveInstance ()->getList ( 'sort_order ASC', $gcategory_params );
		if (! $ret) {
			return $list;
		}
		foreach ( $ret as $val ) {
			$list [$val ['cate_id']] ['cate_name'] = $val ['cate_name'];
			$list [$val ['cate_id']] ['quantity'] = "0%";
			$list [$val ['cate_id']] ['amout'] = "0%";
		}
		// 根据条件获取订单
		$order_params ['time'] = "pay_time >= " . $start_time . " AND pay_time < " . $end_time;
		$order_params ['status'] = " order_status IN (11,12,13,14)";
		$orderIds = \app\dao\OrderDao::getSlaveInstance ()->getOrderIds ( $order_params );
		// 根据订单id获取订单商品
		$goodsList = \app\dao\OrderGoodsDao::getSlaveInstance ()->getGoodsByOrderIds ( $orderIds );
		if (! $goodsList) {
			return $list;
		}
		foreach ( $goodsList as $val ) {
			$total_quantity += $val ['quantity'];
			$total_amout += $val ['price'];
			$gList [$val ['cate_id_1']] ['quantity'] += $val ['quantity'];
			$gList [$val ['cate_id_1']] ['amout'] += $val ['price'];
		}
		// 构造分类名称-销售数量-销售额数组
		foreach ( $list as $key => $val ) {
			$list [$key] ['cate_name'] = $val ['cate_name'];
			$list [$key] ['quantity'] = $total_quantity ? round ( $gList [$key] ['quantity'] / $total_quantity, 4 ) * 100 . "%" : 0;
			$list [$key] ['amout'] = $total_amout ? round ( $gList [$key] ['amout'] / $total_amout, 4 ) * 100 . "%" : 0;
		}
		return $list;
	}

    public function ajaxBatchCost($request, $response) {

        if(self::isPost()) {
            $ids = $request->post('ids');
            $cost = $request->post('cost');
            $order = $request->post('order');

            $goodsDao = GoodsDao::getMasterInstance();
            $orderGoodsDao = OrderGoodsDao::getMasterInstance();

            foreach($ids as $k=>$v) {
                if($cost[$k] <= 0)
                    continue;

                $goodsDao->edit($v, array('cost_price'=>$cost[$k]));
                if($order)
                    $orderGoodsDao->editByWhere(array('cost_price'=>$cost[$k]), 'cost_price=0 AND goods_id='.$v);
            }
            $this->renderJson(array('status'=>200, 'retval'=>'ok'));
        }
        else {
            $ids = $request->get('ids');
            $goods_ids = explode(',', $ids);
            $list = GoodsDao::getSlaveInstance()->getInfoByGoodsIds($goods_ids);
            $ret = array();
            foreach($list as $t) {
                $ret[] = array('goods_id'=>$t['goods_id'], 'goods_name'=>$t['goods_name'], 'sku'=>$t['sku']);
            }

            $this->renderJson(array('status'=>200, 'retval'=>$ret));
        }
    }

    public function ajaxBatchApproval($request, $response) {
        $_time = time() ;
        if(self::isPost()) {
            $ids = $request->post('ids');
            $cost = $request->post('sale_time');
			
            $goodsDao = GoodsDao::getMasterInstance();

            foreach($ids as $k=>$v) {
                if($cost[$k]) {
					$_sale_time = strtotime($cost[$k]);
				}
				else {
					$_sale_time = $_time;
				}   

                $where = 'goods_id='.$v.' and status in(0,8, 24)';
                $goodsDao->editStatus( 24, $where, $_sale_time );
            }

            self::autoSaleGoods();//同步审核一次

            $this->renderJson(array('status'=>200, 'retval'=>$ids));
        }
        else {
            $ids = $request->get('ids');
            $goods_ids = explode(',', $ids);
			$params = array();
			$params[] = 'goods_id in('.implode(',', $goods_ids).')';
			$params[] = 'status in(0,8, 24)';
			$limit = '0,' . count($goods_ids);
			$list = GoodsDao::getSlaveInstance()->getList( $params, $limit );
			
            $ret = array();
			$_time_str = date('Y/m/d H:i:s', $_time);
            foreach($list as $t) {
				$t['sale_time'] = $t['sale_time'] ? date('Y/m/d H:i:s', $t['sale_time']) : $_time_str;
                $ret[] = array('goods_id'=>$t['goods_id'], 'goods_name'=>$t['goods_name'], 'sku'=>$t['sku'], 'sale_time'=>$t['sale_time']);
            }

            $this->renderJson(array('status'=>200, 'retval'=>$ret));
        }
    }

    public function showGoodsSaleTable($request, $response) {
        $params = array();
        $params['status'] = '`status`='.GoodsDao::BUY_STATUS;
        $list = GoodsDao::getSlaveInstance()->getList($params, '0,10000', $sort = ' goods_id asc');

        $params = array();
        $orderSrv = new OrderSrv();
        $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
        $orders = OrderDao::getSlaveInstance()->orderList($params, '0, 100000');

        $all_quantity = $total_amount = $order_num = 0;
        if($orders) {
            foreach($orders as $r) {
                $order_ids[] = $r['order_id'];
                $total_amount += $r['goods_amount'];
                $order_num++;
            }
            unset($orders);
        }

//销售单价	销售量	销售量占比	销售额	销售额占比

        /* 取得满足条件的订单商品列表 */
        $maps = array();
        $order_goods = OrderGoodsDao::getSlaveInstance()->getGoodsByOrderIds($order_ids);
        foreach( $order_goods as $r) {
            $maps[$r['goods_id']]['quantity'] += $r['quantity'];
            $maps[$r['goods_id']]['goods_amount'] += $r['price'] * $r['quantity'];
            $all_quantity += $r['quantity'];
        }
        //商品id	库存	SKU	商品名称	销售单价	销售量	销售量占比	销售额	销售额占比

        ob_start();
        echo '<table border="1">';
        echo '<tr><th>商品id</th><th>SKU</th><th>商品名称</th><th>销售单价（参考）</th><th>销售量</th><th>销售量占比</th><th>销售额</th><th>销售额占比</th><th>库存</th></tr>';
        foreach($list as $item) {
            if( isset($maps[$item['goods_id']]) ) {
                $quantity = $maps[$item['goods_id']]['quantity'];
                $goods_amount = $maps[$item['goods_id']]['goods_amount'];
                $quantity_rate = round($maps[$item['goods_id']]['quantity'] * 100 / $all_quantity, 2);
                $goods_amount_rate = round($maps[$item['goods_id']]['goods_amount'] * 100 / $total_amount, 2);
            }
            else {
                $quantity = 0;
                $goods_amount = 0.00;
                $quantity_rate = 0;
                $goods_amount_rate = 0.00;
            }

            echo '<tr><td>'.$item['goods_id'].'</td><td>'.$item['sku'].'</td><td>'.$item['goods_name'].'</td><td>'.$item['price'].'</td><td>'.$quantity
                .'</td><td>'.$quantity_rate.'%</td><td>'.$goods_amount.'</td><td>'.$goods_amount_rate.'%</td><td>'.$item['stock'].'</td></tr>';
        }
        echo "</table>";

        $result = ob_get_clean();

        self::makeExcel($result, '在售商品销售统计'.date('Y/m/d'));
    }

    private function autoSaleGoods() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ADMIN_ADDR."/index.php?_c=auto&_a=autoSaleGoods");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
    }
}