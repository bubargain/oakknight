<?php
namespace sprite\lib;

/**
 * 图片缩略服务，前提得有php Imagick 扩展
 *
 */
class ThumbImg {
	
	/**
	 * 只用于gif图片压缩
	 * @param string $imgFrom old img
	 * @param string $imgTo new img
	 * @param int $w width
	 * @param int $h height
	 */
	public static function thumbGif($imgFrom, $imgTo, $w, $h) {
		$image = new Imagick($imgFrom);
		$image = $image->coalesceImages();
		foreach ($image as $frame) {
			$frame->thumbnailImage($w, $h);
		}
		$image = $image->optimizeImageLayers();
		$image->writeImages($imgTo, true);
	}
}

?>