<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service;
use \app\dao\OrderDao;
use \app\dao\OrderGoodsDao;
use \app\dao\OrderExtmDao;
use \app\dao\OrderLogDao;
use \app\dao\GoodsDao;
use \app\dao\AddressDao;
use \app\dao\UserDao;
use \app\dao\UserInfoDao;
use \app\dao\StoreDao;
use \app\dao\GoodsStatisticsDao;
use \app\dao\UserCouponDao;
use sprite\lib\Debug;


class OrderSrv extends BaseSrv {

    const UNPAY_ORDER = 10; //待付款
    const PAYED_ORDER = 11; //待发货
    const SHIPPING_ORDER = 12; //已发货
    const RECEIVED_ORDER = 13; //已收货
    const FINISHED_ORDER = 14; //已完成
    const CLOSED_ORDER = 100; //关闭订单

    /**
     * @param $post
     * @throws \Exception
     * @desc 创建订单
     */
    public function submit($post) {
        try{
            $user = self::checkBuyer($post['user_id']);                 //用户状态
            $address = $post['address'];
            if($post['addr_id']) {
                AddressDao::getMasterInstance()->edit($post['addr_id'], $address);
            }
            else{
                AddressDao::getMasterInstance()->add($address);
            }

            $goods = self::checkGoods($post['goods_id'], $post['quantity']);    //核对商品状态
            $store = self::checkStore($goods['store_id']);    //核对商家状态

            $goods_amount = $post['quantity'] * $goods['price'];
            $ship_fee = self::getShipFee($address, $post['quantity'], $goods['freight_template_id']);
            $_time = time();

            $order = array(
                'order_sn'=>self::makeSn(),
                'buyer_id'=>$user['user_id'],
                'buyer_name'=>$user['user_name'],
                'seller_id'=>$goods['store_id'],
                'seller_name'=>$store['store_name'],
                'type'=>$post['type'],
                'payment_code'=>$post['payment_code'],
                'payment_name'=>$post['payment_name'],
                'goods_amount'=>$goods_amount,
                'order_amount'=>$goods_amount + $ship_fee,
                'shipping_fee'=>$ship_fee,
                'postscript'=>$post['postscript'],
                'auto_closed_time'=>2,
                'order_status'=>self::UNPAY_ORDER,
                'order_time'=>$_time,
                'add_time'=>$_time,
            );

            $order_goods = array(
                'goods_id'=>$goods['goods_id'],
                'goods_name'=>$goods['goods_name'],
                'price'=>$goods['price'],
                'cost_price'=>$goods['cost_price'],
                'cate_id'=>$goods['cate_id'],
                'cate_id_1'=>$goods['cate_id_1'],
                'cate_id_2'=>$goods['cate_id_2'],
                'market_price'=>$goods['market_price'],
                'quantity'=>$post['quantity'],
                'sku'=>$goods['sku'],
                'erp_id'=>$goods['erp_id'],
                'goods_image'=>$goods['default_thumb'] ? $goods['default_thumb'] : $goods['default_image'],
            );

            if($post['ucpn_id']) {//优惠券
                $coupon = self::checkCoupon($post['ucpn_id'], $order);    //核对优惠券状态
                $order['order_amount'] = $order['order_amount'] - $coupon['amount'];
                $order['discount_type'] = 1;
                $order['discount_itemid'] = $post['ucpn_id'];
                $order['discount_amount'] = $coupon['amount'];
            }
            if($order['order_amount'] <= 0)//订单小于0 订单自动设定成0.01
                $order['order_amount'] = 0.01;

            try{
                OrderDao::getMasterInstance()->beginTransaction();//开启事务

                //先占用库存
                GoodsDao::getMasterInstance()->stock($goods['goods_id'], $post['quantity'], '-');
                GoodsStatisticsDao::getMasterInstance()->increment($goods['goods_id'],'orders', 1);

                $order_id = OrderDao::getMasterInstance()->add($order);//生成订单

                $order_goods['order_id'] = $order_id;
                OrderGoodsDao::getMasterInstance()->add($order_goods);//生成订单商品
                $address['order_id'] = $order_id;
                OrderExtmDao::getMasterInstance()->add($address); //生成订单地址

                if($order['discount_type'] == 1 && $order['discount_itemid'] > 0) { //优惠券调整
                    UserCouponDao::getMasterInstance()->edit($order['discount_itemid'],
                        array('state'=>2, 'order_id'=>$order_id, 'goods_amount'=>$order['goods_amount'], 'order_amount'=>$order['order_amount'], 'utime'=>$_time));
                }

                UserInfoDao::getMasterInstance()->increment($order['buyer_id'], 'orders', 1);//增加用户统计数据

                //生成log
                \app\dao\OrderlogDao::getMasterInstance()->add(
                    array(//order_id,operator,order_status,changed_status,remark,log_time,
                        'order_id'=>$order_id,
                        'operator'=>$user['user_name'],
                        'order_status'=>'待付款',
                        'changed_status'=>'待付款',
                        'remark'=>'创建订单',
                        'log_time'=>$_time,
                    )
                );

                OrderDao::getMasterInstance()->commit();

            }catch (\Exception $e) {
                OrderDao::getMasterInstance()->rollBack();
                throw $e;
            }

            return array('order_id'=>$order_id, 'order_sn'=>$order['order_sn'], 'order_amount'=>$order['order_amount']);

        }catch(\Exception $e) {
            throw $e;
        }
    }


    /**
     * @param $order_id
     * @desc 取消订单
     */
    public function cancel($order_id, $user_id, $remark = '') {
        $user = UserDao::getSlaveInstance()->find($user_id);
        $order = OrderDao::getSlaveInstance()->find($order_id);
        if(!$order || !$user)
            throw new \Exception('参数错误', 50000);

        self::checkOperateAuth($order, $user);

        if(self::CLOSED_ORDER == $order['order_status'])
            throw new \Exception('订单状态无需处理', 50002);

        $_time = time();
        OrderDao::getMasterInstance()->edit($order_id, array('order_status'=>self::CLOSED_ORDER, 'order_time'=>$_time, 'closed_time'=>$_time));

        if(in_array($order['order_status'], array(self::PAYED_ORDER,self::SHIPPING_ORDER, self::RECEIVED_ORDER, self::FINISHED_ORDER) )) {
            UserInfoDao::getMasterInstance()->decrement($order['buyer_id'], 'sales', 1);//取消订单数

            if($order['discount_type'] == 1 && $order['discount_itemid'] > 0) { //优惠券调整
                UserCouponDao::getMasterInstance()->edit($order['discount_itemid'],
                    array('state'=>2, 'utime'=>$_time));
            }
        }

        //生成log order_time，order_status，closed_time
        \app\dao\OrderlogDao::getMasterInstance()->add(
            array(//order_id,operator,order_status,changed_status,remark,log_time,
                'order_id'=>$order_id,
                'operator'=>$user['user_name'],
                'order_status'=>self::getStatus($order['order_status']),
                'changed_status'=>self::getStatus(self::CLOSED_ORDER),
                'remark'=>$remark,
                'log_time'=>$_time,
            )
        );
    }

    /**
     * @param $info
     * @desc 支付订单
     */
    public function pay($info) {
        //订单支付
      
        $orderDao = OrderDao::getMasterInstance();
        $order = $orderDao->find(array('order_sn'=>$info['order_sn']));

        if( !$order || !in_array( $order['order_status'], array( self::UNPAY_ORDER, self::CLOSED_ORDER ) ) ) //开放关闭订单可以支付
            throw new \Exception('订单不存在或状态不正确', 5001);

        if($info['total_fee'] != $order['order_amount'])
            throw new \Exception('订单支付金额不正确', 5001);

        try{
            $orderDao->beginTransaction();//开启事务
            $_time = time();
            $orderDao->edit($order['order_id'], array(
                'order_status'=>self::PAYED_ORDER,
                'order_time'=>$_time,
                'pay_time'=>$_time,
                'payment_name'=>$info['payment_name'],
                'payment_code'=>$info['payment_code'],
                'out_trade_sn'=>$info['out_trade_sn'],
            ));

            $order_goods = OrderGoodsDao::getSlaveInstance()->findByField('order_id', $order['order_id']);
            foreach($order_goods as $goods) {//increment($id, $filed, $num)
                GoodsStatisticsDao::getMasterInstance()->increment($goods['goods_id'], 'sales', $goods['quantity']);
            }
            //用户订单统计数据更新
            UserInfoDao::getMasterInstance()->increment($order['buyer_id'], 'sales', 1);

            if($order['discount_type'] == 1 && $order['discount_itemid'] > 0) { //优惠券调整
                UserCouponDao::getMasterInstance()->edit($order['discount_itemid'],
                    array('state'=>3, 'utime'=>$_time));
            }

            //生成log
            \app\dao\OrderlogDao::getMasterInstance()->add(
                array(
                    'order_id'=>$order['order_id'],
                    'operator'=>'第三方支付：' . $info['payment_name'],
                    'order_status'=>self::getStatus($order['order_status']),
                    'changed_status'=>self::getStatus(self::PAYED_ORDER),
                    'remark'=>'第三方支付：' . $info['payment_name'] . '支付',
                    'log_time'=>$_time,
                )
            );

           $orderDao->commit();//提交事务

        }catch (\Exception $e) {
            OrderDao::getMasterInstance()->rollBack();
            echo $e;
        }
    }

    /**
     * @param $order_id
     * @desc 订单配送
     */
    public function ship($data) {
        //$user = \app\dao\UserDao::getSlaveInstance()->find($user_id);
        $order = OrderDao::getSlaveInstance()->find($data['order_id']);
        if(!$order)
            throw new \Exception('参数错误', 50000);

        if( $order['order_status'] != self::PAYED_ORDER )
            throw new \Exception('未付款订单不允许发货', 50002);

        $_time = time();
        OrderDao::getMasterInstance()->edit($data['order_id'], array('shipping_code'=>$data['shipping_code'],'shipping_name'=>$data['shipping_name'],'order_status'=>self::SHIPPING_ORDER, 'order_time'=>$_time, 'ship_time'=>$_time));

        //生成log
        \app\dao\OrderlogDao::getMasterInstance()->add(
            array(
                'order_id'=>$data['order_id'],
                'operator'=>$data['user_name'],
                'order_status'=>self::getStatus($order['order_status']),
                'changed_status'=>self::getStatus(self::SHIPPING_ORDER),
                'remark'=>'商家发货',
                'log_time'=>$_time,
            )
        );
    }

    /**
     * @param $order_id
     * @desc 订单收货
     * @delete by wanjilong@yoka.com
     */
    public function receive($order_id, $user_id) {
        $user = \app\dao\UserDao::getSlaveInstance()->find($user_id);
        $order = OrderDao::getSlaveInstance()->find($order_id);
        if(!$order || !$user)
            throw new \Exception('参数错误', 50000);

        self::checkOperateAuth($order, $user);

        if($order['order_status'] != self::SHIPPING_ORDER)
            throw new \Exception('未发货订单不允许直接收货', 50001);

        $_time = time();
        OrderDao::getMasterInstance()->edit($order_id, array('order_status'=>self::RECEIVED_ORDER, 'order_time'=>$_time, 'receive_time'=>$_time));

        //生成log
        \app\dao\OrderlogDao::getMasterInstance()->add(
            array(
                'order_id'=>$order_id,
                'operator'=>$user['user_name'],
                'order_status'=>self::getStatus($order['order_status']),
                'changed_status'=>self::getStatus(self::RECEIVED_ORDER),
                'remark'=>$user['user_name'] . '收货',
                'log_time'=>$_time,
            )
        );
    }

    /**
     * @param $order_id
     * @desc 订单完成
     */
    public function finished($order_id, $user_id) {
        $user = UserDao::getSlaveInstance()->find($user_id);
        $order = OrderDao::getSlaveInstance()->find($order_id);
        if(!$order || !$user)
            throw new \Exception('参数错误', 50000);

        self::checkOperateAuth($order, $user);

        if( $order['order_status'] != self::RECEIVED_ORDER  && $order['order_status'] != self::SHIPPING_ORDER )
            throw new \Exception('未发货订单不允许直接收货', 50001);

        $_time = time();
        OrderDao::getMasterInstance()->edit($order_id, array('order_status'=>self::FINISHED_ORDER, 'order_time'=>$_time, 'finished_time'=>$_time));

        //生成log
        \app\dao\OrderlogDao::getMasterInstance()->add(
            array(
                'order_id'=>$order_id,
                'operator'=>$user['user_name'],
                'order_status'=>self::getStatus($order['order_status']),
                'changed_status'=>self::getStatus(self::FINISHED_ORDER),
                'remark'=>$user['user_name'] . '订单完成',
                'log_time'=>$_time,
            )
        );
    }

    /**
     * @param $order_id
     * @return array
     * @throws \Exception
     * @desc 返回订单信息
     */
    public function info($order_id, $key = 'order_id') {
        if($key == 'order_sn') {
            $order = OrderDao::getSlaveInstance()->find( array('order_sn'=>$order_id) );
        }
        else {
            $order = OrderDao::getSlaveInstance()->find($order_id);
        }


        if(!$order)
            throw new \Exception('订单不存在', 50000);

        $order['order_status_txt'] = self::getStatus($order['order_status']);
        $order['order_time_txt'] = self::getStatusTimeAlt($order['order_status']);

        $order['can_refund'] = false;
        if($order['refund_status'] == 0) {
            $_time = time();
            if($order['order_status'] == self::PAYED_ORDER && $_time - $order['pay_time'] > 12 * 60 * 60)
                $order['can_refund'] = true;

            if($order['order_status'] == self::SHIPPING_ORDER || $order['order_status'] == self::RECEIVED_ORDER)
                $order['can_refund'] = true;

            /**
             * bug fix by daniel: 发货10天内能退货
             */    
            if($order['order_status'] == self::FINISHED_ORDER && $_time - $order['ship_time'] < 10 * 24 * 60 * 60)
                $order['can_refund'] = true;

        }

        if($order['discount_type'] == 1 && $order['discount_itemid']> 0) {
            $order['coupon'] = UserCouponDao::getSlaveInstance()->getInfo( $order['discount_itemid'] );
        }

        $order['refund_status_txt'] = \app\dao\RefundDao::getStatusTxt($order['refund_status']);

        $order['goods'] = OrderGoodsDao::getSlaveInstance()->find(array('order_id'=>$order['order_id']));
        $order['address'] = OrderExtmDao::getSlaveInstance()->find(array('order_id'=>$order['order_id']));
        $order['goods']['goods_image'] = CDN_YMALL . $order['goods']['goods_image'];
        return $order;
    }

    /**
     * @param $buyer_id
     * @return array
     * @desc 返回订单列表
     */
    public function orders($buyer_id, $status = 0, $limit = '0, 20') {
        $ret = array();
        $ret['count'] = OrderDao::getSlaveInstance()->getOrderCnt($buyer_id, $status);
        $ret['list'] = array();

        if($ret['count'] > 0) {
            $list = OrderDao::getSlaveInstance()->getBuyerAll($buyer_id, $status, $limit);
            if($list) {
                foreach($list as $k=>$v) {
                    $order = array(
                        'order_id'=>$v['order_id'],
                        'order_sn'=>$v['order_sn'],
                        'order_status_txt'=>self::getStatus($v['order_status']),
                        'order_status_alert'=>self::getStatusAlt($v['order_status']),
                        'order_status'=>$v['order_status'],
                        'goods_id'=>$v['goods_id'],
                        'goods_name'=>$v['goods_name'],
                        'payment_code'=>$v['payment_code'],
                        'order_amount'=>$v['order_amount'],
                        'goods_amount'=>$v['goods_amount'],
                        'price'=>$v['price'],
                        'quantity'=>$v['quantity'],
                        'shipping_code'=>$v['shipping_code'],
                        'shipping_name'=>$v['shipping_name'],
                        'goods_image'=>CDN_YMALL . $v['goods_image'],
                    );

                    if( $order['order_status'] == self::PAYED_ORDER && (time() - $v['order_time'] > 1200) ) {//20分钟后自动更换
                        $order['order_status_alert'] = '您的订单已在处理发货';
                    }
                    if($order['order_status'] == self::SHIPPING_ORDER)
                        $order['order_status_alert'] = str_replace(
                            array('#SHIPPING_NAME#', '#SHIPPING_CODE#'),
                            array($order['shipping_name'], $order['shipping_code']),
                            $order['order_status_alert']
                        );

                    $ret['list'][] = $order;
                }
            }
        }
        return $ret;
    }

    /**
     * @param $user_id
     * @param $goods_id
     * @param $quantity
     * @return array
     * @throws \Exception
     * @desc 生成订单确认页接口
     */
    public function preOrder($user_id, $goods_id, $quantity) {
        try{
            $user = self::checkBuyer($user_id);                 //用户状态
            $goods = self::checkGoods($goods_id, $quantity);    //核对商品状态
            $goods['quantity'] = $quantity;

            $address = AddressDao::getSlaveInstance()->getDefault($user_id);
            $ship_fee = self::getShipFee($address, $quantity, $goods['freight_template_id']);

            $goods['default_thumb'] = CDN_YMALL . $goods['default_thumb'];
            $goods['default_image'] = CDN_YMALL . $goods['default_image'];

            $ret = array(
                'user_id'=>$user['user_id'],
                'user_name'=>$user['user_name'],
                'goods_amount'=>$quantity * $goods['price'],
                'order_amount'=>$quantity * $goods['price'] + $ship_fee,
                'goods'=>$goods,
                'address'=>$address ? $address : null,
            );

            $ret['coupon'] = self::coupon($user_id, $ret);

            return $ret;
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

    public function coupon($user_id, $order) {
        $OrderCouponSrv = new \app\service\coupon\OrderCouponSrv();
        return $OrderCouponSrv->getList($user_id);
    }

    public function unpayCnt($user_id) {
        return OrderDao::getSlaveInstance()->getOrderCnt($user_id, self::UNPAY_ORDER);
    }

    /**
     * @param $address
     * @param $quantity
     * @param $freight_template_id
     * @return int
     * @desc 运费费用计算
     */
    public function getShipFee($address, $quantity, $freight_template_id) {
        return 0;
    }

    public function getStatus($status) {
        $values = array(
            self::UNPAY_ORDER =>'待付款',
            self::PAYED_ORDER =>'待发货',
            self::SHIPPING_ORDER =>'已发货',
            #self::RECEIVED_ORDER =>'已收货',
            self::FINISHED_ORDER =>'已完成',
            self::CLOSED_ORDER =>'已取消',
        );
        return isset($values[$status]) ? $values[$status] : "未知状态";
    }

    public function getStatusTimeAlt($status) {
        $values = array(
            self::UNPAY_ORDER =>'订单时间：',
            self::PAYED_ORDER =>'付款时间：',
            self::SHIPPING_ORDER =>'发货时间：',
            #self::RECEIVED_ORDER =>'收货时间：',
            self::FINISHED_ORDER =>'完成时间：',
            self::CLOSED_ORDER =>'取消时间：',
        );
        return isset($values[$status]) ? $values[$status] : "订单时间";
    }

    public function getStatusAlt($status) {
        $values = array(
            self::UNPAY_ORDER =>'48小时内未支付，订单将自动取消',
            self::PAYED_ORDER =>'正在进行发货准备，24小时内发出',
            self::SHIPPING_ORDER =>'#SHIPPING_NAME#快递单号：#SHIPPING_CODE#',
            #self::RECEIVED_ORDER =>'已经收货啦',
            self::FINISHED_ORDER =>'',
            self::CLOSED_ORDER =>'订单已经取消啦',
        );
        return isset($values[$status]) ? $values[$status] : "未知状态";
    }

    public function updateErpSn($order_sn, $erp_sn) {

    }



    /**
     * @param $user_id
     * @desc 核对操作权限，buyer_id, store_id, admin_id
     */
    private function checkOperateAuth($order, $user) {
        if( $order['buyer_id'] != $user['user_id']
                && $order['seller_id'] != $user['user_id']
                && ! self::isAdmin($user['user_id']) )
            throw new \Exception('非法操作，没有修改该订单权限', 5000);
    }

    private function isAdmin($user_id) {
        return \app\dao\AdminDao::getSlaveInstance()->find($user_id) ? true : false;
    }

    private function checkBuyer($user_id) {
        $user = UserInfoDao::getSlaveInstance()->find($user_id);
        if(!$user)
            throw new \Exception('用户非法或不存在'.$user_id,50001);

        return $user;
    }

    private function checkGoods($goods_id, $quantity = 1) {
        $goods = GoodsDao::getSlaveInstance()->find($goods_id);
        if(!$goods || $goods['status'] != GoodsDao::BUY_STATUS)
            throw new \Exception('您要的礼物已经穿越了~',50001);

        if($goods['stock'] < $quantity && $goods['stock'] > 0)
            throw new \Exception('哎呀，只有'.$goods['stock'].'个可买呦~',50003);

        if($goods['stock'] < $quantity)
            throw new \Exception('太火爆卖完了，可以设置“到货提醒”哦~',50002);

        try{
            $ecErpObj = new \app\service\ecerp\GoodsSrv();
            $_info = $ecErpObj->getGoodsStock($goods['erp_id'], $goods['sku']);

            if(isset($_info['stock']) &&  $_info['stock']== 0) {
                GoodsDao::getMasterInstance()->edit($goods_id, array('stock'=>0));
                throw new \Exception('太火爆卖完了，可以设置“到货提醒”哦~',50002);
            }
        }
        catch(\Exception $e) {
            if($e->getCode() != 40000)
                throw $e;
        }

        return $goods;
    }

    private function checkStore($store_id) {
        $store = StoreDao::getSlaveInstance()->find( array('store_id'=>$store_id, 'state'=>1) );
        if(!$store)
            throw new \Exception('商家店铺已经关闭',50001);

        return $store;
    }

    private function checkCoupon($cupn_id, $order) {
        $coupon = UserCouponDao::getSlaveInstance()->getInfo( $cupn_id );
        if(!$coupon || $coupon['state'] != 1 || $coupon['order_id'])
            throw new \Exception('您使用的优惠券已经过期了~' . $coupon['state'],50005);

        if($coupon['min_order_amount'] > $order['goods_amount'])
            throw new \Exception('订单满'.$coupon['min_order_amount'].'元，才能使用这张优惠券哦~',50006);

        if($coupon['amount'] >= $order['goods_amount']) {
            $coupon['amount'] = $order['goods_amount'];
        }
        return $coupon;
    }



    /**
     * @return string
     * @desc 取得一个order_sn
     */
    private function makeSn() {
        $timestamp = time();
        $y = date('y', $timestamp);
        $z = date('z', $timestamp);
        $order_sn = $y . str_pad($z, 3, '0', STR_PAD_LEFT) . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        if(OrderDao::getSlaveInstance()->find(array('order_sn'=>$order_sn))) {
            return self::makeSn();
        }
        return $order_sn;
    }
    
    /**
     * @param $where: 查询条件Array
     * @desc: 根据指定检索条件匹配出订单列表（不能处理时间范围）
     * @author: daniel
     */
    public function queryOrderList($where,$limit=0,$start_time=null,$end_time=null)
    {
    	
    	if($start_time == null || $end_time == null)  //非按时间查询
    		return OrderDao::getSlaveInstance()->findAll($where,$limit);
    	else
    	{
    		$sql = "select * from ym_order where (add_time between $start_time and $end_time)";
    
    		if(isset($where['order_status']))
    		{ 	
    			$sql = $sql . " and order_status ='".$where['order_status']."'";
    		}
    		$sql = $sql . " order by add_time desc ";
    		if($limit !=0)
    		$sql = $sql."limit $limit";
    		return OrderDao::getSlaveInstance()->getpdo()->getRows($sql);
    	}
    }
    
    /**
     * 查询单个订单的详情信息
     */
    public function queryOrderDetail($order_id)
    {
        //echo $order_id;
        $sql = "select * from ym_order_goods,ym_order_extm,ym_order where ym_order_goods.order_id=$order_id and ym_order_extm.order_id=$order_id and ym_order.order_id =$order_id";
        return OrderDao::getSlaveInstance()->getpdo()->getRow($sql);
    }

    function postErpOrder($order, $goods, $address) {
        $ecerpObj = new \app\service\ecerp\OrderSrv();
        try{
            $ret = $ecerpObj->submit($order, $goods, $address);
            if(isset($ret->TradeOrders->ERROR)) {
                throw new \Exception($ret->TradeOrders->ERROR, 50002);
            }
            if( isset($ret->TradeOrders->trade_orders_response->trade->tid) ) {
                OrderDao::getMasterInstance()->edit($order['order_id'], array('erp_sn'=>$ret->TradeOrders->trade_orders_response->trade->tid) );
            }
        }
        catch(\Exception $e) {
            //更新库存
            throw new \Exception('太火爆卖完了，可以设置“到货提醒”哦~',50002);
        }
    }
}