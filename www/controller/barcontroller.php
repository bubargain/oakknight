<?php
namespace www\controller;
use \app\dao\BarDao;
use app\dao\UserBarDao;
use \app\service\bar\UserBarSrv;

class BarController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);

        #$this->current_user = array('user_id'=>1000, 'clientid'=>'0ef3194b3445159e641bc4d623c88a2d766fc01e', 'user_name'=>'18610485690');
        #$this->current_user = array('clientid'=>'0ef3194b3445159e641bc4d623c88a2d766fc01e');
    }

    public function index($request, $response) {}

    /**
     * @param $request
     * @param $response
     *
     */
    public function push($request, $response) {
        try{
            $Srv = new UserBarSrv();
			
            /**
             * BUG FIX: 用户没登陆也需要获得clientid
             * @modifer: Daniel
             */
            $data = $Srv->push($this->current_user['user_id'], $this->header['clientid']);
            if($data) {
                $data['img'] = CDN_YMALL . $data['img'];
                $data['target'] = $data['url'] ? 2 : 1;
                $data['left'] = $data['left'] < 0 ? 0 : $data['left'];
                $data['search'] = unserialize( $data['search'] );
            }
            else {
                $data = array();
            }

            $this->result( $data );
        }
        catch(\Exception $e) {$this->error( $e->getMessage(), $e->getCode() );}
    }

    public function set($request, $response) {
        try{
            $UserBarSrc = new UserBarSrv();
            $UserBarSrc->set($request->id);

            $this->result(array('ok'));
        }catch (\Exception $e){}
    }

    public function show($request, $response) {
        $bar_id = $request->bar_id;
        $info = BarDao::getSlaveInstance()->find($bar_id);

        if(!$info)
            throw new \Exception('bar 已过期或被删除', 5002);
        if($info['search']) {
            $info['search'] = urlencode(http_build_query( unserialize($info['search']) ) );
        }
        $info['img'] = CDN_YMALL . $info['img'];
        $q = array('title'=>$info['title'], 'query'=>$info['search']);

        $url = 'http://m.ymall.com/api/goods/search?query=' . $info['search'] . '&title=' . $info['title'];

        $response->url = $url;
        $response->info = $info;

        $this->renderSmarty();
    }
}