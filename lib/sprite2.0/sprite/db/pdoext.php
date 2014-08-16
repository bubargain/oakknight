<?php
namespace sprite\db;

use \PDO;
use \PDOStatement;
use sprite\lib\Auth;
use sprite\lib\Config;
use sprite\lib\Debug;

/**
 * 扩展了的pdo
 *
 */
class PDOext extends PDO {
	private $_lastErrorInfo = '';
	private $_queryTime = '';
	private $_sth = '';
	private $_dsn = '';
	private $_userName = '';
	private $_passowrd = '';
	private $_charSet = '';
	private $_debug = false;
	
	public static $keys = array('key', 'type', 'condition', 'div', 'int1', 'int2', 'int3', 'int4', 'int8', 'status', 'from', 'keyword','desc','left'); //mysql 常用关键字
	
	/**
	 * 取得一个数据库链接
	 * @param string $dsn
	 * @param string $userName
	 * @param string $passowrd
	 * @param string $charSet
	 */
	public function __construct($dsn, $userName, $passowrd, $charSet='utf8') {
		$this->_dsn = $dsn;
		$this->_userName = $userName;
		$this->_passowrd = $passowrd;
		$this->_charSet = $charSet;
		//var_dump($userName);die();
		$this->connect($dsn, $userName, $passowrd, $charSet);
		
		if (@$_GET['debug'] == 'db' || $_SERVER['SPRITE_DEBUG'] === 'db')
			$this->_debug = true;
	}

	/**
	 * @param string $dsn
	 * @param string $userName
	 * @param string $passowrd
	 * @param string $charSet
	 */
	public function connect($dsn, $userName, $passowrd, $charSet='utf8') {
        $begin_microtime = Debug::getTime();
		parent::__construct($dsn, $userName, $passowrd);
		$this->query("set names '$charSet'");
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        Debug::db($dsn, 'dsn', 'connect', Debug::getTime() - $begin_microtime, 'ok');
	}

	/**
	 * 重新链接
	 */
	public function reconnect() {
		$this->connect($this->_dsn, $this->_userName, $this->_passowrd, $this->_charSet);
	}

	/**
	 * connect ping
	 */
	public function ping() {
		return $this->query('select 1');
	}
	
	
	/**
	 * 取查询结果的一列
	 * @param string $sql
	 * @param array $binds
	 * @return multitype:string 
	 */
	public function getCol($sql, array $binds=array()) {
		$rows = array();
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		while ($row = $sth->fetchColumn())
			$rows[] = $row;
		$sth->closeCursor();
		return $rows;
	}
	
	
	/**
	 * 取查询结果一个元素 如 [select count(1) as cnt]的 cnt
	 * @param string $sql
	 * @param array $binds
	 * @return string
	 */
	public function getOne($sql, array $binds=array()) {
		return $this->getScaler($sql, $binds);
	}
	
	
	/**
	 * 同getOne
	 * @param string $sql
	 * @param array $binds
	 * @return string
	 */
	public function getScaler($sql, array $binds=array()) {
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetchColumn();
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 取查询结果中的一行
	 * @param unknown_type $sql
	 * @param array $binds
	 * @return mixed
	 */
	public function getRow($sql, array $binds=array()) {
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetch();
		$sth->closeCursor();
		return $out;
	}
	

	/**
	 * 取查询结果集
	 * @param unknown_type $sql
	 * @param array $binds
	 * @return multitype:
	 */
	public function getRows($sql, array $binds=array()) {
		$sth = $this->prepare($sql);
		self::bindValue($sth, $binds);
		$this->execute($sth);
		$this->_lastErrorInfo = $sth->errorInfo();
		$out = $sth->fetchAll();
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 插入一条数据
	 * @param string $table
	 * @param array $data
	 * @return lastInsertId() if succeed,or boolean false
	 */
	public function insert($table, array $data) {
		$ks = array();
		foreach (array_keys($data) as $k) {
			if (in_array($k, self::$keys))
				$k = "`$k`";
			$ks[] = $k;
		}
		$sqlK = implode(', ', $ks);
		$sqlV = ':'.implode(', :', array_keys($data));
		
		$sql = "insert into $table ($sqlK) values ($sqlV)";
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth)?$this->lastInsertId():false;
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 按条件更新数据
	 * @param string $table
	 * @param array $data
	 * @param string $where
	 * @return boolean
	 */
	public function update($table, array $data, $where) {
		if (strlen($where) == 0)
			return false;
			
		$sqlU = 'set ';
		foreach ($data as $v=>$v2) {
			if ($v[0] == ':')
				$v[0] = '';
			if (in_array($v, self::$keys))
				$k = "`$v`";
			else
				$k = $v;
			$sqlU .= "$k=:$v, ";
		}
		$sqlU = trim(trim($sqlU, ' '), ',');
		$sql = "update $table $sqlU where $where";
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth);
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 按条件更新数据
	 * @param string $table
	 * @param array $data
	 * @return lastInsertId if succeed, or return false
	 */
	public function replace($table, array $data) {
		$ks = array();
		foreach (array_keys($data) as $k) {
			if (in_array($k, self::$keys))
				$k = "`$k`";
			$ks[] = $k;
		}
		$sqlK = implode(', ', $ks);
		$sqlV = ':'.implode(', :', array_keys($data));
		
		$sql = "replace into $table ($sqlK) values ($sqlV)";
		$sth = $this->prepare($sql);
		self::bindValue($sth, $data);
		$out = $this->execute($sth)?$this->lastInsertId():false;
		$sth->closeCursor();
		return $out;
	}
	
	
	/**
	 * 按条件删除数据
	 * @param string $table
	 * @param string $where
	 * @return boolean
	 */
	public function delete($table, $where) {
		$sql = "delete from $table where $where";
		$sth = $this->prepare($sql);
		$out = $this->execute($sth);
		$sth->closeCursor();
		return $out;
	}
	
	/**
	 * 占位符bind
	 * @param PDOStatement $sth
	 * @param array $binds
	 */
	public static function bindValue(PDOStatement &$sth, array $binds) {
		foreach ($binds as $k=>$v) {
			if (is_int($k)) {
				$sth->bindValue($k+1, $v);
				continue;
			}
			if ($k[0] != ':')
				$k = ':'.$k;
			$sth->bindValue($k, $v);
		}
	}
	
	/**
	 * 执行build好的PDOStatement
	 * @param PDOStatement $sth
	 * @param boolean $setFetchAssoc
	 * @return array
	 */
	public function execute(&$sth, $setFetchAssoc=true) {
        $begin_microtime = Debug::getTime();

		if ($setFetchAssoc)
			$sth->setFetchMode(PDO::FETCH_ASSOC);
		
		if('MySQL server has gone away' == $this->getAttribute(PDO::ATTR_SERVER_INFO))	{
			/* 进行PDO连接 */
			$this->reconnect();
		}
		$out = $sth->execute();
		$this->debug($sth);

        Debug::db($this->_dsn, 'dsn', json_encode($sth), Debug::getTime() - $begin_microtime, $out);
		return $out;
	}

	/**
	 * @return string
	 */
	public function lastErrorCode() {
		return $this->_lastErrorInfo? $this->_lastErrorInfo[0]:'';
	}
	
	/**
	 * @return string
	 */
	public function lastError() {
		return $this->_lastErrorInfo? $this->_lastErrorInfo[2]:'';
	}
	
	/**
	 * @param string $sql
	 * @param array $driver_options 详见pdo::prepare driver_options
	 */
	public function prepare($sql, array $driver_options=array()) {
		$this->debugTime();
		return parent::prepare($sql);
	}
	
	/**
	 * 调试执行时间
	 */
	public function debugTime() {
		$this->_queryTime = microtime(true);
	}
	
	/**
	 * 调试
	 * @param PDOStatement $sth
	 */
	public function debug($sth) {
		if ($this->_debug) {
			Auth::check('debug_db');
			$queryTime = (microtime(true) - $this->_queryTime);
			echo '<li style="border:1px solid #f66;background:#FFFBD9; padding:5px; margin-bottom:-1px;">';
			$sth->debugDumpParams();
			printf("cost:[%.4f s]", $queryTime);
			echo '</li>';
		}
		if (@$_GET['trace']) {
			Auth::check('debug_db');
			debug_print_backtrace();
		}
			
	}
	
	
}