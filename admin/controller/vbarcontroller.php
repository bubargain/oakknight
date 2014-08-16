<?php

namespace admin\controller;
use \app\dao\VbarDao;
use \app\dao\UserVbarDao;
use \app\dao\LoveDao;
use \app\dao\UserDao;
use \app\dao\NotifyGoodsDao;
use \app\common\util\SubPages;
use \app\service\vbar\UserVbarSrv;

class VbarController extends BaseController {
	public function index($request, $response) {
		$response->title = '系统Vbar';

        $params = array();
        if($request->type)
            $params['type'] = $request->type;

        $total = VbarDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 10, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = VbarDao::getSlaveInstance()->getList( $params, $limit );
        }

		$response->list = $list;
		$response->params = $params;
        $response->type_options = VbarDao::barTypeArray();
        $response->page_html = $page->GetPageHtml();
		
		$this->layoutSmarty();
	}

    public function info($request, $response) {
        $response->title = 'Vbar 查看';

        $bar_id = $request->bar_id;
        $info = VbarDao::getSlaveInstance()->find($bar_id);

        $info['extra'] = self::getExtra($info['type'], $info['extra']);



        $info['search'] = urldecode( self::str2Query( $info['search'] ) );

        $info['search_list'] = 'http://m.ymall.com/api/goods/search?query='.urlencode( $info['search'] ) . '&title=' . $info['title'];
        $info['search_goods'] = 'http://m.ymall.com/api/goods/detail?goodsid=103782';

        $type_arr = VbarDao::barTypeArray();
        $info['type_txt'] = $type_arr[$info['type']];
        $response->info = $info;
        $this->layoutSmarty();
    }

	public function add($request, $response) {
		$response->title = '系统VBar 添加';
		
		if(self::isPost()) {
            $data = self::formatPost($request);
            $data['utime'] = $data['ctime'] = time();
            $data['bar_id'] = VbarDao::getMasterInstance()->add($data);

            self::send($data);
            header ( "Location: index.php?_c=vbar&_a=index" );
        }

        $response->type_options = VbarDao::barTypeArray();
		$this->layoutSmarty( 'edit' );
	}

    public function edit($request, $response) {
        $response->title = '系统VBar 修改';

        $bar_id = $request->bar_id;
        $info = VbarDao::getSlaveInstance()->find($bar_id);

        $info['extra'] = self::getExtra($info['type'], $info['extra']);
        $info['search'] = self::str2Query( $info['search'] );

        if(self::isPost()) {
            $bar_id = $request->bar_id;
            $data = self::formatPost($request);
            $data['utime'] = time();

            VbarDao::getMasterInstance()->edit($bar_id, $data);

            header ( "Location: index.php?_c=vbar&_a=index" );
        }

        $response->info = $info;
        $response->type_options = VbarDao::barTypeArray();
        $this->layoutSmarty();
    }

    public function userbar($request, $response) {
        $params = array();
        if($request->bar_id)
            $params['bar_id'] = $request->bar_id;

        $total = UserVbarDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 10, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = UserVbarDao::getSlaveInstance()->getList( $params, $limit );
        }

        $response->list = $list;
        $response->params = $params;
        $response->type_options = VbarDao::barTypeArray();
        $response->page_html = $page->GetPageHtml();

        $this->layoutSmarty();
    }

    public function delete($request) {
        $bar_id = $request->bar_id;
        $where = 'bar_id='.intval($bar_id);
        UserVbarDao::getMasterInstance()->deleteByWhere( $where );
        VbarDao::getMasterInstance()->delete($bar_id);

        self::renderJson(array('ret'=>'ok'));
    }

    private function formatPost($request) {
        $data['title'] = $request->title;
        $data['img'] = $request->img;
        $data['url'] = $request->url;
        $data['search'] = self::query2Str( $request->search );

        $data['start_time'] = strtotime($request->start_time);
        $data['end_time'] = strtotime($request->end_time);
        $data['type'] = $request->type;

        $data['extra'] = self::setExtra($request->type, $request->extra);

        if( empty($data['title']) ||  empty($data['img']) || (empty($data['search']) && empty($data['url'])) || empty($data['start_time']) || empty($data['end_time']) )
            $this->showError( '请完善vbar数据' );

        if($data['type'] != VbarDao::BAR_SYS && empty($data['extra']) )
            $this->showError( '请完善vbar数据' );
        return $data;
    }

    private function query2Str( $query ) {
        $output = array();
        parse_str($query, $output);
        return serialize($output);
    }

    private function str2Query( $str ) {
        $arr = unserialize($str);
        return http_build_query($arr);
    }

    private function setExtra($type, $extra) {
        $params = array();
        if($type == VbarDao::BAR_LOVE || $type == VbarDao::BAR_NOTICE) {
            $params['goods_id'] = $extra;
        }
        elseif($type == VbarDao::BAR_USER) {
            $params['user_name'] = $extra;
        }
        return serialize($params);
    }

    private function getExtra($type, $extra) {
        $params = unserialize($extra);

        $txt = '';

        if($type == VbarDao::BAR_LOVE || $type == VbarDao::BAR_NOTICE) {
            $txt = $params['goods_id'];
        }
        elseif($type == VbarDao::BAR_USER) {
            $txt = $params['user_name'];
        }
        return $txt;
    }

    private function send($info) {
        $params = unserialize($info['extra']);
        switch($info['type']) {
            case VbarDao::BAR_LOVE:
                $user = self::love($params);
                break;
            case VbarDao::BAR_NOTICE:
                $user = self::notify($params);
                break;
            case VbarDao::BAR_USER:
                $user = self::users($params);
                break;

            default:
                $user = array();
                break;
        }

        if($user) {
            $userBarSrv = new UserVbarSrv();
            foreach($user as $id=>$name) {
                $userBarSrv->makeInfo($info, $id, $name);
            }
        }
    }

    private function notify($params) {
        $u = array();
        $sql = "select g.user_id,u.user_name from ym_notify_goods g inner join ym_user u on u.user_id=g.user_id where g.goods_id=?";
        $list = NotifyGoodsDao::getSlaveInstance()->getpdo()->getRows($sql, array($params['goods_id']));
        if($list) {
            foreach($list as $r) {
                $u[$r['user_id']] = $r['user_name'];
            }
        }
        return $u;
    }

    private function love($params) {
        $u = array();
        $sql="select g.user_id,u.user_name from ym_wish_goods g inner join ym_user u on u.user_id=g.user_id where g.goods_id=".$params['goods_id']." and g.is_delete=0";
        $list=LoveDao::getSlaveInstance()->getpdo()->getRows($sql);
        if($list) {
            foreach($list as $r) {
                $u[$r['user_id']] = $r['user_name'];
            }
        }
        return $u;
    }

    private function users($params) {
        preg_match_all('/[0-9]{11}/', $params['user_name'], $ret);
        if(!isset($ret[0]))
            return array();

        return UserDao::getSlaveInstance()->nameToIds($ret[0]);
    }
}