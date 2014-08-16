<?php
namespace app\service;
//use app\dao\WishDao;

use app\dao\UserBarDao;
use \app\dao\UserDao;
use \app\dao\UserInfoDao;
use \app\service\BaseSrv;

class UserSrv extends BaseSrv {
	/*
	 * query user info in table `ym_user_info` 
	 * @return : user info
	 * @author:daniel ma
	 */
	
	public function queryUserInfo($uid)
	{
		try {
			$rs = UserInfoDao::getSlaveInstance()->find($uid);
			return $rs;
		}
		catch (\Exception $e)
		{
			throw $e;
		}
	}
	
	/*
	 * verify whether two person are friends or not
	 */
	public function isFriend($uid1,$uid2)
	{
		$service = new \app\service\FriendSrv();
		$friendList= $service->getAll($uid1);
		if( in_array($uid2,$friendList))
		{
			return true;
		}
		else 
			return false;
	}
	
	
	/*
	 * search user loved stuff by user_id
	 * @change: 只能搜索到商品状态位=12，并且心盒状态is-delete=0的商品
	 */
	public function searchLovedStuff($user_id)
	{
		try {//增加商品分类及品牌过滤，引入搜索引擎
			$sql = "select goods_id from ym_wish_goods wg
			where wg.is_delete=0 and wg.user_id=? order by wg.ctime desc limit 100";

            $goods = $data = array();

            $list = \app\dao\WishGoodsDao::getSlaveInstance()->getpdo()->getRows($sql, array($user_id));
            if($list) {
                foreach($list as $row) {
                    $_id = intval($row['goods_id']);
                    $ids[$_id] = $_id;
                }
                if($ids) {
                    $searchSrv = new \app\service\SearchSrv();
                    $ret = $searchSrv->search(array('ids'=>$ids), 'default', 1, 100);

                    $goods = array();
                    if($ret['count']>0) {
                        foreach($ret['list'] as $g) {
                            $g['price'] = (float)$g['price'];
                            $g['market_price'] = (float)$g['market_price'];
                            $g['liked'] = 'true';

                            $goods[$g['goods_id']] = $g;
                        }
                    }
                }
                foreach($list as $k=>$g ) {
                    if(isset($goods[$g['goods_id']]))
                        $data[] = $goods[$g['goods_id']];
                }
            }
			return $data;
		}
		catch (Exception $e)
		{
			throw $e;
		}
	}
	
	/*
	 * modify user info 
	 * $params info : array , items need to be modify
	 * @return : user info
	 * @author :daniel ma
	 */
	
	public function modifyUserInfo($info)
	{
		if(isset($info['uid']) )
		{
			try {
			//更改ym_user_info
				$replace_data = Array();
				if($info['nick_name'])  $replace_data['nick_name'] =$info['nick_name'];
				if($info['birth_year']) $replace_data['birth_year']=$info['birth_year'];
				if($info['birth_month'])  $replace_data['birth_month']=$info['birth_month'];
				if($info['birth_day'])  $replace_data['birth_day']=$info['birth_day'];
				if($info['head_pic']) $replace_data['avator']=$info['head_pic'];
				if(count($replace_data)>0)
				{
					UserInfoDao::getMasterInstance()->edit($info['uid'],$replace_data);  //更新信息
				}	
				//更改ym_user
				
				if($info['pass']) //修改密码
				{ 
					$where['user_id']=$info['uid']; 
					$where['password'] ='';
					$editOp = UserDao::getSlaveInstance() ->find($where); //如果密码不为空，不能重置
					if($editOp == false)
						throw new \Exception ('Password is not empty,can not reset now',21311);					
					$replace_pass= Array('password'=>md5($info['pass']));
					UserDao::getMasterInstance() -> edit($where,$replace_pass);
					
				}
				
				return UserInfoDao::getMasterInstance()->find($info['uid']);
				 
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}
		else
		{
			throw new \Exception('uid and access_token params are neccessory',21309);
		}
	}
	
	/*
	 * modify user info for touch
	* $params info : array , items need to be modify
	* @return : user info
	* @author :dengwei
	*/
	
	public function modifyUserInfoForTouch($info)
	{
		if(isset($info['user_id']) )
		{
			try {
				//更改ym_user_info
				$replace_data = Array();
				if($info['nick_name'])  $replace_data['nick_name'] =$info['nick_name'];
				if($info['birth_year']) $replace_data['birth_year']=$info['birth_year'];
				if($info['birth_month'])  $replace_data['birth_month']=$info['birth_month'];
				if($info['birth_day'])  $replace_data['birth_day']=$info['birth_day'];
				if($info['head_pic']) $replace_data['avator']=$info['head_pic'];
				if(count($replace_data)>0)
				{
					UserInfoDao::getMasterInstance()->edit($info['user_id'],$replace_data);  //更新信息
				}
				//更改ym_user
				if($info['pass']) //修改密码
				{
					$where['user_id']=$info['user_id'];
					$editOp = UserDao::getSlaveInstance() ->find($where);
					if($editOp == false)
						 throw new \Exception( 'user is not exist', 10001);
					$replace_pass= Array('password'=>md5($info['pass']));
					UserDao::getMasterInstance() -> edit($where,$replace_pass);
						
				}
				return UserInfoDao::getMasterInstance()->find($info['user_id']);
			}
			catch (\Exception $e)
			{
				throw $e;
			}
		}
		else
		{
			throw new \Exception('user_id params are neccessory',21309);
		}
	}
	
	/**
	 * User register (will add in three tables)
	 * @param username : string, user phone number
	 * @param clientid：  device id
	 * @return  new user_id if username isn't exsit, or return "phone number already be registered"
	 * @author:　daniel 
	 */
	public function addUserByUserName($username,$clientid) {
        try{
            $info = self::addUser(array('user_name'=>$username, 'uuid'=>$clientid));
            return array('uid'=>$info['user_id']);
        }
        catch(\Exception $e) {
            throw $e;
        }
	}
	
	
	/*
	 * set new token after login
	 * @param $username : string, user phone number
	 * @return Array including uid and access_token if succeed
	 * @author :daniel ma
	 */
	public function setTokenAfterLogin($username)
	{
        $where = array('user_name'=>$username);
        $info = UserDao::getSlaveInstance()->find($where);
        if(!$info)
            throw new \Exception( $username .' is not exist', 10001);

        $time = time();
		$result = array('token'=>md5($username . $time), 'utime'=>$time);
        UserInfoDao::getMasterInstance()->edit($info['user_id'], $result);

        
        $search_pass = UserDao::getSlaveInstance()->find($info['user_id']);
        if($search_pass['password'] == '')
        	$has_password = 0;
        else 
        	$has_password = 1;
        
        return array('uid'=>$info['user_id'], 'accesstoken'=>$result['token'],'has_password'=>$has_password);
	}
	
	
	/**
	 * check whether input is a phone number or not
	 * @auther: daniel Ma
	 * @return True or False
	 */
	private function checkPhoneNum($username)
	{
		//弱验证，是否为纯数字
        if(!preg_match('/^1[0-9]{10}$/', $username))
            throw new \Exception('Username you input is not phone number','21306');
	}
	
	/**
	 * check whether username and password match or not
	 * @param username: string
	 * @param password: string
	 * @return : user_id and access_token if login succeed, or return error info
	 * @author: daniel ma
	 */
	public function checkLogin($username,$pass) {
        $info = UserDao::getSlaveInstance()->find(array('user_name'=>$username, 'password'=>$pass));
		if(!$info)
			throw new \Exception("Username and password not match", "21303");

        return self::setTokenAfterLogin($username);
	}

    public function addUser($info) {
        self::checkPhoneNum($info['user_name']);
        if( UserDao::getSlaveInstance()->find(array('user_name'=>$info['user_name'])) )
            throw new \Exception( $info['user_name'] .' is exist now', 10001);

        $_time = time();
        if( !$info['nick_name'] )
            $info['nick_name'] = '礼物店'.substr($info['user_name'], -4);

        $info['token'] = md5($info['user_name'] . $_time);

        if(!isset($info['password']))
            $info['password'] = '';

        try{
            UserDao::getMasterInstance()->beginTransaction();
            $info['user_id'] = UserDao::getMasterInstance()->add( array('user_name'=>$info['user_name'], 'password'=>$info['password'], 'ctime'=>$_time) );

            $info['ctime'] = $info['utime'] = $_time;
            unset($info['password']);

            UserInfoDao::getMasterInstance()->add($info);

            UserDao::getMasterInstance()->commit();
        }
        catch(\Exception $e) {
            UserDao::getMasterInstance()->rollBack();
            throw $e;
        }
        return $info;
    }
}