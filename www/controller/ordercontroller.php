<?php 
namespace www\controller;

use app\dao\UserCouponDao;
use \app\service\GoodsSrv;
use \app\service\OrderSrv;
use \app\service\coupon\UserCouponSrv;
/*
 * product related behavior
 * @author : daniel
 */
class OrderController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);
        parent::checkLogin();
    }

    /**
     * @param $request
     * @param $response
     * @desc 传入商品id及数量，生成订单确认页面
     */
    public function index($request, $response) {
        self::userLog( array('type'=>'goods', 'action'=> 'buy', 'item_id'=>$request->post('goods_id', 0)));
        try{
            $goods_id = $request->post('goods_id');
            $quantity = $request->post('quantity');

            $user_id = $this->current_user['user_id'];
            $orderSrv = new OrderSrv();
            $order = $orderSrv->preOrder($user_id, $goods_id, $quantity);

            $this->result($order);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    public function unpayCnt($request, $response) {
        try{
            $user_id = $this->current_user['user_id'];
            $orderSrv = new OrderSrv();
            $cnt = $orderSrv->unpayCnt($user_id);

            $UserCouponSrv = new UserCouponSrv();
            $coupon_cnt = $UserCouponSrv->getListCnt(array('user_id'=>$user_id, 'state'=>'valid'));

            $this->result(array('count'=>$cnt, 'coupon_cnt'=>$coupon_cnt));
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $request
     * @param $response
     * @desc 根据商品id及数量，用户信息生成订单
     */
    public function submit($request, $response) {
        try{
            $post['goods_id'] = $request->post('goods_id');
            $post['quantity'] = $request->post('quantity', 1);
            $post['user_id'] = $this->current_user['user_id'];
            $post['payment_code'] = $request->post('payment_code', 'alipay');
            $post['type'] = 'app';

            $post['ucpn_id'] = $request->post('ucpn_id', 0);//优惠券ID

            $post['addr_id'] = $request->post('addr_id', 0);

            if(!$post['goods_id'])
                throw new \Exception('请传商品id', 50000);

            $post['address']['user_id'] = $post['user_id'];
            $post['address']['consignee'] = $request->post('consignee');
            $post['address']['region_id'] = $request->post('region_id');
            $post['address']['region_name'] = $request->post('region_name');
            $post['address']['address'] = $request->post('address');
            $post['address']['phone_mob'] = $request->post('phone_mob');

            self::checkAddress($post['address']);

            $orderSrv = new OrderSrv();
            $order = $orderSrv->submit($post);
            $this->result($order);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $request
     * @param $response
     * @throws \Exception
     * @desc 取得订单详细信息
     */
    public function info($request, $response) {
        $order_id = $request->get('order_id', 0);
        $user_id = $this->current_user['user_id'];

        try{
            $orderSrv = new OrderSrv();
            $order = $orderSrv->info($order_id);

            if($order['buyer_id'] != $user_id)
                throw new \Exception('只能操作自己的订单', 5000);

            $this->result($order);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $request
     * @param $response
     * @desc 返回买家列表
     */
    public function orders($request, $response) {
        try{
            $status_str = $request->get('status', 'all');
            $status = self::getStatus($status_str);
            $page = $request->page;
            $size = 20;

            $page = $page < 1 ? 1 : $page;
            $start = ($page - 1) * $size;
            $limit = " $start, $size";

            $orderSrv = new OrderSrv();
            $list = $orderSrv->orders($this->current_user['user_id'], $status, $limit);

            $list['pages'] = ceil($list['count'] / $size);
            $list['next'] = ($list['pages'] <= $page) ? false : true;
            $this->result($list);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    public function coupon($request, $response) {
        try{
            $goods_id = $request->post('goods_id');
            $quantity = $request->post('quantity');

            $user_id = $this->current_user['user_id'];
            $orderSrv = new OrderSrv();
            $order = $orderSrv->preOrder($user_id, $goods_id, $quantity);

            $this->result($order);
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    private function getStatus($key) {
        switch($key) {
            case 'unpay':
                $status = 10;
                break;
            case 'payed':
				$status = 11;
                break;
            case 'shipped':
                $status = 12;
                break;
            case 'finished':
                $status = 14;
                break;
            case 'closed':
                $status = 100;
                break;
            default:
                $status = 0;
        }
        return $status;
    }

    public function cancel($request, $response) {
        try{
            $order_id = $request->order_id;
            $orderSrv = new OrderSrv();
            $ret = $orderSrv->cancel($order_id, $this->current_user['user_id'], '买家取消订单');
            $this->result(array('order_id'=>$order_id, 'status'=>'ok'));
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    private function checkAddress($info) {
        if(!$info['consignee'] || !$info['phone_mob'] || !$info['address'] || !$info['region_name'])
            throw new \Exception('收货信息不完整，请完善', 5000);

        if(!preg_match('/1[0-9]{10}/', $info['phone_mob']))
            throw new \Exception('手机号码不正确', 5000);
    }
}
