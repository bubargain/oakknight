<?php
namespace sprite\redis;

use \Redis;
use \sprite\lib\Debug;

/**
 * 扩展了的pdo
 *
 */
class Redisext extends Redis {

	public function __construct($host, $port, $passowrd) {
		$this->_host = $host;
		$this->_port = $port;
		$this->_passowrd = $passowrd;
		$this->connect($host, $port);
	}

	public function connect($host, $port) {
		parent::__construct();
        parent::connect($host, $port);
        if($this->_passowrd) {
            $this->auth($this->_passowrd);
        }
	}

	public function reconnect() {
		$this->connect($this->_host, $this->_port);
	}

	public function ping() {
		return $this->ping();
	}

    public function set($key, $value) {
        $begin_microtime = Debug::getTime();
        $out = parent::set($key, $value);
        Debug::db($this->_host . ':' . $this->_port, 'redis:SET', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function get($key) {
        $begin_microtime = Debug::getTime();
        $out = parent::get($key);
        Debug::db($this->_host . ':' . $this->_port, 'redis:GET', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function del($key) {
        $begin_microtime = Debug::getTime();
        $out = parent::del($key);
        Debug::db($this->_host . ':' . $this->_port, 'redis:DEL', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function lrem($key, $value, $count = 0) {
        $begin_microtime = Debug::getTime();
        $out = parent::lrem($key, $value, $count);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LREM', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function lPush($key, $value) {
        $begin_microtime = Debug::getTime();
        $out = parent::lPush($key, $value);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LPUSH', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function rPush($key, $value) {
        $begin_microtime = Debug::getTime();
        $out = parent::rPush($key, $value);
        Debug::db($this->_host . ':' . $this->_port, 'redis:RPUSH', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function lrange($key, $start, $stop) {
        $begin_microtime = Debug::getTime();
        $out = parent::lrange($key, $start, $stop);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LREM', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function lPop($key) {
        $begin_microtime = Debug::getTime();
        $out = parent::lPop($key, $value, $count);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LREM', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function rPop($key) {
        $begin_microtime = Debug::getTime();
        $out = parent::rPop($key);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LREM', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function rpoplpush($source, $destination) {
        $begin_microtime = Debug::getTime();
        $out = parent::rpoplpush($source, $destination);
        Debug::db($this->_host . ':' . $this->_port, 'redis:RPOPLPUSH', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }

    public function lLen($key) {
        $begin_microtime = Debug::getTime();
        $out = parent::lLen($key);
        Debug::db($this->_host . ':' . $this->_port, 'redis:LREM', $key, Debug::getTime() - $begin_microtime, $out);
        return $out;
    }
}
