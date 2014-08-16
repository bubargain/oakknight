<?php 
namespace www\controller;

use app\service\coupon\UserCouponSrv;
/*
 * product related behavior
 * @author : daniel
 */
class CouponController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);
        parent::checkLogin();
    }

    public function index($request, $response) {

        $params = array();
        $params['user_id'] = $this->current_user['user_id'];
        $params['state'] = $request->get('state', 'valid');

        $page = $request->page;
        $size = 20;

        $page = $page < 1 ? 1 : $page;
        $start = ($page - 1) * $size;
        $limit = " $start, $size";

        $UserCouponSrv = new UserCouponSrv();
        $ret['count'] = $UserCouponSrv->getListCnt($params);

        $ret['pages'] = ceil($ret['count'] / $size);
        $ret['next'] = ($ret['pages'] <= $ret) ? false : true;

        $ret['list'] = array();

        if($ret['count'] > 0 && $ret['pages'] <= $page) {
            $ret['list'] = $UserCouponSrv->getList($params, $limit);
        }
        $this->result($ret);
    }

    public function info($request, $response) {
        try{
            $this->result(array());
            }
        catch(\Exception $e) {
            $this->error($e->getCode(), $e->getMessage());
        }
    }
}
