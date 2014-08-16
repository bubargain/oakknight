<?php

namespace admin\controller;

use app\dao\GoodsDao;
use app\dao\NotifyGoodsDao;
use app\common\util\SubPages;
use app\dao\UserInfoDao;

class NotifyController extends BaseController {
	// 商品列表
	public function index($request, $response) {
		$response->title = '商务到货提醒列表';

        $params = array();
        if($goods_id = $request->get('goods_id', 0))
            $params['goods_id'] = ' goods_id='.intval($goods_id);

		$total = NotifyGoodsDao::getSlaveInstance()->getListCnt( $params );

        $page = $request->get('page', 1);
        $size = 20;
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );

		$pageObj = new SubPages( $url, $size, $total, $page );
		$limit = $pageObj->GetLimit();
		$list = array();
		if ($total) {
			$list = NotifyGoodsDao::getSlaveInstance()->getList( $params, $limit );

            $goods_ids = array();
            foreach($list as $r) {
                $goods_ids[$r['goods_id']] = $r['goods_id'];
                $user_ids[$r['user_id']] = $r['user_id'];
            }

            $goods = GoodsDao::getSlaveInstance()->getInfoByGoodsIds($goods_ids);
            $users = UserInfoDao::getSlaveInstance()->getInfoByIds($user_ids);

            foreach($list as $k=>$v) {
                $list[$k]['goods_name'] = $goods[$v['goods_id']]['goods_name'];
                $list[$k]['stock'] = $goods[$v['goods_id']]['stock'];
                $list[$k]['user_name'] = $users[$v['user_id']]['user_name'];
                $list[$k]['status'] = $goods[$v['goods_id']]['status'] == GoodsDao::BUY_STATUS ? '可售' : '不可售';
            }

		}
		$response->list = $list;
		$response->page = $pageObj->GetPageHtml();
		$this->layoutSmarty ( 'index' );
	}

    public function down($request, $response) {
        $params = array();
        if($goods_id = $request->get('goods_id', 0))
            $params['goods_id'] = $goods_id;

        $list = NotifyGoodsDao::getSlaveInstance()->getGroupByGoods( $params);

        $goods_ids = array();
        foreach($list as $r) {
            $goods_ids[$r['goods_id']] = $r['goods_id'];
        }

        $goods = GoodsDao::getSlaveInstance()->getInfoByGoodsIds($goods_ids);

        ob_start();
        echo '<table>';
        echo "<tr><th>商品id</th><th>商品名称</th><th>到货提醒次数</th><th>商品库存</th><th>商品状态</th></tr>";
        foreach($list as $k=>$r) {
            $status = $goods[$r['goods_id']]['status'] == GoodsDao::BUY_STATUS ? '可售' : '不可售';

            echo "<tr><td>".$r['goods_id']."</td><td>"
                .$goods[$r['goods_id']]['goods_name']."</td><td>"
                .$r['num']."</td><td>".$goods[$r['goods_id']]['stock']."</td><td>"
                .$status."</td></tr>";

        }
        echo '</table>';
        $result = ob_get_clean();

        $title = '商品到货提醒'.date('Y/m/d');

        self::makeExcel($result, $title);
    }
}