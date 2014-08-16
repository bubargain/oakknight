<?php
/**
 * 项目启动文件
 */
use sprite\mvc\App;
use sprite\mvc\Request;
use sprite\mvc\SmartyView;


// 初始项目相关框架
include __DIR__ . '/../../init.php';

//require("hm.php"); //百度统计代码

// 初始化smarty配置
$smarty = SmartyView::getSmarty();
$smarty->left_delimiter = '{{';
$smarty->right_delimiter = '}}';

$smarty->template_dir = __DIR__ . '/../template/';
$smarty->cache_dir = ROOT_PATH . "/../tmp/touch/cache";
$smarty->compile_dir = ROOT_PATH . "/../tmp/touch/templates_c";

// $smarty->force_compile =  true;
// $smarty->auto_literal =  false;
// $smarty->force_compile =  true;
$smarty->registerClass( 'tpl', '\app\common\util\SmartyTpl' ); // 注册smarty

/* 百度统计代码 */
//$_hmt = new _HMT("01a63d97e20ae495f99ac7e53b572d1b");
//$_hmtPixel = $_hmt->trackPageView();
//$smarty->assign("_hmtPixel", $_hmtPixel);

// 加载controlller执行
$request = Request::getInstance();
$controller = $request->_c;
$action = $request->_a;

$app_namespace = 'touch';

$e = new \app\YmallException(); // 声明业务异常
$mvc = new App( $controller, $action, $app_namespace );
//try {
	ob_start();
    $mvc->run();
   
   // \sprite\lib\Debug::show();
    ob_end_flush();
	
//} catch( \Exception $e ) {
//	var_dump($e);
	//echo '<!--'.$e->getMessage().'-->';
//	exit();
//}