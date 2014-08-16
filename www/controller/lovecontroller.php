<?php
namespace www\controller;

use app\service\LoveSrv;

class LoveController extends AppBaseController {
	
	public function __construct($request,$response)
	{
		parent::__construct ($request,$response);
        parent::checkLogin();
	}
	
	//用户设置心盒 	
	public function set($request,$response)
	{
		try {
            $is_delete  = $request->type == 'love' ? 0 : 1;

            $instance = new LoveSrv();
            //更改心盒状态
            $rs = $instance->setLoveByUid( $this->current_user['user_id'], $request->goods_id, $is_delete);
            if(!$rs)
                throw new \Exception('内部错误', 10000);

            if($rs['status']) //增加统计日志
                self::userLog( array('type'=>'love','action'=> $is_delete ? 'unlike' : 'like', 'item_id'=>$request->goods_id));

            $this->result($rs);
		}
		catch(\Exception $e) {
			$this->error(10000,'error','内部错误');
		}
	}

    public function goods($request,$response) {
        $start = $request->get('start', 0);
        $size = $request->get ( 'size', 20 );

        try{
            $LoveSrv = new LoveSrv();
            $total = $LoveSrv->getMyCnt( $this->current_user['user_id'] );

            $ret = array('total'=>$total, 'size'=>$size, 'list'=>array());
            $list = array();
            if($total && $start <= $total) {
                $limit = '' . $start . ',' . $size;
                $sort = 'w.ctime desc';
                $ret['list'] = $LoveSrv->getMyList( $this->current_user['user_id'], $limit, $sort );
            }

            $ret['next'] = $total > ($start + $size) ? true : false;

            $this->result($ret);
        }
        catch(\Exception $e) {
            $this->error(10002,'error','内部错误');
        }
    }
}