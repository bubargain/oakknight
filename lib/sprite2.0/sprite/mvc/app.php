<?php
namespace sprite\mvc;

use \sprite\exception\BizException;
use \sprite\lib\Auth;
use \sprite\lib\Config;
use \Exception;

/**
 * 简单的mvc实现
 *
 */
class App {

	private $_controller = 'index';
	private $_action = 'index';
	private $_app_namespace = '';

	public function __construct($controller, $action, $app_namespace = '') {
		if ($controller)
			$this->_controller = $controller;
		if ($action)
			$this->_action = $action;
 		if($app_namespace)
			$this->_app_namespace = $app_namespace;
	}

	/**
	 * 启动一个controller，执行指定的action方法，渲染controller/action模版
	 */
	public function run() {
		$request = Request::getInstance();
		$response = new Response();
		$response->_controller = $this->_controller;
		$response->_action = $this->_action;

		$controller = $this->_app_namespace ."\\controller\\". $this->_controller.'controller';
        //echo $controller;
		if (!class_exists($controller))
			throw new Exception("no controller called $controller ");

		$obj = new $controller($request, $response);
		if (!method_exists($obj, $this->_action))
			throw new Exception("'$controller' has not method '{$this->_action}' ");

		$obj->befor($this->_controller, $this->_action);
		$obj->{$this->_action}($request, $response);
		$obj->after($this->_controller, $this->_action);
	}


}
