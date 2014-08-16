<?php
namespace sprite\cache;

use \Memcache;
use \sprite\lib\Debug;

/**
 * 扩展了的pdo
 *
 */
class Memcacheext extends \Memcache {
    private $_hosts = array();
    public function addServers($list) {
        foreach($list as $row) {
            $this->_hosts[] = $row['host'];
            parent::addserver($row['host'], $row['port']);
        }
    }

    public function set($key, $var, $flag = MEMCACHE_COMPRESSED, $expire = 30) {
        $begin_microtime = Debug::getTime();
        $ret = parent::set($key, $var, MEMCACHE_COMPRESSED, $expire);
        Debug::cache($this->_hosts, $key, Debug::getTime() - $begin_microtime, $ret);
        return $ret;
    }

    public function get($keys) {
        $begin_microtime = Debug::getTime();
        $ret = parent::get($keys);
        Debug::cache($this->_hosts, $keys, Debug::getTime() - $begin_microtime, $ret);
        return $ret;
    }
}
