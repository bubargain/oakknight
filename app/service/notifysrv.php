<?php
/**
 * 缺货登记相关操作
 * @author:daniel
 */
namespace app\service;
use app\service\BaseSrv;

class NotifySrv extends BaseSrv {
 
	
	/**
	 * one user set notify to one product
	 * @param int $user_id
	 * @param int $goods_id
	 */
	public function setNotify($user_id,$goods_id)
	{
		try{
			$data = Array('user_id'=>$user_id,'goods_id'=>$goods_id,'ctime'=>time());
			\app\dao\NotifyGoodsDao::getMasterInstance()->replace($data);
			return true;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}

    public function check($goods_id, $user_id) {
        if($user_id == 0)
            return false;

        $info = \app\dao\NotifyGoodsDao::getSlaveInstance()->find( array('user_id'=>$user_id, 'goods_id'=>$goods_id )  );
        return $info ? true : false;
    }
}