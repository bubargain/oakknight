<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\common\util\subpages;

class PushController extends BaseController {
	// push列表
	public function index($request, $response) {
		$response->title = 'PUSH列表';
		// 处理搜索信息
		if ($request->status || $request->status === '0') {
			$params['status'] = "`status` = " . intval ( $request->status );
		}
		if ($request->start_time || $request->end_time) {
			$start_time = strtotime( $request->start_time . ' 00:00:00' );
			$end_time = strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600;
			$params['push_time'] = "`push_time` >= " . $start_time . " AND `push_time` < " . $end_time;
		}
		
		$total = \app\dao\PushDao::getSlaveInstance()->getListCnt( $params );
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
		$page = new SubPages ( $url, 10, $total, $curPageNum );
		$limit = $page->GetLimit();
		$list = array();
		if ($total) {
            $user_options = self::options();
            $show_options = self::show_options();
			$list = \app\dao\PushDao::getSlaveInstance()->getList( $params, $limit );
            foreach($list as $k=>$row) {
                $list[$k]['extra'] = json_decode( $row['extra'] , true);
                $list[$k]['type_str'] = $user_options[$row['type']];
                $list[$k]['show_type_str'] = $user_options[$row['show_type']];
            }
		}
		$response->list = $list;
		$response->page = $page->GetPageHtml();
		$this->layoutSmarty ( 'index' );
	}

	// 发送PUSH
	public function push($request, $response) {
		$response->title = '发送PUSH';
		//获取表情目录
		$response->sf = Array('U0001F4E2','U0001F49D','U0001F60A','U0001F60D','U0001F61C','U0001F604','U0001F381','U0001F389','U0001F496','U000231B','U0002728','U0001F553','U0001F631');
		$response->current_time = date ( 'Y-m-d H:i:s', time () );
        if(self::isPost()) {
            if(!$request->type || !$request->message || !$request->push_time)
                $this->showError ( '提交信息不完整或有误', 'index.php?_c=push&_a=push' );

            $params = array();
            $params['status'] = 0;
            $params['user_id'] = $this->current_user['user_id'];
            $params['type'] = $request->post('type');
            $params['message'] = $request->post('message', '');
            $params['push_time'] = strtotime($request->post('push_time'));

            $params['extra'] = self::getPostExtra( $request, $params['type'] );

            $params['show_type'] = $request->post('show_type', 1);
            $show_property = $request->post('show_property', '');
            if($params['show_type'] == 2) {
                $params['show_property'] = 'goods_id='.$show_property;
            }
            elseif($params['show_type'] == 3) {
                $params['show_property'] = 'url='.$show_property;
            }
            else {
                $params['show_property'] = '';
            }

            $params['ctime'] = time();
            \app\dao\PushDao::getMasterInstance()->add($params);
            header( "Location: index.php?_c=push&_a=index" );
            exit();
        }

        $response->extra_list = self::initExtraForm();
        $response->options = self::options();
        $response->show_options = self::show_options();
        $this->layoutSmarty();
	}

    public function ajaxGetExtra($request, $response) {
        $list = self::initExtraForm($request->type);
        self::renderJson($list);
    }

    private function initExtraForm($type = 'all') {
        $config_list = self::config($type);
        $list = array();
        foreach($config_list as $row) {
            $list[] = $this->$row['type']($row);
        }
        return $list;
    }

    private function getPostExtra($request, $type) {
        $config_list = self::config($type);
        $p = array();
        foreach($config_list as $row) {
            $p[$row['key']] = $request->post( $row['key'] );
        }
        return json_encode($p);
    }

    private function text($info) {
        return array('label'=>$info['label'], 'form'=>'<input type="text" name="'.$info['key'].'">');
    }

    private function texteara($info) {
        return array('label'=>$info['label'], 'form'=>'<textarea name="'.$info['key'].'" rows="5"></textarea>');
    }

    private function config($type = 'all') {
        $list = array(
            'all'=>array(),
            'wishes'=>array(
                array('key'=>'goods_id','type'=>'text', 'label'=>'商品名称'),
            ),
            'notify'=>array(
                array('key'=>'goods_id','type'=>'text', 'label'=>'商品名称'),
            ),
            'users'=>array(
                array('key'=>'users','type'=>'texteara', 'label'=>'用户列表'),
            ),
        );
        return $list[$type];
    }

    private function show_options() {
        return array(
            '1'=>'--无操作--',
            '2'=>'--指定商品--',
            '3'=>'--指定html--',
        );
    }

    private function options() {
        return \app\dao\PushDao::getSlaveInstance()->getTypes();
    }
}