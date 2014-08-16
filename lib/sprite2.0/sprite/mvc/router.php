<?php
namespace sprite\mvc;

use \Exception;
/**
 * 
 */

class Router {
	
	private $_cfg;
	
	public function __construct(array $cfg) {
		$this->_cfg = $cfg;
	}
	
	public function urlto($url) {
		$app = array();
		$request = Request::getInstance();
		foreach ($this->_cfg as $item) {
			$rule = preg_replace('|\{([\w_\d]+)\}|', '([\w_\d]+)', $item);			
			if (!preg_match("`$rule`", $url, $urlMatches))
				continue;

			
			if (!preg_match_all('|\{([\w_\d]+)\}|', $item, $routMatches))
				continue;
			
			foreach ( $routMatches[1] as $k=>$v) {
				$app[$v] = $urlMatches[$k+1];
			}
			$request->ModifyGet($app);
			break;
		}
		
		return array($request->_c, $request->_a); //return controller, action
	}
	
	public function tourl(array $items) {
		$query = $items;
		$out = '';
		foreach($this->_cfg as $url) {
			if (!preg_match_all('|\{([\w_\d]+)\}|', $url, $matches))
				continue;

			$outMatch = false;
			foreach ($matches[1] as $v) {
				if (isset($items[$v])) {
					$search[] = '{'.$v.'}';
					$replace[] = $items[$v];
					unset($query[$v]);
				} else {
					$outMatch = true;
					break;
				}				
			}
			
			if ($outMatch)
				continue;
			
			$out = str_replace($search, $replace, $url);
			break;
			if (!empty($query)) {
				$query = array_merge($_GET, $query);
				$out .= '?'.http_build_query($query);
			}
			return $out;
		}
		
		$query = array_merge($_GET, $query);
		$queryStr = http_build_query($query);
		$url = '/';
		if ($out)
			$url = $out;

		
		return $queryStr? $url.'?'.$queryStr: $url;	
	}
		
}

/*
$cfg = array(
	'/{_a}/{_c}/{id}/{p}.html',
	'/{_a}/{_c}/{id}.html',
);
$url = 'http://www.yoka.com/action/controller/2.html';
$router = new Router($cfg);
//$r = $router->urlto($url);
$r = $router->tourl(array('_c'=>'controller', 'id'=>'3','_a'=>'action', 'u'=>'livivi'));
var_dump($r);
*/
