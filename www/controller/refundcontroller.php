<?php 
namespace www\controller;

use app\dao\OrderDao;
use app\dao\OrderExtmDao;
use app\dao\RefundDao;
use app\dao\UserInfoDao;
/*
 * product related behavior
 * @author : daniel
 */
class RefundController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);
        parent::checkLogin();
    }

    public function index() {}

    /**
     * @param $request
     * @param $response
     * @desc 传入商品id及数量，生成订单确认页面
     */
    public function apply($request, $response) {
        try{
            $data = array();
            $data['card_no'] = $request->post('card_no', '');
            $data['refund_desc'] = $request->post('refund_desc', '');
            $data['order_id'] = $request->post('order_id', 0);
            
			$refund =new \app\service\RefundSrv();
			$this->result($refund->apply($data));
        }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }

    public function info($request, $response) {
        try{
            $order_id = $request->order_id;
            $info = RefundDao::getSlaveInstance()->find( array('order_id'=>$order_id) );

            if(!$info)
                throw new \Exception('暂未提交退款申请', '6001');

            $this->result($info);
            }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }
}
