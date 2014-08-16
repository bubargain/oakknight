<?php
/**
     * 本配置文件保存各开发者本地的配置数据
     * 在本配置文件中设置的值，将覆盖全局YOKA-ENV.php的配置，因此请勿提交svn
     * 重要： 请勿在本配置文件中增加YOKA-ENV.PHP中未定义的配置项
     * User: xwarrior
     * Date: 12-11-8
     * Time: 下午4:34
     */
/*
 * 开发人员可以对自己的配置文件中做任意修改提交 服务器使用软链接加载该文件，创建软链的脚本build.sh在/config 目录下 重要：
 * 如果要增加新的全局配置，请将配置值提交开发管理员jujianhua@yoka.com，以便放入开发服务器全局的YOKA-ENV.php中，否则会影响其他开发人员
 */

// ---------------YEPF框架级别常量覆盖定义----------------------------------
$_SERVER ['SPRITE_PATH'] = ROOT_PATH . '/lib/sprite2.0/sprite';

// 是否默认打开调试模式
define ( 'YEPF_IS_DEBUG', true ); // 系统默认：'yoka-inc'
                                  
// 自定义错误级别,只有在调试模式下生效（YEPF_IS_DEBUG）
define ( 'YEPF_ERROR_LEVEL', E_ALL & ~ E_NOTICE ); // 系统默认值： E_ALL & ~E_NOTICE
                                                   
// 定义使用YEPF内置的Firebug控制台显示错误，还是将错误直接显示在页面上，默认为true,即显示在页面上
define ( 'YEPF_THROW_ERROR', true );

define ( 'YEPF_DEBUG_PASS', 'yoka-inc' );


//主库配置
$_SERVER['mobile_mdb']['dsn'] = "mysql:host=127.0.0.1;dbname=ymall_mobile";
$_SERVER['mobile_mdb']['user'] = 'root';
$_SERVER['mobile_mdb']['password'] = '';
$_SERVER['mobile_mdb']['charset'] = 'utf8';


//从库配置
$_SERVER['mobile_sdb']['dsn'] = "mysql:host=127.0.0.1;dbname=ymall_mobile";
$_SERVER['mobile_sdb']['user'] = 'root';
$_SERVER['mobile_sdb']['password'] = '';
$_SERVER['mobile_sdb']['charset'] = 'utf8';

$_SERVER ['ROOT_DOMAIN'] = 'http://127.0.0.1';
$_SERVER ['APP_SITE_URL'] = 'http://127.0.0.1';

define ( 'TOUCH_YMALL', 'http://www.testmar.com' );
define ( 'ADMIN_ADDR', 'http://admin.testmar.com' );

define('ERP_SITE_URL', 'http://127.0.0.1');

/*
 * 添加配置项备注： 1.如果要增加新的配置，请将配置值提交开发管理员，放入YOKA-ENV.php中，以免影响其它开发人员
 * 2.其它开发人员增加了新的配置项时，不会自动同步到当前文件中，因此会采用系统默认值，如果需要覆盖默认值请自己添加进来
 */
// 此项目日记文件地址
define ( 'LOG_PATH', ROOT_PATH . '/../tmp/log' );
//此项目上传文件配置
define ( 'CDN_YMALL',  'http://cdn.oakknight.com/' );

define ( 'CDN_YMALL_PATH', ROOT_PATH. '/../tmp/' );
define ( 'DEFAULT_IMAGE', '/mobile/default.jpeg' );
define ( 'APP_VERSION', '2.0.1' );

