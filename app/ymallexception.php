<?php
/**
 * 
 * @author imlivv@gmail.com
 * @date 2012-12-10
 */
namespace app;

use \Exception;

final class YmallException extends Exception{}
//登录
class ENoLogin extends Exception{};
//用户不存在
class EUserNotExists extends Exception{}
//没操作权限
class ENoPrivilege extends Exception{}