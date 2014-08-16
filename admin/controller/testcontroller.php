<?php 
namespace admin\controller;

use app\dao\UserDao;

class TestController extends BaseController {

	protected $pdo;
	
	public function index()
	{
		$this->pdo = UserDao::getSlaveInstance()->getpdo();
		var_dump($this->pdo);
	
	}
	

	
	
	
	
	
}