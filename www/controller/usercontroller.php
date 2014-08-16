<?php 
namespace www\controller;
use app\dao\PushDao;
use app\dao\PushTokenDao;
use app\dao\UserDao;

use app\service\UserSrv;
use app\service\VerifySrv;

/*
 * use to handle user related operation
 * @auther: Daniel Ma
 */
class userController extends AppBaseController
{
	//default action with notification
	public function index($request,$response)
	{
		echo "This is user login API. <br/>";
		echo "You need to call proper action";
	}
	
	/*
	 * API: user/login
	 * API doc:  http://mobile.ymall.com/api_intro/userLogin.htm
	 * @param username : string, $request
	 * @param pass : string ,$request
	 */
	public function login($request,$response)
	{	
		
			if($request->user_name && $request->pass)
			{
			
				$username=$request->user_name;
				$pass =$request->pass;
				
				try{
					$user = new UserSrv();
					$rs= $user->checkLogin($username,md5($pass));
					
					$this->result($rs);	
				}
				catch (\Exception $e)
				{
				
					if($e->getCode() == 21303 )
					{
						$rs['status'] = $e->getCode();
						$rs['error_info'] = $e -> getMessage();
						$rs['error_detail'] = "用户名或密码错误，请重新填写";
						$this->renderJson($rs);
					}
					else 
					{
						$rs['status'] = $e->getCode();
						$rs['error_info'] = $e -> getMessage();
						$rs['error_detail'] = "系统错误，请重试";
						$this->renderJson($rs);
					}	
				}
	 
			}
			else { //username or password can't be empty
					
					$rs['status'] = 21304;
					$rs['error_info'] = "username and password can't be empty";
					$rs['error_detail'] = "用户名/密码不能为空";
					$this->renderJson($rs);
			}
	}
	
	/**
	 * 
	 * use logout
	 * delete push_token
	 * @param object $request
	 * @param object $response
	 */
	
	public function logout($request,$response)
	{
		if($this->has_login)
		{
			//清除push_token
			$user_id = $this->header['uid'];
			\app\dao\UserInfoDao::getMasterInstance()->edit($user_id,Array('push_token'=>''));
			$this->result(array('result'=>true));
		}
		else 
			$this->error(20320,'uid and access_token not match','用户未登录或已经退出');
	}
	
	
	public function check($request,$response)
	{
		try{
			if($request->user_name  ) 
			{
				$user_name = $request->user_name;
				$rs=UserDao::getSlaveInstance()->findByField('user_name',$user_name);
				if($rs)
				{
					$data=Array();
					$data['has_password'] = true;
					if($rs[0]['password'] == '')
						$data['has_password'] = false;
					$data['exist'] = true;	
					$this->result($data);
					
				}
				else {
					$this->result(Array('exist'=>False));
				}
			}
		}
		catch(Exception $e)
		{
			$rs = Array();
			$rs['status'] = $e->getCode();
			$rs['error_info'] = $e -> getMessage();
			$rs['error_detail'] = "内部错误";

            $this->error($rs['status'], $rs['error_info'], $rs['error_detail']);
		}
	}
	
	/*
	 * API: user/create
	 * API doc: http://192.168.52.158/api_intro/userCreate.htm
	 * create a new user by phone number
	 * @author: Daniel Ma
	 */
	public function create($request,$response)
	{
		try{
			if($request->user_name && $this->header['clientid'] )  //需更改成从header获取
			//if($request->get('user_name') && $request->get('clientid') )
			{
				$userBehavior =new UserSrv();
                $info = array('user_name'=>$request->user_name, 'uuid'=>$this->header['clientid'], 'source'=>1);
                $user = self::addUser($info);

                $this->result( array('uid'=>$user['user_id']) );
			}
			else {
				$rs = Array();
				$rs['status'] = 21308;
				$rs['error_info'] = "Didn't get required params";
				$rs['error_detail'] = "传入参数不全";
                $this->error($rs['status'], $rs['error_info'], $rs['error_detail']);
			}
		}
		catch (\Exception $e)
		{
			$rs = Array();
			$rs['status'] = $e->getCode();
			$rs['error_info'] = $e -> getMessage();
			$rs['error_detail'] = "内部错误";

            $this->error($rs['status'], $rs['error_info'], $rs['error_detail']);
		}
	}
	
	/*
	 * API: user/info
	 * API DOC: http://192.168.52.158/api_intro/userInfo.htm
	 * Used to query and modify user basic infomation
	 */
	public function info($request,$response)
	{
			if(!$this->isPost())
			{
				//echo "im here";
				$this-> queryInfo($request,$response);
			}
			else
			{
				
				$this-> setInfo($request,$response);
			}
	}
	
	/*
	 *  query user info by uid and accesstoken
	 *  @author : daniel
	 */
	protected  function queryInfo($request,$response)
	{
		try{
			$uid =(int)$this->header['uid'];
			if ($uid)
			{
				
				$userOper = new UserSrv();
				$data = $userOper->queryUserInfo($uid);
				$this->result($data);
			}
			else {
				$rs = Array();
				$rs['status'] = 21310;
				$rs['error_info'] = 'Did not get right header';
				$rs['error_detail'] = "header头信息不符合要求";
				$this->renderJson($rs);
			}
		}
		catch (Exception $e)
		{
			$rs = Array();
			$rs['status'] = $e->getCode();
			$rs['error_info'] = $e->getMessage();
			$rs['error_detail'] = "内部错误";
			$this->renderJson($rs);
		}
	}
	
	/*
	 * set or modify user info
	 * Api doc: http://192.168.52.158/api_intro/userInfo.htm
	 * @author :daniel
	 */
	protected  function setInfo($request,$response)
	{
		try{
			$info = Array();
			$info['uid'] = $this->header['uid'];
			if ($request->post('nick_name'))  $info['nick_name'] = $request->post('nick_name');
			if ($request->post('birth_year'))  $info['birth_year'] = $request->post('birth_year');
			if ($request->post('birth_month'))  $info['birth_month'] = $request->post('birth_month');
			if ($request->post('birth_day'))  $info['birth_day'] = $request->post('birth_day');
			if ($request->post('head_pic'))  $info['head_pic'] = $request->post('head_pic');
			if ($request->post('pass'))  $info['pass'] = $request->post('pass');
			
			$userOper = new UserSrv();
			$rs=$userOper->modifyUserInfo($info);//修改信息
			$this->result($rs);
				
		}
		catch (\Exception $e)
		{
			$rs = Array();
			$rs['status'] = $e->getCode();
			$rs['error_info'] = $e -> getMessage();
			$rs['error_detail'] = "内部错误";
			$this->renderJson($rs);
		}
	}
	
	/*
	 *  user love or unlove stuff
	 *  @author ： Daniel
	 */
	public function love($request,$response)
	{
		try 
		{
			if($this->header['uid'] )
			{
				$user_id = (int)$this->header['uid'];
				$search_id = (int)$request->get('search_id');
				
				$instance = new UserSrv();
			
				if($user_id == $search_id || $instance->isFriend($user_id,$search_id))
				{
					$rs = $instance->searchLovedStuff($search_id);
					
					$this->result( $rs );
				}
				else 
				{
					$this->result(array()); // 不是好友，看到对方心盒表为空
				}
			}
			else 
			{
				$this->error(213001,'user did not login','用户未登录');
			}
		}
		catch (Exception $e) {
			$this->error($e->getCode(),$e->getMessage(),"内部错误");
		}
	}

    /**
     * @param $request
     * @param $response
     * @throws \Exception
     * @desc 更新设备push token
     */
    public function push($request, $response) {
        try{
            if(!$this->header['clientid'])
                throw new \Exception('请输入设备号', 5000);

            $info = PushTokenDao::getSlaveInstance()->find( array('uuid'=>$this->header['clientid']) );
            $_time = time();
            if($info) {
                PushTokenDao::getMasterInstance()->edit( $this->header['clientid'], array('push_token'=>$request->token, 'utime'=>$_time) );
            }
            else {
                PushTokenDao::getMasterInstance()->add( array( 'uuid'=>$this->header['clientid'], 'push_token'=>$request->token, 'ctime'=>$_time, 'utime'=>$_time) );
            }

            $data = array( 'uuid'=>$this->header['clientid'], 'push_token'=>$request->token);
            if($this->has_login)
                \app\dao\UserInfoDao::getMasterInstance()->edit($this->current_user['user_id'], $data);

            $this->result(array());

        } catch (\Exception $e) {
            $this->error(50000, 'token更新失败');
        }
    }
}
