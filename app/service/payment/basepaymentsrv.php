<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\payment;

use app\service\BaseSrv;

class BasePaymentSrv extends BaseSrv {

    var $_gateway   = '';
    /* 支付方式唯一标识 */
    var $_code      = '';
	
	public function __construct($payment_info = array()) {
        $this->_info   = $payment_info;
		$this->_config = unserialize($payment_info['config']);
    }

	public function getPayForm() {
        return $this->createPayForm('POST');
    }
	
	protected function createPayForm($method = '', $params = array()) {
        return array(
            'online'    =>  $this->_info['is_online'],
            'desc'      =>  $this->_info['payment_desc'],
            'method'    =>  $method,
            'gateway'   =>  $this->_gateway,
            'params'    =>  $params,
        );
    }
	
	/**
     *    获取通知地址
     */
    protected function createNotifyUrl($order_id, $from_goods=0) {
        return SITE_URL . "/index.php?app=paynotify&act=notify&order_id={$order_id}&from_goods={$from_goods}&type=".$_GET['type'];
    }

    /**
     *    获取返回地址
     */
    protected function createReturnUrl($order_id, $from_goods=0) {
        return SITE_URL . "/index.php?app=paynotify&order_id={$order_id}&from_goods={$from_goods}&type=".$_GET['type'];
    }
	
	/**
     *    获取外部交易号
     */
    protected function getTradeSn($order) {
        $out_trade_sn = $order['out_trade_sn'];
        if (!$out_trade_sn)
			OrderDao::getMasterInstance()->edit($order, array('out_trade_sn'=>out_trade_sn));

        return $out_trade_sn;
    }

    /**
     *    获取商品简介
     */
    protected function getSubject($order) {
        $list = \app\dao\OrderGoodsDao::getSlaveInstance()->findByField('order_id', $order['order_id']);
        if(count($list) == 1)
            return $list[0]['goods_name'];

        return 'Ymall礼物店订单：' . $order['order_sn'];
    }

    /**
     *    验证支付结果 
     */
    function verifyNotify() {
        #TODO
        return false;
    }

    /**
     *    将验证结果反馈给网关
     */
    function verifyResult($result) {
        if ($result) {
            echo 'success';
        }
        else {
            echo 'fail';
        }
    }    
}