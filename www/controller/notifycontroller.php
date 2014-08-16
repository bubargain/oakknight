<?php

namespace www\controller;

use \stdClass;
use \app\service\LoveSrv;


/**
 * 
 * 缺省提醒接口
 * @author daniel
 *
 */
class NotifyController extends AppBaseController {

	public function __construct($request, $response) {
        parent::__construct($request, $response);
        parent::checkLogin(); //用户必须登录 
    }
	/**
	 * set goods notification
	 * @param object $request
	 * @param object $response
	 */
	public function set($request,$response)
	{
		if($this->has_login)
		{
			//统计埋点
        	self::userLog( array('type'=>'notify', 'action'=> 'set', 'item_id'=>$request->goods_id));
        	
			try {
				$goods_id = $request->goods_id;
				$uid = $this->header['uid'];
				$notify = new \app\service\NotifySrv();	
				$notify->setNotify($uid,$goods_id);
				
				$instance = new LoveSrv();
					
					
				//更改心盒状态
				$rs = $instance->setLoveByUid($uid, $goods_id, 0);

                //增加统计日志
                if($rs)
               		 self::userLog( array('type'=>'love', 'action'=> 'like', 'item_id'=>$goods_id));
				
				$this->result(Array('result'=>'ok'));
				
			} catch (Exception $e) {
				$this->error($e->getCode(),$e->getMessage());
			}
		}
		else 
		{
			$this->error(20001,'uid and access_token not match');
		}
		
	}
	
	/**
	 *  check whether set goods notification or not 
	 */
	
	public function check($request,$response)
	{
		if($this->haslogin)
		{
			try {
				$data = Array('user_id'=>$this->header['uid'],'goods_id'=>$request->goods_id);
				$rs = \app\dao\NotifyGoodsDao::getSlaveInstance()->find($data);	
				if($rs)
					$this->result(Array('result'=>TRUE));
				else 
					$this->result(Array('result'=>FALSE));
				
			} catch (Exception $e) {
				$this->error($e->getCode(),$e->getMessage());
			}
		}
	}
}