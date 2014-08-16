<?php

namespace admin\controller;

use \app\dao\ShortDao;
use \app\common\util\SubPages;

class ShortController extends BaseController {
	public function index($request, $response) {
		$response->title = '短链管理';

        $params = array();
        $total = ShortDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 20, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = ShortDao::getSlaveInstance()->getList( $params, $limit );
        }

        $response->list = $list;
        $response->params = $params;
        $response->page_html = $page->GetPageHtml();

        $this->layoutSmarty();
	}

    public function info($request, $response) {
        $response->title = '短链 查看';

        $start_time = $request->start_time ? $request->start_time : date('Y-m-d', time());
        $end_time = $request->end_time ? $request->end_time : date('Y-m-d', time());

        $key = $request->get('short', '');

        if($key)
            $params['key'] = " `info` like '%$key%'";

        $response->cnt = \app\dao\UserLogDao::getSlaveInstance()->getActionCnt( $params );

        $params['ctime'] = "ctime >= " . strtotime($start_time . '00:00:00') . " AND ctime <= " . strtotime($end_time . '23:59:59');

        $response->day_cnt = \app\dao\UserLogDao::getSlaveInstance()->getActionCnt( $params );
        $response->start_time = $start_time;
        $response->end_time = $end_time;

        $this->layoutSmarty();
    }

	public function add($request, $response) {
        $response->title = '短链 添加';
		
		if(self::isPost()) {
            $short_url = \app\common\util\short::generate($request->url);
            $response->short_url = $short_url;
        }

		$this->layoutSmarty();
	}

}