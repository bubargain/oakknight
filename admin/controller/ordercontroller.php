<?php

namespace admin\controller;

use app\dao\GcategoryDao;
use app\dao\OrderDao;
use app\dao\OrderExtmDao;
use app\dao\OrderGoodsDao;
use app\service\OrderSrv;
use app\common\util\SubPages;

class OrderController extends BaseController {
	// 订单列表
	public function index($request, $response) {
		$response->title = '订单列表';

        $params = self::formatSearchParams($request);

		$total = \app\dao\OrderDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		// 分页对象
		$page = new SubPages ( $url, 20, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\OrderDao::getSlaveInstance ()->getList ( $params, $limit );
		}
		$response->list = $list;
		$response->page = $page->GetPageHtml();
		$response->down_link = str_replace('_a=index', '', $url) .'&_a=listDown';
		$this->layoutSmarty();
	}

    public function listDown($request, $response) {
        $params = self::formatSearchParams($request);
        $list = \app\dao\OrderDao::getSlaveInstance ()->getList ( $params, '0, 10000' );
//订单号 	买家姓名 	订单金额 	类型 	支付方式 	订单状态 	下单时间
        ob_start ();
        echo '<table>';
        echo '<tr><th>order_sn</th><th>买家姓名</th><th>下单时间</th><th>支付时间</th><th>商品名称</th><th>sku</th><th>数量</th></tr>';

        foreach ( $list as $r ) {
            echo '<tr><td>'.$r['order_sn'].'</td><td>'.$r['buyer_name'].'</td><td>'.date('Y/m/d H:i:s', $r['add_time']).'</td><td>'.date('Y/m/d H:i:s', $r['pay_time']).'</td><td>'.$r['goods_name'].'</td><td>'.$r['sku'].'</td><td>'.$r['quantity'].'</td></tr>';
        }
        echo '</table>';
        $result = ob_get_clean();

        self::makeExcel ( $result, '订单列表' . date ( 'Y/m/d' ) );
    }

    private function formatSearchParams($request) {
        $params = array();
        $orderSrc = new \app\service\OrderSrv();
        if ($request->order_sn) {
            $params ['order_sn'] = "a.order_sn = " . trim ( $request->order_sn );
        }
        if ($request->type) {
            $params ['type'] = "a.type = '" . trim ( $request->type ) . "'";
        }
        if ($request->buyer_name) {
            $params ['buyer_name'] = "a.buyer_name='" . trim ( $request->buyer_name ) . "'";
        }
        if ($request->order_status) {
            if ($request->order_status == 1) {
                $params ['order_status'] = "a.order_status in( " . $orderSrc::PAYED_ORDER . ',' . $orderSrc::SHIPPING_ORDER . ',' . $orderSrc::RECEIVED_ORDER . ',' . $orderSrc::FINISHED_ORDER . ')';
            } else {
                $params ['order_status'] = "a.order_status = " . intval ( $request->order_status );
            }
        }
        if ($request->phone_mob) {
            $params ['phone_mob'] = "c.phone_mob = '" . trim ( $request->phone_mob ) . "'";
        }
        if ($request->goods_name) {
            $params ['sku'] = "b.sku = '" . trim ( $request->goods_name ) . "'";
        }
        if ($request->buyer_id) {
            $params ['buyer_id'] = "a.buyer_id= " . $request->buyer_id;
        }
        if ($request->start_time && $request->end_time) {
            $_default = strtotime ( date ( "Y-m-d" ) );
            $start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $_default;
            $end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : $_default + 24 * 3600;
            $params ['add_time'] = "a.add_time >= " . $start_time . " AND a.add_time < " . $end_time;
        }

        return $params;
    }

	// 查看订单
	public function detail($request, $response) {
		$response->title = '查看订单';
		$order_id = intval ( $request->order_id );
		// 订单详情
		$info = \app\dao\OrderDao::getSlaveInstance ()->find ( $order_id );
		$response->info = $info;
		// 收货详情
		$orderExtmInfo = \app\dao\OrderExtmDao::getSlaveInstance ()->findByField ( 'order_id', $order_id );
		$response->orderExtmInfo = $orderExtmInfo [0];
		// 商品详情
		$orderGoodsList = \app\dao\OrderGoodsDao::getSlaveInstance ()->getList ( array (
				'order_id =' . $order_id 
		) );
		$response->cdn_ymall = CDN_YMALL;
		$response->orderGoodsList = $orderGoodsList;
		// 订单操作详情
		$orderLogList = \app\dao\OrderlogDao::getSlaveInstance ()->getList ( array (
				'order_id =' . $order_id 
		) );
		$response->orderLogList = $orderLogList;
		
		$this->layoutSmarty ();
	}
	// 取消订单
	public function cancel($request, $response) {
		$response->title = '取消订单';
		$order_id = intval ( $request->order_id );
		if ($request->type == 'saveRemark') {
			$remark = trim ( $request->remark );
			if ($remark) {
				try {
					\app\service\OrderSrv::cancel ( $order_id, $this->current_user ['user_id'], $remark );
				} catch ( \Exception $e ) {
					$this->showError ( $e->getMessage () );
				}
				header ( "Location: index.php?_c=order&_a=index" );
			} else {
				$this->showError ( '填写信息不完整' );
			}
		} else {
			$response->order_id = intval ( $request->order_id );
			$this->layoutSmarty ( 'cancel' );
		}
	}
	// 备注
	public function postscript($request, $response) {
		$response->title = '订单备注';
		$order_id = intval ( $request->order_id );
		if ($request->type == 'savePostscript') {
			$params = array (
					'postscript' => trim ( $request->postscript ) 
			);
			$result = \app\dao\OrderDao::getMasterInstance ()->edit ( $order_id, $params );
			if (! $result) {
				$this->showError ( '保存信息失败' );
			}
			header ( "Location: index.php?_c=order&_a=index" );
		} else {
			// 获取记录
			$info = \app\dao\OrderDao::getSlaveInstance ()->find ( $order_id );
			$response->info = $info;
			$this->layoutSmarty ( 'postscript' );
		}
	}
	public function saleDown($request, $response) {
		// 手机 管理员备注 配送方式 物流号 商品ID 一级分类 二级分类 品牌名称 商品名称 数量 成本价
		if (self::isPost ()) {
			$params = array ();
			if ($request->start_time)
				$params ['start_time'] = 'pay_time>=' . strtotime ( $request->start_time . ' 00:00:00' );
			if ($request->end_time)
				$params ['end_time'] = 'pay_time<' . (strtotime ( $request->end_time . ' 23:59:59' ) + 1);
			
			$orderSrv = new OrderSrv ();
			$params ['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
			/* 取得满足条件的订单列表 */
			$orders = OrderDao::getSlaveInstance ()->orderList ( $params, '0, 10000' );
			
			if (! $orders)
				return array ();
			
			$list = $order_ids = array ();
			foreach ( $orders as $r ) {
				$order_ids [] = $r ['order_id'];
				$list [$r ['order_id']] = $r;
			}
			unset ( $orders );
			
			/* 取得满足条件的订单商品列表 */
			$order_goods = OrderGoodsDao::getSlaveInstance ()->getGoodsByOrderIds ( $order_ids );
			foreach ( $order_goods as $r ) {
				$list [$r ['order_id']] ['goods'] [] = $r;
				
				$cate_ids [$r ['cate_id_1']] = $r ['cate_id_1'];
				$cate_ids [$r ['cate_id_2']] = $r ['cate_id_2'];
			}
			
			unset ( $order_goods );
			
			/* 取得收货人手机号码 */
			$extm = OrderExtmDao::getSlaveInstance ()->getInfoByOrderIds ( $order_ids );
			
			/* 取得收货人手机号码 */
			$cate_info = GcategoryDao::getSlaveInstance ()->getInfoByIds ( $cate_ids );
			
			ob_start ();
			echo '<table>';
			echo '<tr><th>order_sn</th><th>用户名</th><th>手机</th><th>支付时间</th><th>管理员备注</th><th>快递公司</th><th>物流号</th><th>订单金额</th><th>商品ID</th><th>一级分类</th><th>二级分类</th><th>商品名称</th><th>数量</th></tr>';
			
			foreach ( $list as $r ) {
				$item_num = count ( $r ['goods'] );
				if ($item_num > 1)
					$_rowspan = ' rowspan="' . $item_num . '"';
				
				$_id = $r ['order_id'];
				
				$pre = '<tr><td' . $_rowspan . '>' . $r ['order_sn'] . '</td><td' . $_rowspan . '>' . $r ['buyer_name'] . '</td><td' . $_rowspan . '>' . $extm [$_id] ['phone_mob'] . '</td><td' . $_rowspan . '>' . date ( 'Y/m/d H:i:s', $r ['pay_time'] ) . '</td><td' . $_rowspan . '>' . $r ['postscript'] . '</td>' . '<td' . $_rowspan . '>' . $r ['shipping_name'] . '</td><td' . $_rowspan . '>' . $r ['shipping_code'] . '</td><td' . $_rowspan . '>' . $r ['order_amount'] . '</td>';
				
				foreach ( $r ['goods'] as $g ) {
					echo $pre . '<td>' . $g ['goods_id'] . '</td><td>' . $cate_info [$g ['cate_id_1']] ['cate_name'] . '</td><td>' . $cate_info [$g ['cate_id_2']] ['cate_name'] . '</td><td>' . $g ['goods_name'] . '</td><td>' . $g ['quantity'] . '</td></tr>';
					
					$pre = '<tr>';
				}
			}
			
			$result = ob_get_clean ();
			
			self::makeExcel ( $result, '订单销售报表' . date ( 'Y/m/d' ) );
		} else {
			$params ['end_time'] = strtotime ( date ( 'Y/m/d', time () ) );
			$params ['start_time'] = $params ['end_time'] - 24 * 60 * 60;

            $response->params = $params;
            $this->layoutSmarty();
        }
    }

    public function saleDataTable($request, $response) {
        if(self::isPost()) {
            $params = array();
            if($request->start_time)
                $params['start_time'] = 'pay_time>='.strtotime( $request->start_time . ' 00:00:00' );
            if($request->end_time)
                $params['end_time'] = 'pay_time<='.(strtotime( $request->end_time  . ' 23:59:59') + 1);

            $orderSrv = new OrderSrv();
            $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
            /* 取得满足条件的订单列表 */
            $orders = OrderDao::getSlaveInstance()->orderList($params, '0, 10000');

            if(!$orders)
                return array();

            $list = $order_ids = array();
            foreach($orders as $r) {
                $order_ids[] = $r['order_id'];
                $list[$r['order_id']] = $r;
            }
            unset($orders);

            /* 取得满足条件的订单商品列表 */
            $order_goods = OrderGoodsDao::getSlaveInstance()->getGoodsByOrderIds($order_ids);
            $all_quantity = 0; $all_goods_amount = 0.00; $goods_list = $cate_cnt = array();
            foreach( $order_goods as $r) {
                $cate_ids[$r['cate_id_1']] = $r['cate_id_1'];
                $cate_ids[$r['cate_id_2']] = $r['cate_id_2'];

                $cate_cnt[$r['cate_id_1']][$r['goods_id']] = $r['goods_id'];
                $cate_cnt[$r['cate_id_2']][$r['goods_id']] = $r['goods_id'];

                $all_quantity += $r['quantity'];
                $all_goods_amount += $list[$r['order_id']]['goods_amount'];

                $goods_list[$r['cate_id_1']][$r['cate_id_2']][$r['goods_id']]['quantity'] += $r['quantity'];
                $goods_list[$r['cate_id_1']][$r['cate_id_2']][$r['goods_id']]['goods_amount'] += $list[$r['order_id']]['goods_amount'];
                $goods_list[$r['cate_id_1']][$r['cate_id_2']][$r['goods_id']]['sku'] = $r['sku'];
                $goods_list[$r['cate_id_1']][$r['cate_id_2']][$r['goods_id']]['goods_name'] = $r['goods_name'];
                $goods_list[$r['cate_id_1']][$r['cate_id_2']][$r['goods_id']]['price'] = $r['price'];
            }

            unset($order_goods);

            /* 取得收货人手机号码 */
            $cate_info = GcategoryDao::getSlaveInstance()->getInfoByIds($cate_ids);

            ob_start();
            echo '<table border="1">';
            echo '<tr><td colspan="8">销售数据' . $request->start_time . ' 至 '.$request->end_time. '</td>';
            echo '<tr><th>一级分类</th><th>二级分类</th><th>SKU</th><th>商品名称</th><th>销售单价（参考）</th><th>销售量（件）不包含退款</th><th>销售量占比(不包含退款)</th><th>销售额</th><th>销售额占比</th></tr>';
            echo '<tr><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>该sku商品销售件数/已销售商品总件数</th><th>&nbsp;</th><th>该sku商品销售额/已销售商品总金额</th></tr>';

            foreach($goods_list as $cate_1=>$row) {
                $cnt_1 = count($cate_cnt[$cate_1]);

                $_rowspan_1 = '<td rowspan="'.$cnt_1.'">'.$cate_info[$cate_1]['cate_name'].'</td>';
                foreach($row as $cate_2=>$r) {
                    $cnt_2 = count($cate_cnt[$cate_2]);
                    $pre = '<tr>' . $_rowspan_1 . '<td rowspan="'.$cnt_2.'">'.$cate_info[$cate_2]['cate_name'].'</td>';
                    foreach($r as $item) {
                        if($item['price'] * $item['quantity'] != $item['goods_amount']) {
                            $price_str = '<font color="red">'.$item['price'].'</font>';
                        } else {
                            $price_str = $item['price']  ;
                        }
                        echo $pre . '<td>'.$item['sku'].'</td><td>'.$item['goods_name'].'</td><td>'.$price_str.'</td><td>'.$item['quantity']
                            .'</td><td>'.round($item['quantity'] * 100 / $all_quantity, 2).'%</td><td>'.$item['goods_amount'].'</td><td>'.round($item['goods_amount'] * 100 / $all_goods_amount, 2).'%</td></tr>';

                        $pre = '<tr>';
                    }
                    $_rowspan_1 = '';
                }
            }

            $result = ob_get_clean();

            self::makeExcel($result, '销售数据报表'.date('Y/m/d'));
        }
        else {
            $params['end_time'] = strtotime ( date ( 'Y/m/d', time () ) );
            $params['start_time'] = $params ['end_time'] - 24 * 60 * 60;

            $response->params = $params;
            $this->layoutSmarty();
        }
    }

    public function reBuyOrder($request, $response) {
        if(self::isPost()) {
            if($request->start_time && $request->end_time) {
                $start_time = strtotime($request->start_time . ' 00:00:00');
                $end_time = strtotime($request->end_time . ' 23:59:59');
            }
            else {
                $str_time = date('Y/m/d');
                $start_time = strtotime($str_time . ' 00:00:00');
                $end_time = strtotime($str_time . ' 23:59:59');
            }

            $params['pay_time'] = "a.pay_time >= " . $start_time. " AND a.pay_time <= " . $end_time;

            if($request->sku)
                $params['sku'] = "b.sku='".$request->sku."'";

            if($request->phone_mob)
                $params ['phone_mob'] = "c.phone_mob = '" . trim ( $request->phone_mob ) . "'";

            $orderSrv = new \app\service\OrderSrv();
            $params['order_status'] = "a.order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
            $list = \app\dao\OrderDao::getSlaveInstance()->getList( $params, '0,100000', ' a.order_id asc');

            if (! $list)
                $this->showError ( '暂无订单信息' );


            foreach ( $list as $row ) {
                $maps['orders'][$row['order_id']] = $row['type'];
                $maps['_buyers'][$row['buyer_id']]++;
            }

            if($start_time) {
                // 处理重复购买
                $orderSrv = new \app\service\OrderSrv();
                $_p['pay_time'] = "pay_time < " . $start_time;
                $_p['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';
                $_p['buyer_id'] = "buyer_id in( " . implode(',', array_keys($maps['_buyers']) ) . ")";
                $old = \app\dao\OrderDao::getSlaveInstance()->orderList( $_p, '0,100000', ' order_id asc');
                if ($old) {
                    foreach ( $old as $o ) {
                        $maps['old_buyer'][$o['buyer_id']] = 1;
                    }
                }
            }


//user id	已购商品名称	sku	支付时间

            ob_start ();
            echo '<table border="1">';
            echo '<tr><th colspan="7">重复购买用户（' . date('Y/m/d', $start_time) . '--' . date('Y/m/d', $end_time) . '</th>';
            echo '<tr><th>order_sn</th><th>手机</th><th>下单时间</th><th>支付时间</th><th>商品名称</th><th>sku</th><th>数量</th></tr>';

            foreach ( $list as $row ) {
                if($maps['old_buyer'][$row['buyer_id']] || ( $maps['_buyers'][$row['buyer_id']] > 1) ) {
                    echo '<tr><td>'.$row['order_sn'].'</td><td>'.$row['buyer_name'].'</td><td>'.date('Y/m/d H:i:s',$row['add_time']).'</td><td>'.date('Y/m/d H:i:s',$row['pay_time']).'</td><td>'.$row['goods_name'].'</td><td>'.$row['sku'].'</td><td>'.$row['quantity'].'</td></tr>';
                }
            }

            echo '</table>';

            $result = ob_get_clean ();

            self::makeExcel ( $result, '重复购买用户统计' . date ( 'Y/m/d' ) );

        }
        else {
            $this->layoutSmarty();
        }
    }
}