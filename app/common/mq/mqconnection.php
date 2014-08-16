<?php
    namespace app\common\mq;

use \Exception;
use AMQPConnection;
use AMQPChannel;
use AMQPExchange;

/**
 * AMQP operation 
 * @author jujianhua
 *
 */
class MQConnection{
      private $conn = null;
      private $channel = null;
      private $exchange = null;
	  private $serverconfig = null;
	  
	  private static $_instance = null;
	  
	  /**
	   *   singleton
	   */
	  private function __construct(){
	  	    $this->_connect();
	  	    
	  }
	  
	  /**
	  * 获取mongodb连接操作对象,singleton
	  * @return RabbitMQConnection
	  */
	  public static function getInstance(){
	  	if ( !self::$_instance  ){
	  			
	  		self::$_instance  = new MQConnection();
	  
	  	}
	  	return self::$_instance ;
	  }
	  
	  /**
	   * 发送指定的消息到队列中
	   * @param AsyncMessage $message
	   */
	  public function sendMessage($message){
	  	  if (!$this->conn || !$this->exchange || !$this->channel){
	  	  	    if ( !$this->_connect() ){
	  	  	    	  return false;
	  	  	    }
	  	  }
	  	  
	  	  $exchange_name = $message->GetExchange($message->messageType);
	  	  $this->exchange->setName($exchange_name);
	  	 
	  	  $routingkey =  $message->GetRoutingKey($message->messageType);
	  	  $json = json_encode( $message );
	  	  
	  	  $ret = $this->exchange->publish($json,$routingkey);
	  	  if ( !$ret ){
	  	  	    //TODO: save faild message to mongodb
	  	  	    
	  	  		return false;
	  	  }
	  	 
	  	  return $ret;
	  }
	  
	  private function _open($config){
	  	    //clean previos resource
	  	    if ( $this->conn  ){
	  	    	 @$conn->disconnect();
	  	    }
	  	    
	  	    if ( $this->channel){
	  	    	  unset($this->channel);
	  	    }
	  	    
	  	    if ( $this->exchange){
	  	    	  unset($this->exchange);
	  	    }

	  		$conn_args = array('host' => $config['host'],
                               'port' => $config['port'],
                               'login' => $config['user_name'],
                               'password' => $config['password'],
                               'vhost' => $config['virtual_host'] );

	  		$this->conn = new AMQPConnection($conn_args);
	  		$ret = @$this->conn->connect();
	  		if ( $ret ){
	  			//create a channel and exchange
	  			 $this->channel = new AMQPChannel($this->conn);
	  		}
	  		
	  		if ( $ret ){
	  			 $this->exchange = new AMQPExchange($this->channel);
	  		}
	  		return $ret;
	  }
	  
	  /**
	   * 使用轮询方式连接数据库
	   * @throws Exception
	   */
	  private  function _connect(){
		  	
		  	$server_count = count( $GLOBALS['fan_rabbit_mq_config']);
		  	if ( $server_count == 0 ){
		  		   throw new Exception('rabbit mq connfig not fund,plese check config file ');
		  	}
		  	
		  	//轮询 server
		  	$selected_server_index =  time() % $server_count;
		  	$this->serverconfig = $GLOBALS['fan_rabbit_mq_config'][ $selected_server_index ];
		  	 
		  	 if (  $this->_open( $this->serverconfig ) ){
		  	 	  	return true;
		  	 }else{
		  	  	  //try connect other server
		  	 	   foreach ($GLOBALS['fan_rabbit_mq_config'] as $index => $config){
                         if ( $selected_server_index != $index){
                               if (  $this->_open( $config ) ){
                                    $this->serverconfig = $config;
                                    return true;
                              }
                         }
		  	 	   }
		  	 }
		  	 
		  	 return false;
	  }
	  
	 
	  
}