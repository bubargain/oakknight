<?php
namespace www\controller;

use \stdClass;

/**
 * 
 * 统计埋点的记录接口
 * @author:daniel
 */
class StatController extends AppBaseController {
	
	//导入用户操作日志
	public function logs($request,$response)
	{
		try {
			$event = $request->post('data');
            $event = urldecode($event);
			$event = json_decode($event,true);
			try{
                foreach ($event as $oneEvent)
                {
                    $info = Array();
                    $info['type'] =    $oneEvent['type'];
                    $info['action'] =  $oneEvent['action'];
                    $info['item_id'] = intval($oneEvent['goods_id']);
                    $info['info'] =   $oneEvent['extra'] ? json_encode($oneEvent['extra']) : '';
                    $info['ctime'] =   $oneEvent['timestmap'];
                    $this->userLog($info);
                }
                $this->result('handled '.count($event) .' rows');
            }catch(\Exception $e) {
                $this->error($e->getCode(),$e->getMessage());
            }
		} catch (Exception $e) {
			$this->error($e->getCode(),$e->getMessage());
		}
	}
}