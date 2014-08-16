<?php
namespace www\controller;

use \app\service\transfer\kuaidi100srv;

class TransferController extends AppBaseController{
	
	
	/**
	 * postOrder call back info from kuaidi100
	 * Chech wether post right
	 */
	public function callBack($request,$response)
	{
		$data= $request->param;
		
		/*$data = '{
	"status":"polling",	  
	"billstatus":"got",	 
	"message":"",		
	
    "lastResult":{		 
		"message":"ok",  
		"state":"0",    
		"status":"200",         
		"condition":"F00",		
		"ischeck":"0",			
		"com":"yuantong",      
		"nu":"V030344423" }}   
		'; */
		
		$res['result'] = "true";  //response content
		$res['returnCode'] = "200";
		$res['message'] = "成功";
		
		$param = \json_decode($data,true);
		if(isset($param['status']) &&  ( $param['status'] == "polling" || $param['status'] == "shutdown")) //有效推送信息
		{
			try{
				$content = $param['lastResult'];
				$dbRow['shipping_code'] = $content['nu'];
				$dbRow['shipping_name'] = $content['com'];
				$dbRow['content'] =  json_encode($content);
				
				
				\app\dao\UserDao::getMasterInstance()->getpdo()->replace("ym_transfer_info",$dbRow); //更新快递信息
				$this->renderJson($res);
			}
			catch(Exception $e)
			{
				\sprite\lib\Log::customLog(
           		 'Transfer_auto_'.date('Ymd').'.log',
            	 '__Transfer_CallBack________'.$e->getMessage()."\n\n"
     		    );
     		    $this->renderJson($res);
				
			}
			
		}
		else {
			// do nothing
			\sprite\lib\Log::customLog(
           		 'Transfer_auto_'.date('Ymd').'.log',
            	 '__Transfer_CallBack________Didnt get body content currectly'."\n\n"
     		    );
			$this->renderJson($res);
		}
				
	}
	
	
	/**
	 * test post order
	 */
	public function testPostOrder($request,$response)
	{
		
		$sql = "select shipping_name,shipping_code,region_name from ym_order a left join ym_order_extm b on a.order_id = b.order_id  where shipping_code != '' limit 100,100"; 
		$rs = \app\dao\UserDao::getSlaveInstance()->getpdo()->getRows($sql);
		//var_dump($rs);
		foreach ($rs as $row)
		{
			$realCom = '';
			switch($row['shipping_name'])
			{
				case '圆通快递':
					$realCom='yuantong';
					break;
				case '圆通':
					$realCom='yuantong';
					break;
				case '顺丰快递':
					$realCom='shunfeng';
					break;
				case '顺丰':
					$realCom='shunfeng';
					break;
				default:
					$realCom=$row['shipping_name'];
			}
			$data['shipping_name'] = $realCom ;
			$data['shipping_code'] = $row['shipping_code'] ;
			$data['region_name']   = $row['region_name'] ;
			
			
			//var_dump($data);
			$postOrderIns = new kuaidi100srv();
			$postOrderIns->postOrder($data);
			//var_dump($row);
		}
	}
	
	
	
	/**
     * 
     * 物流信息查询
     * @param object $request : url get parameters
     * @param object $response : http response contents
     */
	public function Info($request,$response)
	{
		
		try {
			$com = $request->com;
			$realCom='';
			switch($com)
			{
				case '圆通快递':
					$realCom='yuantong';
					break;
				case '圆通':
					$realCom='yuantong';
					break;
				case '顺丰快递':
					$realCom='shunfeng';
					break;
				case '顺丰':
					$realCom='shunfeng';
					break;
				default:
					$realCom=$com;
			}
			$order_sn = $request->sn;
			$data =new kuaidi100srv();
			echo $data->innerQuery($order_sn,$realCom);
			
			
		} catch (Exception $e) {
			$this->error($e->getCode(),$e->getMessage());
		}
	} 
}
