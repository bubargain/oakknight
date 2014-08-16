<?php

namespace shop\controller;

use app\dao\OrderGoodsDao;
use \app\dao\RefundDao;
use \app\dao\OrderDao;
use \app\service\OrderSrv;
use app\common\util\SubPages;

class RefundController extends BaseController {

    public function __construct($request, $response) {
        parent::__construct($request, $response);
        $this->_store_id = $this->current_user['user_id'];
    }

	public function index($request, $response) {
        $params = array();
        $params['seller_id'] = 'seller_id ='.$this->_store_id;
        if($request->refund_status)
            $params['refund_status'] = 'refund_status ='.intval($request->refund_status);

        if($request->order_sn)
            $params['order_sn'] = 'order_sn =\''.$request->order_sn.'\'';

        if($request->start_time)
            $params['start_time'] = 'ctime >='.strtotime($request->start_time);

        if($request->end_time)
            $params['end_time'] = 'ctime <'.strtotime($request->end_time);

        if($request->user_id)
            $params['user_id'] = 'user_id ='.intval($request->user_id);

        if($request->phone_mob)
            $params['phone_mob'] = 'phone_mob =\''.$request->phone_mob.'\'';

        if($request->consignee)
            $params['consignee'] = 'consignee like \'%'.$request->consignee.'%\'';


        $_curr_page = $request->get("page", 1);

        $total = RefundDao::getSlaveInstance()->getListCnt($params);

        $url = $_SERVER['PHP_SELF'] . '?' . preg_replace('/[\?|&]page=[0-9]+/', '', $_SERVER['QUERY_STRING']);
        $page = new SubPages($url, 20, $total, $_curr_page);
        $limit = $page->GetLimit();
        $sort = ' ctime desc ';

        $response->page_html = $page->GetPageHtml();
        $response->status_options = RefundDao::getStatusArr();

        $response->list = array();
        if($total)
            $response->list = RefundDao::getSlaveInstance()->getList($params, $limit, $sort );

		$response->CDN_YMALL = CDN_YMALL;
        $this->layoutSmarty();
	}

    /**
     * @param $request
     * @param $response
     * @throws \Exception
     * @desc 商品状态修改
     */
    public function info($request, $response) {
        $id = $request->id;

        $info = RefundDao::getSlaveInstance()->find($id);
        if(!$info)
            throw new \Exception('退款订单id 错误','40002');

        if(self::isPost()) {
            $data = array();
            $data['refund_status'] = $request->post('refund_status');
            $data['refund_money'] = $request->post('refund_money');

            $data['admin_id'] = $this->current_user['user_id'];
            $data['admin_desc'] = $request->post('admin_desc');
            $data['utime'] = time();

            try{
                RefundDao::getMasterInstance()->edit($id, $data);
                $order_edit = array();
                $order_edit['refund_status'] = $data['refund_status'];

                OrderDao::getMasterInstance()->edit($info['order_id'], $order_edit);

                if($order_edit['refund_status'] == RefundDao::REFUND_ACCEPTED) {
                    $orderSrv = new OrderSrv();
                    $remark = '申请退款审核通过，商家取消订单';
                    $orderSrv->cancel($info['order_id'], $this->current_user['user_id'], $remark);
                }

                self::redirect('index.php?_c=refund');
            }catch (\Exception $e) { throw $e; }

        }
        else {
            $order_goods = OrderGoodsDao::getSlaveInstance()->find(array('order_id'=>$info['order_id']));
            $order_goods['goods_image'] = CDN_YMALL . $order_goods['goods_image'];

            $response->info = $info;
            $response->order_goods = $order_goods;

            $p = array('user_id='.$info['user_id']);
            $response->refund_times = RefundDao::getSlaveInstance()->getListCnt($p);
            $response->status_options = RefundDao::getStatusArr();

            $this->layoutSmarty();
        }
    }

    protected function initMenu() {
        return array(
            0=>array('url'=>'index.php?_c=orders&status=warning', 'tag'=>'warning', 'title'=>'E同步失败'),
            1=>array('url'=>'index.php?_c=orders&status=unpay', 'tag'=>'unpay', 'title'=>'待付款订单'),
            2=>array('url'=>'index.php?_c=orders&status=unsend', 'tag'=>'unsend', 'title'=>'待发货订单'),
            3=>array('url'=>'index.php?_c=orders&status=sended', 'tag'=>'sended', 'title'=>'已发货订单'),
            4=>array('url'=>'index.php?_c=orders&status=finished', 'tag'=>'finished', 'title'=>'已完成订单'),
            5=>array('url'=>'index.php?_c=orders&status=canceled', 'tag'=>'canceled', 'title'=>'已取消订单'),
            6=>array('url'=>'index.php?_c=orders&_a=saledown', 'tag'=>'saledown', 'title'=>'销售导出'),
            7=>array('url'=>'index.php?_c=refund', 'tag'=>'refund', 'title'=>'退款管理'),
        );
    }
}