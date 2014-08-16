<?php
namespace sprite\mvc;
use \Exception;

/**
 * 简单配置的路由分发类
 * 依赖于$_SERVER['router_config']
 * e.g.
 * 
 * 
 * 
 * @author liweiwei
 *
 * $routCfg[] = 'http://www.yoka.com/<_a>/<_c>_<id>.htmls';  => app[_a:$_a, _c:$_c, id:$id]
 * $routCfg[] = 'http://www.yoka.com/<_c>/<_a>_<id>.html';   => app[_c:$_c, _a:$_a, id:$id]
 * $routCfg[] = 'http://www.yoka.com/<_c>/<id>.html';        => app[_c:$c, id:$id]
 *
 */
use sprite\exception\DataAssert;

class SimpleRouter {
	
	/**s
	 * 从url转为分发的控制器信息数组
	 * @param string $url
	 * @return array app 
	 */
	public static function url2app($url) {
		$app = array();
		$routCfg = self::cfgFmt();
		foreach ($routCfg as $v) {
			if (preg_match_all($v['urlPattern'], $url, $matches)) {
				if (count($v['app'])+1 == count($matches)) {
					foreach ($v['app'] as $k2=> $v2) {
						$app[$v2] = $matches[$k2+1][0];
					}
					break;
				}
			}
		}
	
		return $app;
	}
	
	/**
	 * 从控制器信息数组转为静态的url
	 * @param array $app
	 * @return string url
	 */
	public static function app2url($app) {
		$url = '';
		$appKeys = array_keys($app);
		$appSortedkeys = $appKeys;
		sort($appSortedkeys);
		$routCfg = self::cfgFmt();
		foreach ($routCfg as $v) {
			$appCfg = $v['app'];
			sort($appCfg);
			if (!array_diff($appCfg, $appSortedkeys)) {
				foreach ($appKeys as &$v2) {
					$v2 = '<'.$v2.'>';
				}
				$url = str_replace($appKeys, $app, $v['urlRaw']);
				break;
			}
		}
	
		return $url;
	}
	
	public static function cfgFmt() {
		$out = array();
		DataAssert::assertNotEmpty($_SERVER['router_config'], new Exception('$_SERVER["router_config"]为空（没有配置路由规则）'));
		
		$routCfg = $_SERVER['router_config'];
		foreach ($routCfg as $v) {
			if (preg_match_all('/<(\w+)>/', $v, $matches)) {
				$tmp['urlRaw'] = $v;
				$tmp['urlPattern'] = '^'.preg_replace('/<.+?>/', '(.+?)', $v).'^';
				$tmp['app'] = $matches[1];
				$out[] = $tmp;
			}
	
	
		}
		return $out;
	}
	
}