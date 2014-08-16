<?php

namespace app\service;

use \app\dao\OrderDao;
use \app\dao\OrderExtmDao;
use \app\dao\RefundDao;
use \app\dao\UserInfoDao;

/**
 * 退款服务
 *
 * @author daniel
 *        
 */
class RefundSrv extends BaseSrv {
	
	/**
	 * 申请退款
	 *
	 * @param array $data:
	 *        	neccessory segment:
	 *        	$data['card_no'] , $data['refund_desc'], $data['order_id'],
	 *        	$data['user_id']
	 * @throws \Exception
	 * @throws Exception
	 */
	public function apply($data) {
		if (! $data ['card_no'] || ! $data ['order_id'])
			throw new \Exception ( '请完善退款资料', '5000' );
		
		$order = OrderDao::getSlaveInstance ()->find ( $data ['order_id'] );
		$order_extm = OrderExtmDao::getSlaveInstance ()->find ( $data ['order_id'] );
		
		if (! $order || $order ['buyer_id'] != $data ['user_id'])
			throw new \Exception ( '只能对自己订单申请退款', '5001' );
		
		if ($order ['refund_status'] != 0)
			throw new \Exception ( '已经提交退款申请，请等待客服处理', '5001' );
		
		$data ['order_sn'] = $order ['order_sn'];
		$data ['seller_id'] = $order ['seller_id'];
		$data ['refund_status'] = RefundDao::REFUND_ACCEPT;
		$data ['user_id'] = $order ['buyer_id'];
		$data ['refund_money'] = $data ['order_amount'] = $order ['order_amount'];
		$data ['consignee'] = $order_extm ['consignee'];
		$data ['phone_mob'] = $order_extm ['phone_mob'];
		
		$data ['ctime'] = $order ['utime'] = time ();
		try {
			RefundDao::getMasterInstance ()->beginTransaction (); // 开启事务
			$id = RefundDao::getMasterInstance ()->add ( $data );
			OrderDao::getMasterInstance ()->edit ( $data ['order_id'], array (
					'refund_status' => $data ['refund_status'] 
			) );
			UserInfoDao::getMasterInstance ()->edit ( $data ['user_id'], array (
					'alipay_no' => $data ['card_no'] 
			) );
			RefundDao::getMasterInstance ()->commit ();
			return array (
					'refund_id' => $id 
			);
		} catch ( \Exception $e ) {
			RefundDao::getMasterInstance ()->rollBack ();
			throw $e;
		}
	}
}