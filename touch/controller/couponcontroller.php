<?php

namespace touch\controller;

use \app\service\coupon\CouponSrv;

class CouponController extends BaseController {
	private $_pageSize = 10;
	public function __construct($request, $response) {
		parent::__construct ( $request, $response );
	}
	/**
	 * 个人优惠劵列表页
	 * @param object $request
	 * @param object $response
	 */
	public function index($request, $response) {
		
		$params = array();
        $params['user_id'] = $this->current_user['user_id'];
        
        
        $params['state'] = $request->get('state', 'valid');
		$response->state = $params['state'];
        
        $page = $request->page;
        $size = 5;
		
        $page = $page < 1 ? 1 : $page;
        $start = ($page - 1) * $size;
        $limit = " $start, $size";
		
        $UserCouponSrv = new \app\service\coupon\UserCouponSrv();
        $ret['count'] = $UserCouponSrv->getListCnt($params);  //用户可用优惠劵总数
		
        $ret['pages'] = ceil($ret['count'] / $size);
        $ret['cur_page'] = $page;
        $ret['next'] = ($page < $ret['pages']) ? $page +1 : 0;
		$ret['prev'] = ($page == 1) ?  0: $page -1 ;
		$response->ret = $ret;
		
        if($ret['count'] > 0 && $ret['pages'] >= $page) {
            $response->coupons = $UserCouponSrv->getList($params, $limit);  //获取优惠劵信息
        }
		$this->layoutSmarty ('index');
	}

	
}