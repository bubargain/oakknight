<?php
namespace sprite\lib;

/**
 * 获取ip
 *
 */
class Ip {

	public static function getIp() {
		if (getenv("HTTP_X_FORWARDED_FOR"))
			return  getenv("HTTP_X_FORWARDED_FOR");
		else if (getenv("HTTP_CLIENT_IP"))
			return getenv("HTTP_CLIENT_IP");
		else if (getenv("REMOTE_ADDR"))
			return  getenv("REMOTE_ADDR");
		else
			return "Unknown";
	}
}