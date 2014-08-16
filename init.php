<?php
/**
 * 项目框架初始化文件
 * 本文件不包含任何配置信息，项目的所有配置在/config/local.config.php中
 * 开发期间，每个人拥有自己的local.config.php,在/config/用户名.local.config.php 文件中修改
 */
define('ROOT_PATH', __DIR__);



//load local application config
include ROOT_PATH . '/config/local.config.php';
include ROOT_PATH . '/config/cache.config.php';

if( !defined('LOG_PATH') ){
    define('LOG_PATH',$_SERVER['XHPROF_ROOT']);
}

//加载sprite框架(必须在yepf2之后加载)
include $_SERVER['SPRITE_PATH'].'/autoload.php';
//echo $_SERVER["DB_CONFIG"];die();
//echo "h1: " ;die();

$autoPath = __DIR__;
$path = get_include_path();
if (strpos($path.PATH_SEPARATOR, $autoPath.PATH_SEPARATOR) === false){
    set_include_path($path.PATH_SEPARATOR.$autoPath);
}
spl_autoload_register('spl_autoload');

/*
$_REQUEST['debug'] = 'yoka-inc2';
$_REQUEST['debug'] = 'yoka-inc3';
$_REQUEST['debug'] = 'yoka-inc4';
*/
$_REQUEST['debug'] = 'yoka-inc4';

if((defined('YEPF_IS_DEBUG') && YEPF_IS_DEBUG) || (isset($_REQUEST['debug']) && strpos($_REQUEST['debug'], YEPF_DEBUG_PASS) !== false))
{
	//Debug模式将错误打开
	ini_set('display_errors', true);
	//设置错误级别
	error_reporting(YEPF_ERROR_LEVEL);
	//Debug开关打开
	\sprite\lib\Debug::start();
}
