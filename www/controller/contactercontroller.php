<?php
namespace www\controller;


/**
 * 
 * 用于用户通讯录导入
 * @author daniel
 *
 */
class ContacterController extends AppBaseController {
	
	/**
	 * 
	 * 批量导入用户通讯录
	 * @param object $request
	 * @param object $response
	 */
	
	public function import($request,$response)
	{
		//echo '123';
		//var_dump(json_encode(Array(0=>Array('first_name'=>'daniel','last_name'=>'ma'))));die();
		if($this->header['uid'])
		{
			try {
				
			$data = $request->post('data');
			//$data ='[{"home_phone":"","first_name":"荫涛","last_name":"张","blog_index":"","email":"","birthday":611928000,"company":"凯铭风尚","nick_name":null,"department":null},{"home_phone":"","first_name":"四","last_name":"李","blog_index":"","email":"","birthday":0,"company":"百度","nick_name":null,"department":null},{"home_phone":"","first_name":"五","last_name":"王","blog_index":"","email":"","birthday":0,"company":"新浪","nick_name":null,"department":null},{"home_phone":"","first_name":"总","last_name":"张","blog_index":"","email":"","birthday":0,"company":"哈哈","nick_name":null,"department":null}]';
			$dataToArray = json_decode($data,true);
			
			\app\dao\ContacterDao::getMasterInstance()->beginTransaction(); //开启事务
			foreach ($dataToArray as $oneRow)
			{
				$contact = Array();
				if(count($oneRow) > 0)
				{
					$contact['user_id']=$this->header['uid'];
					
					foreach($oneRow as $key => $value)
					{
	
						$contact[$key]=$value?$value:'##';
					}
				}
				//var_dump($contact);
				\app\dao\ContacterDao::getMasterInstance()->replace($contact);
			}
			\app\dao\ContacterDao::getMasterInstance()->commit();
			$this->result('handled number equals '. count($dataToArray));
			} 
			catch (Exception $e) 
			{
				$this->error(10003,'data format or values not right','传入数据异常');
			}
		}
		else {
			  $this->error(20010,'we need uid','需要uid');
		}
	}
}