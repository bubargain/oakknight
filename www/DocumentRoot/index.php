<?php
/**
 * fan项目启动文件
 */
use sprite\mvc\App;
use sprite\mvc\Request;
use sprite\mvc\SmartyView;

//初始项目相关框架
include __DIR__.'/../../init.php';

//加载controlller执行

$request = Request::getInstance();
$controller = $request->_c;
$action = $request->_a;

$app_namespace = 'www';
new \app\YmallException(); //声明业务异常
$mvc = new App($controller, $action, $app_namespace);

// 初始化smarty配置
$smarty = SmartyView::getSmarty ();
$smarty->left_delimiter = '{{';
$smarty->right_delimiter = '}}';

$smarty->template_dir = __DIR__ . '/../template/';
$smarty->cache_dir = ROOT_PATH . "/../tmp/www/cache";
$smarty->compile_dir = ROOT_PATH . "/../tmp/www/templates_c";

try {
    ob_start();
    $mvc->run();
    \sprite\lib\Debug::show();
    ob_end_flush();
} catch (\Exception $e) {
	//echo '<!--'.$e->getMessage().'-->';
	exit;
}
