<?php
/**
 * 
 * @author li.weiwei@163.com
 * @date 2013-03-28
 */
namespace app\common\util;

use sprite\lib\ShortUrl;
use app\dao\ShortDao;

class short {
	const PERFIX = 'http://t.ymall.com/s/';
	
	public static function generate($url) {
		if (!$url)
			return false;
		
		$short = ShortDao::getSlaveInstance()->find(array('url'=>$url));
		if ($short)
			return self::PERFIX.$short['short'];
		
		$shorts = ShortUrl::geterateAll($url);

		foreach ($shorts as $v) {
			try {
                $r = ShortDao::getMasterInstance()->add( array('short'=>$v, 'url'=>$url, 'utime'=>time()) );
			} catch (\Exception $e) {
				continue;
			}
			if ($r)
				return self::PERFIX.$v;
		}
		return false;
	}

	public static function get($shortUrl) {
		$p = ShortDao::getSlaveInstance()->find(array('short'=>$shortUrl));
		
		return $p? $p['url']:'';
	}
}