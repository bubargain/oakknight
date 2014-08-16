<?php
/**
 * @author liweiwei
 * smarty 注册类
 *
 */
namespace app\common\util;

class SmartyTpl {
	public static $test = 'hello world';
	
	/**
	 * @param long $time 毫秒级unix timestamp
	 */
	public static function shortTimeString($time, $format='Y-m-d') {
		$r = '';
		$time = ceil($time/1000);
		$v = time() - $time;
		if ($v>86400*10) {
			$r = date($format, $time);
		} else if ($v>86400) {
			$r  = ceil($v/86400).'天前';
		} else if ($v>3600) {
			$r = ceil($v/3600).'小时前';
		} else if ($v>60) {
			$r = ceil($v/60).'分钟前';
		} else if ($v>1) {
			$r = $v.'秒前';
		} else {
			$r = '刚刚';
		}
		
		return $r;
	}
	
	/**
	 * 三元运算
	 */
	public static function ifis($a, $b, $c=null) {
		if ($c===null)
			return $a? $a:$b;
			
		return $a? $b:$c;
	}
	
	/**
	 * 取用户头像
	 * @param string $uid
	 * @param int $width
	 * @param int $height
	 * @return string user header url
	 */
	public static function getAvatar($uid, $width=null, $height=null, $time='') {
		$x = (int)$uid%128;
		$y = (int)$uid%64;
		
		if ($time=='') {
			if (isset($_SERVER['_uid']) && $_SERVER['_uid']==$uid)
				$time = $_SERVER['_utime'];
		}
		if ($time)
			$time = '?'.$time;
		
		
		if ($width && $height)
			return $_SERVER['IMAGE_STORE_URL']."/fanhead/{$x}/{$y}/{$uid}_{$width}_{$height}.jpg".$time;
		else
			return $_SERVER['IMAGE_STORE_URL']."/fanhead/{$x}/{$y}/{$uid}.jpg".$time;
		
	}
	
	/**
	 * @param string $content
	 * @return string
	 */
	public function text2image($content) {
		$face_arr = array(
				"[呵呵]"=>"/img/face/d1c48408bf75b807fb68790f97ee318a.gif",
				"[嘻嘻]"=>"/img/face/76dd05ab2e8f63ec0105a46828406f35.gif",
				"[哈哈]"=>"/img/face/9050c065b3fd57559cddb62f0f21e36b.gif",
				"[颓]"=>"/img/face/2b99c07e0c10fb3cdad4325b5ee16671.gif",
				"[发呆]"=>"/img/face//70d318a0d452a18147d10587abfbeb45.gif",
				"[色]"=>"/img/face/3e2bc36fb40f1189a4d30496bdcb5496.gif",
				"[汗2]"=>"/img/face/605e605c1522f34f016280df8bac00c4.gif",
				"[强]"=>"/img/face/47098865dab99055febd19540ba922dd.gif",
				"[弱]"=>"/img/face/14fb94f9cc8bf26151ff8b805585fd5c.gif",
				"[玫瑰]"=>"/img/face/9a163a4fe7025599018c8a8aa848518e.gif",
				"[凋谢]"=>"/img/face/b2897e49a4deebf208dc3401dbb75c17.gif",
				"[黑线]"=>"/img/face/7170505917da8829d3ea9f2fd354fa76.gif",
				"[怒]"=>"/img/face/92d8fcdf5d035678be144d167121fcf7.gif",
				"[奥特曼]"=>"/img/face/3748faebd31568c5807fbc46ebf72804.gif",
				"[衰]"=>"/img/face/60dddbc12f8f60a9877e1647a80a28ae.gif",
				"[惊呆]"=>"/img/face/2f10895705934f4a1afbd5f3d329e4a4.gif",
				"[思考]"=>"/img/face/a0fb654e2bfbe9c253805a5e22ac26a6.gif",
				"[发怒]"=>"/img/face/4718a10064eda1741c096f4b26045846.gif",
				"[流泪]"=>"/img/face/430088e7fd74ffcf6758360835be84c9.gif",
				"[爱心]"=>"/img/face/8b4d5f35bbf1008b99e400910a16f00c.gif",
				"[右哼哼]"=>"/img/face/32e95cb9071f76d012c02d356f070010.gif",
				"[左哼哼]"=>"/img/face/600bc5e305d5ae214c4cca5cb3776bd1.gif",
				"[赞]"=>"/img/face/5e7b9eaa2d2611976396cc752c6c6465.gif",
				"[哼]"=>"/img/face/8f374a2c695bfdd5140f485f18feee05.gif",
		);
		foreach ($face_arr as $k=>$v) {
			$content = str_replace($k, '<img src="'.$v.'" />', $content);
		}
		
		return $content;
	}
	
	/**
	 * 缩略图地址转换,可以直接在模板里拼接，提高性能
	 * @param string $url 原图地址
	 * @param int $width
	 * @param int $height
	 * @param boolean $isCut
	 * @return string
	 */
	public static function resizeImg($url, $width='A', $height='A', $isCut=0) {
		if ($width=='A'&&$height=='A')
			return $_SERVER['IMAGE_STORE_URL'].$url;
		
		$out = $_SERVER['RESIZE_IMAGE_SERVICE'].'/'.$width.'/'.$height.$url;
		if ($isCut)
			$out .= '?cut=1';
		
		return $out;
	}
	
	public static function getLevelsImage($levels) {
		$out = array();
		if (!$levels)
			return $out;
		
		foreach ($levels as $k=>$v) {
			$out[$k]['title'] = $v;
			$out[$k]['image'] = '/img/level/'.md5($v).'.png';
		}
		
		return $out;
	}
	
	public static function getGoodsLink($goods) {
		$link = '/detail.do/';
		if ($goods->cat_id_1)
			$link .= $goods->cat_id_1.'/';
		if ($goods->cat_id_2)
			$link .= $goods->cat_id_2.'/';
		if ($goods->cat_id_3)
			$link .= $goods->cat_id_3.'/';
		if ($goods->cat_id_4)
			$link .= $goods->cat_id_4.'/';
		
		$link .= $goods->_id;
		
		return $link;
	}
}
