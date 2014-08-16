<?php
namespace www\controller;
use \app\dao\VbarDao;
use app\dao\UserVbarDao;
use \app\service\vbar\UserVbarSrv;

class VbarController extends AppBaseController
{
    public function __construct($request, $response) {
        parent::__construct($request, $response);

        #$this->current_user = array('user_id'=>1000, 'clientid'=>'0ef3194b3445159e641bc4d623c88a2d766fc01e', 'user_name'=>'18610485690');
        #$this->current_user = array('clientid'=>'0ef3194b3445159e641bc4d623c88a2d766fc01e');
    }

    public function index($request, $response) {}

    public function bars($request, $response) {
        try{
            $barSrv = new UserVbarSrv();
            $ret = $barSrv->push($this->current_user['user_id']);
            $this->result($ret);
        }catch (\Exception $e) {
            $this->error( $e->getMessage(), $e->getCode() );
        }
    }
}