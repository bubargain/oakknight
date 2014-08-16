<?php
namespace sprite\mvc;

use sprite\exception\DataAssert;
use \Smarty;
use \Exception;

class SmartyView implements iView {
	
	private $_response;
	private $_template;
	private static $_smarty;
	private $_layoutDir = 'layout';

	public function __construct($response) {
        $this->_response = $response;
        self::$_smarty = self::getSmarty();

	}
	
	/**
	 * 取得当前的smary
	 * @return \Smarty
	 */
	public static function getSmarty() {
		//include YEPF_PATH . "/core/smarty/Smarty.class.php";
		
		if (!self::$_smarty) {
			include __DIR__.'/../shared/smarty/Smarty.class.php';
			DataAssert::assertTrue(class_exists('Smarty'), new Exception('没有包含smarty类库'));
			self::$_smarty = new Smarty();
		}
		return self::$_smarty;
	}
	
	
	/**
	 * smary assign
	 * @param string $varname
	 * @param array $var
	 */
	public function assign($varname, $var)	{
		self::$_smarty->assign($varname,$var);
	}
	
	/**
	 * 渲染smarty模版文件
	 * @param string $file 指定的模版文件
	 */
	public function render($file) {
		foreach (get_object_vars($this->_response) as $key=>$value) {
			self::$_smarty->assign($key, $value);
		}
		self::$_smarty->display($file);
	}
	
	/**
	 * 渲染smarty模版布局文件
	 * @param string $action_file 指定的布局文件
	 */
	public function layout($layoutFile) {
		foreach (get_object_vars($this->_response) as $key=>$value) {
			self::$_smarty->assign($key, $value);
		}
		$layout_template =  $this->_layoutDir.'/'.$layoutFile.'.html';
        self::$_smarty->display($layout_template);
	}
}
