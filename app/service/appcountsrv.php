<?php

namespace app\service;

use \app\dao\YmallActivitySgappGoodsDao;
use \app\service\BaseSrv;

class AppCountSrv extends BaseSrv {
	private $start = null;
	private $end = null;
	private $db = null;
	public function __construct() {
		$this->db = YmallActivitySgappGoodsDao::getSlaveInstance ()->getpdo ();
	}
	public function setDate($start, $end) {
		$this->start = $start;
		$this->end = $end;
	}
	public function getDate() {
		return array (
				'start' => $this->start,
				'end' => $this->end 
		);
	}
	/**
	 * 根据时间段统计订单数和销售额
	 */
	public function getOrderCnt() {
		$ret = array (
				'orderCnt' => 0,
				'orderAmount' => 0 
		);
		$sql = "SELECT COUNT(*) AS orderCnt, SUM(`order_amount`) AS orderAmount FROM `ecm_order` WHERE `pay_time` >=? AND `pay_time` <?  AND `type`='sgapp'";
		$list = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		if ($list) {
			$ret ['orderCnt'] = $list [0] ['orderCnt'] ? $list [0] ['orderCnt'] : 0;
			$ret ['orderAmount'] = $list [0] ['orderAmount'] ? $list [0] ['orderAmount'] : 0;
		}
		return $ret;
	}
	// 商品模型统计
	public function orderCnt() {
		/*
		 * array ( 'buyer' => 0, // 总活跃用户 'reBuyer' => 0, // 重复购买用户
		 * 'reBuyerGoods' => 0, // 重复购买件数 'preReBuyerGoods' => 0, // 重复购买用户下单件数
		 * 总UV 'order' => 0, // 总支付订单件数 'orderAmount' => 0, // 净营业收入 'preOrder'
		 * => 0 // 平均件单价 )
		 */
		$ret = array (
				'buyer' => 0,
				'reBuyer' => 0,
				'reBuyerGoods' => 0,
				'preReBuyerGoods' => 0,
				'order' => 0,
				'orderAmount' => 0,
				'preOrder' => 0 
		);
		
		$sql = "SELECT `order_id`, `buyer_id`, `order_amount` FROM `ecm_order` WHERE `pay_time`>=? AND `pay_time`<? AND `type`='sgapp'";
		$list = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$buyer = array ();
		if (! $list)
			return $ret;
		foreach ( $list as $row ) {
			$buyer [$row ['buyer_id']] = isset ( $buyer [$row ['buyer_id']] ) ? $buyer [$row ['buyer_id']] + 1 : 1;
		}
		if ($buyer) { // 处理重复购买
			$sql = "SELECT DISTINCT `buyer_id` FROM `ecm_order` WHERE `pay_time`<? AND `type`='sgapp' AND `buyer_id` IN(" . implode ( ',', array_keys ( $buyer ) ) . ")";
			if ($old = $this->db->getCol ( $sql, array (
					$this->start 
			) )) {
				foreach ( $old as $o ) {
					$buyer [$o] ++;
				}
			}
		}
		$ret ['buyer'] = count ( $buyer );
		foreach ( $buyer as $t ) {
			if ($t > 1)
				$ret ['reBuyer'] ++;
		}
		foreach ( $list as $row ) {
			$ret ['order'] ++;
			$ret ['orderAmount'] += $row ['order_amount'];
			if ($buyer [$row ['buyer_id']] > 1) // 收集重复购买用户订单
				$ids [] = $row ['order_id'];
		}
		
		if ($ids) {
			$sql = "SELECT SUM(`quantity`) FROM `ecm_order_goods` WHERE `order_id` IN(" . implode ( ',', $ids ) . ")";
			$ret ['reBuyerGoods'] = $this->db->getOne ( $sql );
			$ret ['preReBuyerGoods'] = round ( ($ret ['reBuyerGoods'] / $ret ['reBuyer']), 2 );
		}
		if ($ret ['order']) {
			$ret ['preOrder'] = round ( $ret ['orderAmount'] / $ret ['order'], 2 );
		}
		
		return $ret;
	}
	
	/**
	 * 统计阶段内用户uv
	 *
	 * @return mixed
	 */
	public function uvCnt() {
		$sql = "SELECT COUNT( DISTINCT (`user_id`) ) AS user_count FROM `ymall_activity_sgapp_user_actions` WHERE (`action` = 'fav_to_detail' OR `action` = 'today_to_detail' OR `action` = 'sg_to_detail' OR `action` = 'lost_to_detail') AND `add_time` >=? AND `add_time` < ?";
		return $this->db->getOne ( $sql, array (
				$this->start,
				$this->end 
		) );
	}
	
	/**
	 * APP活动用户行为统计
	 */
	public function getUserActionsCnt() {
		$userActionsCnt = array ();
		// 详情页单向翻看页数的行为的总量
		$sql = "SELECT COUNT(*) AS mx FROM `ymall_activity_sgapp_user_actions` 
				WHERE (`action` = 'fav_to_detail' OR `action` = 'today_to_detail' OR `action` = 'sg_to_detail' OR `action` = 'lost_to_detail') AND `add_time` >=? AND `add_time` < ? ";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntGoodsInfo'] ['mx'] = ( int ) $result [0] ['mx'];
		// 商品总量
		$sql = "SELECT COUNT(DISTINCT(goods_id)) AS goods_count  
					FROM `ymall_activity_sgapp_user_actions` 
					WHERE (`action` = 'fav_to_detail' OR `action` = 'today_to_detail' OR `action` = 'sg_to_detail' OR `action` = 'lost_to_detail') AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntGoodsInfo'] ['goods_count'] = ( int ) $result [0] ['goods_count'];
		// 人数
		$sql = "SELECT COUNT( DISTINCT (`user_id`) ) AS user_count FROM `ymall_activity_sgapp_user_actions` WHERE (`action` = 'fav_to_detail' OR `action` = 'today_to_detail' OR `action` = 'sg_to_detail' OR `action` = 'lost_to_detail') AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntGoodsInfo'] ['user_count'] = ( int ) $result [0] ['user_count'];
		// 比率，详情页平均翻看次数：详情页单向翻看页数总量\商品总量\人次
		$userActionsCnt ['cntGoodsInfo'] ['view_count'] = ($userActionsCnt ['cntGoodsInfo'] ["mx"] > 0 && $userActionsCnt ['cntGoodsInfo'] ["goods_count"] > 0 && $userActionsCnt ['cntGoodsInfo'] ["user_count"] > 0) ? round ( ($userActionsCnt ['cntGoodsInfo'] ["mx"] / $userActionsCnt ['cntGoodsInfo'] ["goods_count"] / $userActionsCnt ['cntGoodsInfo'] ["user_count"]), 2 ) : 0;
		// 收藏的商品总数
		$sql = "SELECT COUNT(wg.`wg_id`) AS goods_count FROM `ymall_wish_goods` AS wg 
				INNER JOIN `ymall_wish` AS w 
				ON w.`wish_id`=wg.`wish_id` 
				WHERE wg.`add_time`>=? AND wg.`add_time`<? AND wg.`is_delete`=?  
				AND w.`user_id` IN (SELECT `uid` FROM `ym_user_ref` WHERE `source`=7)";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end,
				0 
		) );
		$userActionsCnt ['cntSgAppGoodsInfo'] ['goods_count'] = ( int ) $result [0] ['goods_count'];
		// 所有的被收藏商品总数
		$sql = "SELECT COUNT(wg.`wg_id`) AS all_goods_count FROM `ymall_wish_goods` AS wg
				INNER JOIN `ymall_wish` AS w
				ON w.`wish_id`=wg.`wish_id`
				WHERE wg.`is_delete`=? AND w.`user_id` IN (SELECT `uid` FROM `ym_user_ref` WHERE `source`=7)";
		$result = $this->db->getRows ( $sql, array (
				0 
		) );
		$userActionsCnt ['cntSgAppGoodsInfo'] ['all_goods_count'] = ( int ) $result [0] ['all_goods_count'];
		// 至少收藏一个的用户总数
		$sql = "SELECT COUNT(DISTINCT(w.`user_id`)) AS user_count FROM `ymall_wish_goods` AS wg 
				INNER JOIN `ymall_wish` AS w 
				ON w.`wish_id`=wg.`wish_id` 
				WHERE wg.`add_time`>=? AND wg.`add_time`<? AND wg.`is_delete`=? 
				AND w.`user_id` IN (SELECT `uid` FROM `ym_user_ref` WHERE `source`=7)";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end,
				0 
		) );
		$userActionsCnt ['cntSgAppGoodsInfo'] ["user_count"] = ( int ) $result [0] ['user_count'];
		// 所有的至少收藏一个的用户总数
		$sql = "SELECT COUNT(DISTINCT(w.`user_id`)) AS all_user_count FROM `ymall_wish_goods` AS wg
				INNER JOIN `ymall_wish` AS w 
				ON w.`wish_id`=wg.`wish_id`
				WHERE wg.`is_delete`=? AND w.`user_id` IN (SELECT `uid` FROM `ym_user_ref` WHERE `source`=7)";
		$result = $this->db->getRows ( $sql, array (
				0 
		) );
		$userActionsCnt ['cntSgAppGoodsInfo'] ["all_user_count"] = ( int ) $result [0] ['all_user_count'];
		// 比率：收藏总数\至少收藏一个的用户总数
		$userActionsCnt ['cntSgAppGoodsInfo'] ["fav_count"] = ($userActionsCnt ['cntSgAppGoodsInfo'] ["goods_count"] > 0 && $userActionsCnt ['cntSgAppGoodsInfo'] ["user_count"] > 0) ? round ( ($userActionsCnt ['cntSgAppGoodsInfo'] ["goods_count"] / $userActionsCnt ['cntSgAppGoodsInfo'] ["user_count"]), 2 ) : 0;
		// 总比率：总收藏总数\总的至少收藏一个的用户总数
		$userActionsCnt ['cntSgAppGoodsInfo'] ["all_fav_count"] = ($userActionsCnt ['cntSgAppGoodsInfo'] ["all_goods_count"] > 0 && $userActionsCnt ['cntSgAppGoodsInfo'] ["all_user_count"] > 0) ? round ( ($userActionsCnt ['cntSgAppGoodsInfo'] ["all_goods_count"] / $userActionsCnt ['cntSgAppGoodsInfo'] ["all_user_count"]), 2 ) : 0;
		// 由今日进入详情的总数
		$sql = "SELECT COUNT(*) AS from_today FROM `ymall_activity_sgapp_user_actions` WHERE `action`='today_to_detail' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["from_today"] = ( int ) $result [0] ['from_today'];
		// 由闪购进入详情的总数
		$sql = "SELECT COUNT(*) AS from_sg FROM `ymall_activity_sgapp_user_actions` WHERE `action`='sg_to_detail' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["from_sg"] = ( int ) $result [0] ['from_sg'];
		// 由转身进入详情的总数
		$sql = "SELECT COUNT(*) AS from_lost FROM `ymall_activity_sgapp_user_actions` WHERE `action`='lost_to_detail' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["from_lost"] = ( int ) $result [0] ['from_lost'];
		// 由心盒进入详情的总数
		$sql = "SELECT COUNT(*) AS from_fav FROM `ymall_activity_sgapp_user_actions` WHERE `action`='fav_to_detail' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["from_fav"] = ( int ) $result [0] ['from_fav'];
		// 直接买单的总数
		$sql = "SELECT COUNT(*) AS buy FROM `ymall_activity_sgapp_user_actions` WHERE `action`='try_pay' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["buy"] = ( int ) $result [0] ['buy'];
		// 点击支付宝按钮的总数
		$sql = "SELECT COUNT(*) AS buy_pay FROM `ymall_activity_sgapp_user_actions` WHERE `action`='try_pay' AND `result`=? AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				1,
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["buy_pay"] = ( int ) $result [0] ['buy_pay'];
		// 支付成功总数
		$sql = "SELECT COUNT(*) AS pay_success FROM `ymall_activity_sgapp_user_actions` WHERE `action`='pay' AND `result`=? AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				1,
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["pay_success"] = ( int ) $result [0] ['pay_success'];
		// 支付失败总数
		$sql = "SELECT COUNT(*) AS pay_fail FROM `ymall_activity_sgapp_user_actions` WHERE `action`='pay' AND `result`=? AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				0,
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["pay_fail"] = ( int ) $result [0] ['pay_fail'];
		// 直接买单的概率--点击支付按钮总次数/进入买单页的总次数
		$userActionsCnt ['cntSgApp'] ["buy_pro1"] = ($userActionsCnt ['cntSgApp'] ["buy_pay"] > 0 && $userActionsCnt ['cntSgApp'] ["buy"] > 0) ? round ( ($userActionsCnt ['cntSgApp'] ["buy_pay"] / $userActionsCnt ['cntSgApp'] ["buy"]), 2 ) : 0;
		// 支付成功量/未支付成功量
		$userActionsCnt ['cntSgApp'] ["buy_pro3"] = ($userActionsCnt ['cntSgApp'] ["pay_success"] > 0 && $userActionsCnt ['cntSgApp'] ["pay_fail"] > 0) ? round ( ($userActionsCnt ['cntSgApp'] ["pay_success"] / $userActionsCnt ['cntSgApp'] ["pay_fail"]), 2 ) : 0;
		// 用户总量
		$sql = "SELECT COUNT(DISTINCT(`uid`)) AS user_count FROM `ym_user_ref` WHERE `source`=?";
		$result = $this->db->getRows ( $sql, array (
				7 
		) );
		$userActionsCnt ['cntSgApp'] ["user_count"] = ( int ) $result [0] ['user_count'];
		// PUSH的点击总量
		$sql = "SELECT COUNT(*) AS push_click FROM `ymall_activity_sgapp_user_actions` WHERE `action`='launch_from_msg' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["push_click"] = ( int ) $result [0] ['push_click'];
		// 规则页的点击总量
		$sql = "SELECT COUNT(*) AS rule FROM `ymall_activity_sgapp_user_actions` WHERE `action`='to_rule' AND `add_time` >=? AND `add_time` < ?";
		$result = $this->db->getRows ( $sql, array (
				$this->start,
				$this->end 
		) );
		$userActionsCnt ['cntSgApp'] ["rule"] = ( int ) $result [0] ['rule'];
		// PUSH的点击总量/用户总数
		$userActionsCnt ['cntSgApp'] ["push_pro1"] = ($userActionsCnt ['cntSgApp'] ["push_click"] > 0 && $userActionsCnt ['cntSgApp'] ["user_count"] > 0) ? round ( ($userActionsCnt ['cntSgApp'] ["push_click"] / $userActionsCnt ['cntSgApp'] ["user_count"]), 2 ) : 0;
		// 规则页点击总数/用户总数：
		$userActionsCnt ['cntSgApp'] ["rule_pro"] = ($userActionsCnt ['cntSgApp'] ["rule"] > 0 && $userActionsCnt ['cntSgApp'] ["user_count"] > 0) ? round ( ($userActionsCnt ['cntSgApp'] ["rule"] / $userActionsCnt ['cntSgApp'] ["user_count"]), 2 ) : 0;
		//
		return $userActionsCnt;
	}
	/**
	 * 获取参加活动的商品
	 */
	function get_app_goods($goods_ids = array(), $pid = 0) {
		if ((count ( $goods_ids ) > 0) || ($pid > 0)) {
			$sql = "SELECT * FROM `ymall_activity_sgapp_goods` WHERE 1 ";
			if ($pid > 0) {
				$sql .= " AND `pid` = " . $pid;
			}
			if (count ( $goods_ids ) > 0) {
				$sql .= " AND `goods_id` IN (" . implode ( ',', $goods_ids ) . ")";
			}
			return $this->db->getRows ( $sql );
		} else {
			return array ();
		}
	}
	/**
	 * 获取商品加入心盒数量
	 */
	public function fn_goods($goods_ids) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT a.`goods_id` , a.`goods_name` , count(b.`wg_id`) AS wishCnt 
				FROM `ymall_wish_goods` AS b 
				INNER JOIN `ecm_goods` AS a ON a.`goods_id` = b.`goods_id` 
				WHERE b.`is_delete` = 0 AND b.`goods_id` 
				IN (" . implode ( ',', $goods_ids ) . ")  
				GROUP BY b.`goods_id` 
				ORDER BY b.`goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		$ret = array ();
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		return $ret;
	}
	/**
	 * 获取商品的库存
	 */
	public function fn_goods_wishCnt($goods_ids) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT `goods_id`, `sku_inventory` FROM `ecm_goods` WHERE `goods_id`
				IN (" . implode ( ',', $goods_ids ) . ") 
				ORDER BY `goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		$ret = array ();
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		return $ret;
	}
	/**
	 * 获取商品的点击购买按钮的PV数
	 */
	public function fn_goods_clickBuyButtonPV($goods_ids) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT `goods_id`, COUNT(*) AS clickBuyButtonPV FROM `ymall_activity_sgapp_user_actions` WHERE `action`='clickBuyButton' AND `goods_id`
				IN (" . implode ( ',', $goods_ids ) . ") 
				GROUP BY `goods_id`  
				ORDER BY `goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		$ret = array ();
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		return $ret;
	}
	/**
	 * 获取商品的点击购买按钮的UV数
	 */
	public function fn_goods_clickBuyButtonUV($goods_ids) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT `goods_id`, COUNT(DISTINCT(`user_id`)) AS clickBuyButtonUV FROM `ymall_activity_sgapp_user_actions` WHERE `action`='clickBuyButton' AND `goods_id`
				IN (" . implode ( ',', $goods_ids ) . ") 
				GROUP BY `goods_id` 
				ORDER BY `goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		$ret = array ();
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		return $ret;
	}
	/**
	 * 获取商品的被分享数
	 */
	public function fn_goods_shareCnt($goods_ids) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT `goods_id`, COUNT(*) AS shareCnt FROM `ymall_activity_sgapp_user_actions` WHERE `action`='share' AND `goods_id`
				IN (" . implode ( ',', $goods_ids ) . ")
				GROUP BY `goods_id`
				ORDER BY `goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		$ret = array ();
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		return $ret;
	}
	
	/**
	 * 获取商品订单数量 参数为true时，获取商品已支付订单数量及销售额
	 */
	public function fn_goods_order($goods_ids, $is_pay = false) {
		if (! $goods_ids)
			return array ();
		$sql = "SELECT og.`goods_id`,SUM(o.`order_amount`) AS amount,COUNT(*) AS num 
				FROM `ecm_order_goods` AS og 
				INNER JOIN `ecm_order` AS o ON og.`order_id` = o.`order_id` 
				WHERE og.`goods_id` IN(" . implode ( ',', $goods_ids ) . ") AND o.`type`='sgapp'";
		if ($is_pay) {
			$sql .= " AND o.`order_status` IN(10,11,20,100,101,102)";
		}
		$sql .= " GROUP BY og.`goods_id` ASC";
		$list = $this->db->getRows ( $sql );
		foreach ( $list as $row ) {
			$ret [$row ['goods_id']] = $row;
		}
		
		return $ret;
	}
}


