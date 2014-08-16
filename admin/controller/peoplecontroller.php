<?php

namespace admin\controller;

use app\dao\PushTokenDao;
use app\dao\UserDao;
use app\service\ecerp\OrderSrv;
use sprite\mvc\controller;
use \stdClass;

class peopleController extends BaseController {
	// 商业模型统计列表
	public function index($request, $response) {
		;
	}

    function userAction($request, $response) {
        if(self::isPost()) {
            if($request->tui_start)
                $tui_start = strtotime( $request->tui_start . ' 00:00:00' );
            if($request->tui_end)
                $tui_end = strtotime( $request->tui_end  . ' 23:59:59');

            if($request->new_start)
                $new_start = strtotime( $request->new_start . ' 00:00:00' );
            if($request->new_end)
                $new_end = strtotime( $request->new_end  . ' 23:59:59');

            $return = array('active'=>0, 'reg'=>0, 'like'=>0, 'unlike'=>0, 'buy_pv'=>0, 'buy_uv'=>0, 'share_uv'=>0, 'share_pv'=>0,
                'notify_pv'=>0,'notify_uv'=>0, 'goods_pv'=>0,'goods_uv'=>0, 'orders'=>0, 'payed'=>0, 'unpay'=>0,'closed'=>0, 'payed_avg'=>0,'payed_amount'=>0,'order_buyers'=>0,'order_users'=>0, 're_quantity'=>0 );
            $maps = array();

            $pdo = \app\dao\PushTokenDao::getSlaveInstance()->getpdo();
            $sql = "select uuid, ctime from ym_push_token where ctime>=$new_start and ctime<=$new_end limit 0, 100000";

            $list = $pdo->getRows($sql);
            foreach($list as $r) {
                $uuid_arr[$r['uuid']] = 1;
                $return['active']++;
            }
            unset($list);

            $sql = "select user_id,uuid from ym_user_info where ctime>=$new_start";
            $list = $pdo->getRows($sql);
            foreach($list as $r) {
                if( !$uuid_arr[$r['uuid']] )
                    continue;
                $return['reg']++;
                $maps[$r['user_id']] = $r['uuid'];
            }
            unset($list);
            //取得阶段注册数

            $params = array("ctime>=" . $tui_start . " AND ctime<" . $tui_end);
            $list = \app\dao\UserLogDao::getSlaveInstance()->getActionList( $params );

            $uuid_curr = $user_curr = array();
            foreach ( $list as $val ) {
                if( !$uuid_arr[$val['uuid']] )
                    continue;

                $tmp['uuid_curr'][$val['uuid']] = 1;
                $tmp['user_curr'][$val['user_id']] = 1;

                //详情页UV(PV) 分享点击UV(PV) 喜欢总数 取消喜欢总数 到货提醒UV(PV) 订单确认页UV(PV)
                if($val['type'] == 'love' && $val['action'] == 'like')
                    $return['like']++;

                if ($val['type'] == 'love' && $val['action'] == 'unlike')
                    $return['unlike']++;

                if ($val ['type'] == 'share' && ($val ['action'] == 'goods' || $val ['action'] == 'order')) {
                    $return['share_pv']++;
                    $tmp['share_uv'][$val['uuid']] = 1;
                }

                if ($val ['type'] == 'goods' && $val ['action'] == 'buy') {
                    $return['buy_pv']++;
                    $tmp['buy_uv'][$val['uuid']] = 1;
                }

                if ($val ['type'] == 'notify' && $val ['action'] == 'set') {
                    $return['notify_pv']++;
                    $tmp['notify_uv'][$val['uuid']] = 1;
                }

                if ($val ['type'] == 'goods' && $val ['action'] == 'info') {
                    $return['goods_pv']++;
                    $tmp['goods_uv'][$val['uuid']] = 1;
                }
            }
            unset($list);

            $return['share_uv'] = count($tmp['share_uv']);
            $return['buy_uv'] = count($tmp['buy_uv']);
            $return['notify_uv'] = count($tmp['notify_uv']);
            $return['goods_uv'] = count($tmp['goods_uv']);
            $return['uuid_curr'] = count($tmp['uuid_curr']);
            $return['user_curr'] = count($tmp['user_curr']);
            unset($tmp);

            $orderSrv = new \app\service\OrderSrv();
            $params = array();
            $params['add_time'] = "add_time>=" . $tui_start . " AND add_time<" . $tui_end;

            if(count($maps) <= 5000)
                $params['buyer_id'] = "buyer_id in(" . implode(',', array_keys($maps) ) . " )";
            //$params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';

            /* 取得满足条件的订单列表 */
            $orders = \app\dao\OrderDao::getSlaveInstance()->orderList($params, '0, 100000', ' order_id asc');
            if($orders) { //总订单数	支付订单数	代付款订单数	取消订单数

                $_o_users = array();

                foreach($orders as $r) {
                    if( !isset($maps[$r['buyer_id']]) )
                        continue;

                    switch($r['order_status']) {
                        case $orderSrv::PAYED_ORDER:
                        case $orderSrv::SHIPPING_ORDER:
                        case $orderSrv::RECEIVED_ORDER:
                        case $orderSrv::FINISHED_ORDER:
                            $_o_users['_buyers'][$r['buyer_id']]++;
                            break;
                    }
                }
                /* 取得阶段内老用户数据 */
                if( $_o_users['_buyers'] ) {
                    $params = array();
                    $params['add_time'] = "add_time<" . $tui_start;
                    $params['buyer_id'] = "buyer_id in(" . implode(',', array_keys($_o_users['_buyers']) ) . " )";
                    $params['order_status'] = "order_status in( " . $orderSrv::PAYED_ORDER . ',' . $orderSrv::SHIPPING_ORDER . ',' . $orderSrv::RECEIVED_ORDER . ',' . $orderSrv::FINISHED_ORDER . ')';

                    /* 取得满足条件的订单列表 */
                    $old_list = \app\dao\OrderDao::getSlaveInstance()->orderList($params, '0, 100000', ' order_id asc');
                    foreach($old_list as $r) {
                        $_o_users['old_buyers'][$r['buyer_id']] = 1;
                    }
                }


                foreach($orders as $r) {
                    if( !$maps[$r['buyer_id']] )
                        continue;

                    $return['orders']++;
                    $_o_users['order_users'][$r['buyer_id']] = 1;
                    switch($r['order_status']) {
                        case $orderSrv::UNPAY_ORDER:
                            $return['unpay']++;
                            break;
                        case $orderSrv::CLOSED_ORDER:
                            $return['closed']++;
                            break;
                        case $orderSrv::PAYED_ORDER:
                        case $orderSrv::SHIPPING_ORDER:
                        case $orderSrv::RECEIVED_ORDER:
                        case $orderSrv::FINISHED_ORDER:
                            $return['payed']++;
                            $return['payed_amount'] += $r['goods_amount'];
                            $_o_users['order_buyers'][$r['buyer_id']] = 1;

                            //订单统计
                            $_o_users['_orders'][$r['order_id']] = 1;

                            if($_o_users['old_buyers'][$r['buyer_id']] || $_o_users['_buyers'][$r['buyer_id']] >1) {
                                $_o_users['rebuyers'][$r['buyer_id']] = 1;
                                $_o_users['reorders'][$r['order_id']] = 1;
                            }
                            break;
                    }
                }

                if($_o_users['_orders']) {
                    $sql = "select sum(quantity) from ym_order_goods where order_id in(".implode(',', array_keys($_o_users['_orders'])).')';
                    $return['quantity'] = \app\dao\OrderDao::getSlaveInstance()->getpdo()->getOne($sql);
                }

                if($_o_users['reorders']) {
                    $sql = "select sum(quantity) from ym_order_goods where order_id in(".implode(',', array_keys($_o_users['reorders'])).')';
                    $return['re_quantity'] = \app\dao\OrderDao::getSlaveInstance()->getpdo()->getOne($sql);
                }

                $return['rebuyers'] = count($_o_users['rebuyers']);
                $return['order_buyers'] = count($_o_users['order_buyers']);
                $return['order_users'] = count($_o_users['order_users']);
                if($return['payed'] > 0)
                    $return['payed_avg'] = $return['payed_amount'] / $return['payed'];

                unset($_o_users);

            }
            unset($orders);
            unset($maps);
            unset($uuid_arr);

            $response->return = $return;
        }

        $this->layoutSmarty();
    }

    public function userActiveLog($request, $response) {
        if(self::isPost()) {
            if($request->start)
                $params[] = 'ctime>='.strtotime($request->start_time . '00:00:00');

            if($request->end_time)
                $params[] = 'ctime<'.(strtotime($request->end_time . '23:59:59') + 1);

            $user = \app\dao\UserInfoDao::getSlaveInstance()->getInfoByName(trim($request->user_name));
            if(!$user)
                $this->showError('该用户不存在');

            $params[] = 'user_id='.$user['user_id'];

            $token = \app\dao\PushTokenDao::getSlaveInstance()->find($user['uuid']);

            $params[] = '`type`="active"';
            $list = \app\dao\UserLogDao::getSlaveInstance()->getActionList( $params );

            $ret = array();
            foreach($list as $r) {
                $_idx = date('Y/m/d', $r['ctime']);
                $ret[$_idx]['times']++;
            }

            ob_start();
            echo '<table border="1">';
            echo '<tr><th>活跃日期</th><th>活跃次数</th><th>注册日期（与输入的时间区间无关）</th><th>第一次活跃日期（与输入的时间区间无关）</th></tr>';
            echo '<tr><th colspan="4">'.$request->start_time.'--'.$request->end_time.'</th></tr>';

            foreach($ret as $day=>$row) {
                echo '<tr><th>'.$day.'</th><th>'.$row['times'].'</th><th>'.date('Y/m/d H:i:s', $user['ctime']) .'</th><th>'.date('Y/m/d H:i:s', $token['ctime']).'</th></tr>';
            }
            echo '</table>';
            $result = ob_get_clean();
            self::makeExcel($result, '活跃用户详细统计'.date('Y/m/d'));
        }
        else {
            $this->layoutSmarty();
        }
    }

    public function activeHot($request, $response) {
        if(self::isPost()) {
            if($request->start_time)
                $params[] = 'ctime>='.strtotime($request->start_time . ' 00:00:00');

            if($request->end_time)
                $params[] = 'ctime<'.(strtotime($request->end_time . '23:59:59') + 1 );

            $params[] = '`type`="active"';

            $list = \app\dao\UserLogDao::getSlaveInstance()->getActionList( $params );

            $user = $uuid = array();
            foreach($list as $r) {
                $_idx = date('ymd', $r['ctime']);
                $uuid[$r['uuid']]['times'][$_idx] = 1;
                if($r['user_id']) {
                    $uuid[$r['uuid']]['user'] = $r['user_id'];
                    $user[$r['user_id']] = $r['uuid'];
                }
            }
            unset($list);

            $ret = array();
            foreach($uuid as $_d=>$r) {
                $cnt = count($r['times']);
                $ret[$cnt]['uuid']++;

                if($_uid = $uuid[$_d]['user'])
                    $ret[$cnt]['users'][$_uid] = $_uid;
            }

            $total_uuid = count($uuid);
            $total_user = count($user);

            unset($uuid);
            unset($user);

            ksort($ret, SORT_NUMERIC);
            //	激活用户数	占比（该活跃天数下激活用户/该日期区间内总激活用户数）	注册用户数	占比（该活跃天数下注册用户数/该日期区间内总注册用户数）
            ob_start();
            echo '<table border="1">';
            echo '<tr><th>活跃天数（区间日期相减绝对值+1后依次排列）</th><th>激活用户数</th><th>占比（该活跃天数下激活用户/该日期区间内总激活用户数）</th><th>注册数</th><th>占比（该活跃天数下注册用户数/该日期区间内总注册用户数）</th></tr>';
            echo '<tr><th colspan="5">'.$request->start_time.'--'.$request->end_time.'</th></tr>';

            foreach($ret as $cnt=>$row) {
                $uuid_rate = 0.00;
                $reg_rate = 0.00;
                $reg_cnt = count($row['users']);
                if($total_uuid)
                    $uuid_rate = round($row['uuid'] * 100 / $total_uuid, 2);
                if($total_user)
                    $reg_rate = round( $reg_cnt * 100 / $total_user, 2);
                echo '<tr><th>'.$cnt.'</th><th>'.$row['uuid'].'</th><th>'.$uuid_rate .'%</th><th>'.$reg_cnt.'</th><th>'.$reg_rate.'%</th></tr>';
            }
            echo '</table>';
            $result = ob_get_clean();
            self::makeExcel($result, '活跃用户统计'.date('Y/m/d'));
        }
        else {
            $this->layoutSmarty();
        }
    }

    public function active($request, $response) {
        if(self::isPost()) {
            $params = array();
            if($request->start_time)
                $params['start_time'] = 'ctime>='.strtotime( $request->start_time . ' 00:00:00' );
            if($request->end_time)
                $params['end_time'] = 'ctime<='.strtotime( $request->end_time  . ' 23:59:59');

            $params['type'] = '`type`="active"';
            $list = \app\dao\UserLogDao::getSlaveInstance ()->getActionList( $params );
            $uuid_arr = array();

            $return = array('active_num'=>0, 'active_num_uv'=>0, 'reg_num'=>0);
            foreach($list as $r) {
                $uuid_arr[$r['uuid']] = 1;
                $return['active_num']++;
            }
            $return['active_num_uv'] = count($uuid_arr);
            unset($list);

            $day_list = array();

            $pdo = \app\dao\UserLogDao::getSlaveInstance()->getpdo();
            if($uuid_arr) {
                $sql = "select user_id, uuid from ym_user_info where uuid in('".implode("','", array_keys($uuid_arr))."')";
                $user = $pdo->getRows($sql);
                foreach($user as $r) {
                    $maps[$r['uuid']][$r['user_id']] = 1;
                    $return['reg_num']++;
                }
                unset($user);

                $sql = "select ctime, uuid from ym_push_token where uuid in('".implode("','", array_keys($uuid_arr))."')";
                $push = $pdo->getRows($sql);

                foreach($push as $r) {
                    $_idx = date('Ymd', $r['ctime']);
                    $day_list[$_idx]['active_num']++;
                    $day_list[$_idx]['day'] = date('Y/m/d', $r['ctime']);
                    if($maps[$r['uuid']])
                        $day_list[$_idx]['reg_num'] += count($maps[$r['uuid']]);
                }
                unset($maps);
            }
            ksort($day_list, SORT_NUMERIC);
            //开始时间	结束时间	活跃数UV(PV)	注册数（活跃数中注册人数）

            ob_start();
            echo '<table border="1">';
            echo '<tr><th>开始时间</th><th>结束时间</th><th>活跃数UV(PV)</th><th>注册数（活跃数中注册人数）</th></tr>';
            echo '<tr><th>'.$request->start_time.'</th><th>'.$request->end_time.'</th><th>'.$return['active_num_uv'].'('.$return['active_num'].')</th><th>'.$return['reg_num'].'</th></tr>';
            echo '</table>';

            echo '<br />';
            echo '<br />';

            echo '<table border="1">';
            echo '<tr><th>日期</th><th>首次激活UV</th><th>首次激活UV占比</th><th>注册数（首次激活中注册人数）</th><th>注册数（首次激活中注册人数）占比</th></tr>';

            foreach($day_list as $key=>$row) {
                $uv_rate = 0.00;
                $reg_rate = 0.00;
                if($return['active_num_uv'])
                    $uv_rate = round($row['active_num'] * 100 / $return['active_num_uv'], 2);
                if($return['reg_num'])
                    $reg_rate = round($row['reg_num'] * 100 / $return['reg_num'], 2);
                echo '<tr><th>'.$row['day'].'</th><th>'.$row['active_num'].'</th><th>'.$uv_rate .'%</th><th>'.$row['reg_num'].'</th><th>'.$reg_rate.'%</th></tr>';
            }
            echo '</table>';
            $result = ob_get_clean();

            self::makeExcel($result, '销售数据报表'.date('Y/m/d'));
        }
        else {
            $this->layoutSmarty();
        }
    }
}