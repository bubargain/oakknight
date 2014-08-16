<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;

class ApiController extends BaseController {
	
	public function index($request,$response)
	{
		$response->title='API介绍文档';
		
		$sql = 'select api_id,name,loc,comments,author from api_intro';
		$api = \app\dao\UserDao::getSlaveInstance()->getpdo()->getRows($sql);
		
		$response->apiContent = $api;
		
		
		$this->setLayout('apifault');  //设置api的默认模板页
		$this->layoutSmarty('index');
	}

}