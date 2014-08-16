<?php
/**
 * @author liweiwei * 
 * 包含本文件后，本文件的目录将成为一个根命名空间
 */

$autoPath = __DIR__.'/../';
$path = get_include_path();

if (strpos($path.PATH_SEPARATOR, $autoPath.PATH_SEPARATOR) === false)
	set_include_path($path.PATH_SEPARATOR.$autoPath);

spl_autoload_register('spl_autoload');