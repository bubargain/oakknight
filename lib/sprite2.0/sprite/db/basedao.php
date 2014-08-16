<?php
namespace sprite\db;

use sprite\db\PDOManager;
use sprite\exception\DataAssert;
use sprite\exception\BizException;

abstract class BaseDao {

	const SLAVE = true;
	const MASTER = false;
	protected $_pdo = null;
	
	/**
	 * @param string  $pdoconn_or_conntype 传入参数为一个链接或指明链接类型，自动生成链接
	 * @throws Exception
	 */
	public function __construct($pdoconn_or_conntype=self::SLAVE){
		if ($pdoconn_or_conntype == self::SLAVE) {
			$this->_pdo = PDOManager::getConnect($this->getSdbCfgName());
		} else if ($pdoconn_or_conntype == self::MASTER) {
			$this->_pdo = PDOManager::getConnect($this->getMdbCfgName());
		} else if (is_object($pdoconn_or_conntype)) {
			$this->_pdo = $pdoconn_or_conntype;
		} else {
			throw new \Exception('参数不是有效的链接对象，也不是有效的链接类型');
		}
	}
	
	
	/**
	 * 取得子类的一个链接从库的实例
	 * 调用此方法的子类上必需有静态属性::$_slave
	 */
	public static function getSlaveInstance() {
		$daoName = get_called_class();		
		if (empty(static::$_slave))
			static::$_slave = new $daoName(self::SLAVE);
		
		return static::$_slave;
	}
	
	/**
	 * @return 执行一条sql删除或修改的sql，返回记录受影响条数
	 * 如果没有记录被修改，返回0
	 * 其它的修改和删除，最好直接调用PDOext类的delete及update方法
	 * @param string $sql  要执行的sql误句
	 */
	public function exec($sql){
		return $this->_pdo->exec($sql);
	}
	
	/**
	 * 取得子类的一个链接从库的实例
	 * 调用此方法的子类上必需有静态属性::$_master
	 */
	public static function getMasterInstance() {
		$daoName = get_called_class();		
		if (empty(static::$_master))
		static::$_master = new $daoName(self::MASTER);
		
		return static::$_master;
	}
	
	/**
	 * 启动当前连接的事务
	 */
	public function beginTransaction(){
		return $this->_pdo->beginTransaction();
	}
	
	/**
	 * 提交当前已经启动的事务
	 */
	public function commit(){
		return $this->_pdo->commit();
	}
	
	/**
	 * 
	 * 回滚事务
	 */
	public function rollBack(){
		return $this->_pdo->rollBack();
	}
	

	//取得数据库主库配置，由子类实现
	protected abstract function getMdbCfgName();
	
	//取得数据库从库配置，由子类实现
	protected abstract function getSdbCfgName();
	
	//dao对应表名，由子类实现
	protected abstract function getTableName();
	
	//dao对应表主键名，由子类实现
	protected abstract function getPKey();

	
	/**
	 * 根据主键查找一条记录
	 * @param string $pk_value 主键的值
	 */
	public function find($pk_value) {
		$sql = "select * from {$this->getTableName()} where {$this->getPkeyWhere($pk_value)} limit 1";
		return $this->_pdo->getRow($sql);
	}
	
	
	/*
	 * 寻找所有匹配记录
	 * $where : 查询条件
	 * $start:  起始页
	 * $numberPerTime: 每次取得个数
	 */
	public function findALL($where,$limit=20)
	{
		if($where)
			$sql = "select * from {$this->getTableName()} where {$this->getPkeyWhere($where)}  order by add_time desc ";
		else 
			$sql = "select * from {$this->getTableName()} order by add_time desc ";
		if($limit != 0)
		{
			$sql = $sql."limit $limit";
		}
		return $this->_pdo->getRows($sql);
	}
	
	
	/**
	 * @param unknown_type $fieldName 字段名
	 * @param unknown_type $value 字段值
	 */
	public function findByField($fieldName, $value) {
		$sql = "select * from {$this->getTableName()} where $fieldName=?";
		return $this->_pdo->getRows($sql, array($value));
	}
	
	/**
	 * 新增一条记录
	 * @param array $vars 行记录数组
	 */
	public function add(array $vars) {
		DataAssert::assertNotEmpty($vars, new BizException('插入内容为空'));
		return $this->_pdo->insert($this->getTableName(), $vars);
	}

	/**
	 * 修改一条记录
	 * @param unknown_type $pk_value 主键
	 * @param array $vars 修改行记录数组
	 */
	public function edit($pk_value, array $vars) {
		DataAssert::assertNotEmpty($pk_value, new BizException('主键为空'));
		return $this->_pdo->update($this->getTableName(), $vars, $this->getPkeyWhere($pk_value));
		
	}
	
	/**
	 * 根据条件更新
	 * @param array $vars
	 * @param string $where
	 * e.g. 
	 * 	editByWhere(array('name'=>'hi'), 'id=1') == sql: update xxx set name='hi' where id=1;
	 */
	public function editByWhere(array $vars, $where) {
		DataAssert::assertNotEmpty($where, new BizException('where条件为空'));
		return $this->_pdo->update($this->getTableName(), $vars, $where);
	}
	
	
	/**
	 * 根据数据表里的 unique index 替换
	 * @param array $vars 修改行记录数组
	 */
	public function replace(array $vars) {
		return $this->_pdo->replace($this->getTableName(), $vars);
	
	}
	
	/**
	 * 按主键删除一条记录
	 * @param unknown_type $pk_value 主键值
	 */
	public function delete($pk_value) {
		DataAssert::assertNotEmpty($pk_value, new BizException('主键为空'));
		return $this->_pdo->delete($this->getTableName(), $this->getPkeyWhere($pk_value));
	}
	
	/**
	 * 按条件删除一条记录
	 * @param unknown_type $where 条件
	 */
	public function deleteByWhere($where) {
		DataAssert::assertNotEmpty($where, new BizException('where条件为空'));
		return $this->_pdo->delete($this->getTableName(), $where);
	}
	
	protected function getPkeyWhere($pk_value) {
		/*if ( is_array($pk_value) != is_array($this->pk() )  ){
			 throw new Exception("当前dao的pk()定义的键与查询key使用的key不相同，请检查");
		}*/
		
		if (is_array($pk_value)) {
			$tmp = array();
			foreach ($pk_value as $key=>$field) {
				$tmp[] = "$key='$field'";
			}
			return implode(' and ', $tmp);
		}
		else{
		    $pkname = $this->getPKey();
			return " $pkname='$pk_value' ";
		}
	}
	
	public function getLastError() {
		return $this->_pdo->lastError();
	}
	
}