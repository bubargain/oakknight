<?php

namespace admin\controller;
use \app\dao\BarDao;
use \app\dao\UserBarDao;
use \app\dao\LoveDao;
use \app\dao\UserDao;
use \app\dao\NotifyGoodsDao;
use \app\common\util\SubPages;
use \app\service\bar\UserBarSrv;

class BarController extends BaseController {
	public function index($request, $response) {
		$response->title = '系统bar';

        $params = array();
        if($request->type)
            $params['type'] = $request->type;

        $total = BarDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 10, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = BarDao::getSlaveInstance()->getList( $params, $limit );
        }

		$response->list = $list;
		$response->params = $params;
        $response->type_options = self::getTypes();
        $response->page_html = $page->GetPageHtml();
		
		$this->layoutSmarty();
	}

    public function info($request, $response) {
        $response->title = '系统Bar 查看';

        $bar_id = $request->bar_id;
        $info = BarDao::getSlaveInstance()->find($bar_id);

        $info['extra'] = self::getExtra($info['type'], $info['extra']);

        $info['search'] = urldecode( self::str2Query( $info['search'] ) );

        $type_arr = self::getTypes();

        $info['type_txt'] = $type_arr[$info['type']];

        $response->info = $info;
        $this->layoutSmarty();
    }

	public function add($request, $response) {
		$response->title = '系统Bar 添加';
		
		if(self::isPost()) {

            $data = self::formatPost($request);
           
            $data['ctime'] = time();

            $data['bar_id'] = BarDao::getMasterInstance()->add($data);
			// var_dump( $data);die();
            self::send($data);

            header ( "Location: index.php?_c=bar&_a=index" );
        }

        $response->type_options = self::getTypes();
		$this->layoutSmarty( 'edit' );
	}

    public function edit($request, $response) {
        $response->title = '系统Bar 修改';

        $bar_id = $request->bar_id;
        $info = BarDao::getSlaveInstance()->find($bar_id);
        $_time = time();
        if($info['start_time'] <= $_time)
            $this->showError( '只能修改未开始的bar' );

        $info['extra'] = self::getExtra($info['type'], $info['extra']);
        $info['search'] = self::str2Query( $info['search'] );

        if(self::isPost()) {
            $bar_id = $request->bar_id;
            $data = self::formatPost($request);
            $data['ctime'] = time();

            BarDao::getMasterInstance()->edit($bar_id, $data);

            header ( "Location: index.php?_c=bar&_a=index" );
        }

        $response->info = $info;
        $response->type_options = self::getTypes();
        $this->layoutSmarty();
    }

    public function userbar($request, $response) {
        $params = array();

        if($request->bar_id)
            $params['bar_id'] = $request->bar_id;

        $total = UserBarDao::getSlaveInstance()->getListCnt( $params );
        $curPageNum = $request->page ? intval ( $request->page ) : 1;
        $url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] );
        $page = new SubPages( $url, 10, $total, $curPageNum );
        $limit = $page->GetLimit();
        $list = array();
        if ($total) {
            $list = UserBarDao::getSlaveInstance()->getList( $params, $limit );
            $_ids = array();
            foreach($list as $r) {
                $_ids[$r['bar_id']] = $r['bar_id'];
            }

            $bars = BarDao::getSlaveInstance()->getInfoByIds($_ids);

            foreach($list as $k=>$r) {
                $list[$k] = array_merge($r, $bars[$r['bar_id']]);
            }
        }

        $response->list = $list;
        $response->params = $params;
        $response->type_options = self::getTypes();
        $response->page_html = $page->GetPageHtml();

        $this->layoutSmarty();
    }

    public function delete($request) {
        $bar_id = $request->bar_id;
        $where = 'bar_id='.intval($bar_id);
        UserBarDao::getMasterInstance()->deleteByWhere( $where );
        BarDao::getMasterInstance()->delete($bar_id);

        self::renderJson(array('ret'=>'ok'));
    }

    private function formatPost($request) {
        $data['title'] = $request->title;
        $data['img'] = $request->img;
        //$data['keyword'] = $request->keyword;

        $data['search'] = self::query2Str( $request->search );
        $data['url'] = $request->url;
        $data['times'] = $request->times;
        $data['start_time'] = strtotime($request->start_time);
        $data['end_time'] = strtotime($request->end_time);
        $data['type'] = $request->type;

        $data['extra'] = self::setExtra($request->type, $request->extra);

        if( empty($data['title']) ||  empty($data['img']) || (empty($data['search']) && empty($data['url'])) || empty($data['start_time']) || empty($data['end_time']) )
            $this->showError( '请完善bar数据' );

        if($data['type'] > 1 && empty($data['extra']) )
            $this->showError( '请完善bar数据' );
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
        if($type == 2 || $type == 3) {
            $params['goods_id'] = $extra;
        }
        elseif($type == 4) {
            $params['user_name'] = $extra;
        }

        return serialize($params);
    }

    private function getExtra($type, $extra) {
        $params = unserialize($extra);

        $txt = '';

        if($type == 2 || $type == 3) {
            $txt = $params['goods_id'];
        }
        elseif($type == 4) {
            $txt = $params['user_name'];
        }
        return $txt;
    }

    private function send($info) {
        //if($info['type'] == 2)
            //return true;

        $params = unserialize($info['extra']);
		//var_dump($info);
        switch($info['type']) {
            case '2':
                $user = self::love($params);
                break;
            case '3':
                $user = self::notify($params);
                break;
            case '4':
                $user = self::users($params);
                break;

            default:
                $user = array();
                break;
        }
		//var_dump($user);die();
        if($user) {
            $userBarSrv = new UserBarSrv();
            foreach($user as $id) {
                $userBarSrv->makeInfo($info, $id, '');
            }
        }
    }

    private function getTypes() {
        return array( 4=>'指定用户', 1=>'全部用户', 2=>'喜欢', 3=>'到货提醒');
    }

    private function notify($params) {
    	
        $u = array();
        $list = NotifyGoodsDao::getSlaveInstance()->findByField('goods_id',$params['goods_id'] );
        if($list) {
            foreach($list as $r) {
                $u[$r['user_id']] = $r['user_id'];
            }
        }
        return $u;
    }

    private function love($params) {
        $u = array();
        //$list = LoveDao::getSlaveInstance()->findByField(array('goods_id'=>$params['goods_id'], 'is_delete'=>0) );
        $sql="select * from ym_wish_goods where goods_id=".$params['goods_id']." and is_delete=0";
       
        $list=LoveDao::getSlaveInstance()->getpdo()->getRows($sql);
        //var_dump($list);die();
        if($list) {
            foreach($list as $r) {
                $u[$r['user_id']] = $r['user_id'];
            }
        }
        return $u;
    }

    private function users($params) {
        preg_match_all('/[0-9]{11}/', $params['user_name'], $ret);
        if(!isset($ret[0]))
            return array();
        
        $list = UserDao::getSlaveInstance()->nameToIds($ret[0]);
        return array_keys($list);
    }

}