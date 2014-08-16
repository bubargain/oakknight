<?php

namespace admin\controller;

use \app\dao\UserDao;
use \app\dao\CouponDao;
use \app\dao\UserCouponDao;
use \app\service\coupon\UserCouponSrv;
use \app\common\util\SubPages;

class CouponController extends BaseController {
	public function index($request, $response) {
		$response->title = '优惠券列表';
        $params = array();

        $total = CouponDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 20, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = CouponDao::getSlaveInstance()->getList( $params, $limit );
        }

		$response->list = $list;
		$response->params = $params;
        $response->page_html = $page->GetPageHtml();
		
		$this->layoutSmarty();
	}

    public function send($request, $response) {
        $response->title = '优惠券 发送';
        $coupon = CouponDao::getSlaveInstance()->find($request->cpn_id);

        $_time = time();
        if(!$coupon || $coupon['end_time'] <= $_time) {
            $this->showError ( "优惠券不存在或已结束" , "index.php?_c=coupon"  );
        }

        if(self::isPost()) {
            $users = self::getSendUser($request);

            $info = array();
            $info['cpn_id'] = $coupon['cpn_id'];
            $info['amount'] = $coupon['money'];
            $UserCouponSrv = new UserCouponSrv();

            $ok_num = $err_num = 0;
            foreach($users as $_id=>$_name) {
                $info['user_id'] = $_id;
                $info['user_name'] = $_name;
                try{
                    $UserCouponSrv->send($info);
                    $ok_num++;
                }
                catch(\Exception $e) {
                    throw $e;
                    $err_num++;
                }
            }
            $this->showError ( "优惠券:{$coupon['title']} 成功: $ok_num, 失败: $err_num", "index.php?_c=coupon" );
        }
        else {
            $response->coupon = $coupon;
            $this->layoutSmarty();
        }
    }

    public function info($request, $response) {
        $response->title = '优惠券 详细';

        $response->info = CouponDao::getSlaveInstance()->find($request->cpn_id);
        $this->layoutSmarty( );
    }

	public function add($request, $response) {
		$response->title = '优惠券 添加';
		
		if(self::isPost()) {
            $data = self::formatPost($request);
            $data['ctime'] = time();
            $data['cpn_id'] = CouponDao::getMasterInstance()->add($data);
            header ( "Location: index.php?_c=coupon&_a=index" );
        }
		$this->layoutSmarty( 'edit' );
	}

    public function edit($request, $response) {
        $response->title = '优惠券 修改';

        $info = CouponDao::getSlaveInstance()->find($request->cpn_id);
        $_time = time();
        if($info['start_time'] <= $_time)
            $this->showError( '只能修改未开始的优惠券' );

        if(self::isPost()) {
            $cpn_id = $request->cpn_id;
            $data = self::formatPost($request);
            $data['utime'] = time();

            BarDao::getMasterInstance()->edit($cpn_id, $data);

            header ( "Location: index.php?_c=coupon&_a=index" );
        }

        $response->info = $info;
        $this->layoutSmarty();
    }

    public function userCoupon($request, $response) {
        $params = array();

        if($request->cpn_id)
            $params['cpn_id'] = $request->cpn_id;

        if($request->coupon_sn)
            $params['coupon_sn'] = $request->coupon_sn;

        if($request->user_name)
            $params['user_name'] = $request->user_name;

        $total = UserCouponDao::getSlaveInstance()->getListCnt( $params );

        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 20, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = UserCouponDao::getSlaveInstance()->getList( $params, $limit );

            $total_info = UserCouponDao::getSlaveInstance()->getSumInfo($params);
            $total_info['total'] = $total;
        }

        $response->list = $list;
        $response->params = $params;
        $response->total_info = $total_info;
        $response->page_html = $page->GetPageHtml();

        $this->layoutSmarty();
    }

    public function delete($request) {
        $cpn_id = $request->cpn_id;
        $where = 'cpn_id='.intval($cpn_id);
        UserCouponDao::getMasterInstance()->deleteByWhere( $where );
        CouponDao::getMasterInstance()->delete($cpn_id);

        self::renderJson(array('ret'=>'ok'));
    }

    private function formatPost($request) {
        $data['title'] = $request->title;
        $data['alt'] = $request->alt;
        $data['money'] = $request->amount;
        $data['min_order_amount'] = $request->min_order_amount;
        $data['times'] = $request->times ? $request->times : 1;
        $data['from_time'] = strtotime($request->from_time);
        $data['end_time'] = strtotime($request->end_time);
        $data['url'] = $request->url;
        $data['coupon_text'] = $request->coupon_text;

        if( empty($data['title']) ||  empty($data['money']) || empty($data['from_time']) || empty($data['end_time']) )
            $this->showError( '请完善优惠券数据' );
        return $data;
    }

    /**
     * @param $request
     * @return array|void
     * @desc 根据参数取得用户对应uids
     */
    private function getSendUser($request) {
        $users = array();
        switch($request->type) {
            case 'reg':
                $users = self::iGetUidsByReg(strtotime($request->start_time), strtotime($request->end_time));
                break;
            case 'names':
                $users = self::iGetUidsByNames($request->names);
                break;
        }

        return $users;
    }

    /**
     * @param $start_time
     * @param $end_time
     * @desc 根据注册时间生成取得用户uids
     */
    private function iGetUidsByReg($start_time, $end_time) {
        $list = UserDao::getSlaveInstance()->getAllBrSearch(array('start_time'=>$start_time, 'end_time'=>$end_time));
        $uids = array();
        foreach($list as $r) {
            $uids[$r['user_id']] = $r['user_name'];
        }
        return $uids;
    }

    private function iGetUidsByNames($names) {
        preg_match_all('/[0-9]{11}/', $names, $ret);
        if(!isset($ret[0]))
            return array();

        return UserDao::getSlaveInstance()->nameToIds($ret[0]);
    }
}