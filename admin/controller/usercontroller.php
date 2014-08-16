<?php

namespace admin\controller;

use sprite\mvc\controller;
use \stdClass;
use app\common\util\subpages;

class UserController extends BaseController {
	// 会员列表
	public function index($request, $response) {
		$response->title = '会员列表';
		// 处理搜索信息
		$extUrl = '';
		if ($request->user_name) {
			$params ['user_name'] = "a.`user_name` LIKE '%" . trim ( $request->user_name ) . "%'";
			$response->user_name = trim ( $request->user_name );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&user_name=' ) === false) {
				$extUrl .= "&user_name=" . trim ( $request->user_name );
			}
		}
		if ($request->nick_name) {
			$params ['nick_name'] = "`nick_name` LIKE '%" . trim ( $request->nick_name ) . "%'";
			$response->nick_name = trim ( $request->nick_name );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&nick_name=' ) === false) {
				$extUrl .= "&nick_name=" . trim ( $request->nick_name );
			}
		}
		if ($request->user_id) {
			$params ['user_id'] = "a.`user_id` = " . intval ( $request->user_id );
			$response->user_id = intval ( $request->user_id );
			if (strpos ( $_SERVER ['REQUEST_URI'], '&user_id=' ) === false) {
				$extUrl .= "&user_id=" . intval ( $request->user_id );
			}
		}
		if ($request->start_time || $request->end_time) {
			$_default = strtotime ( date ( "Y-m-d" ) );
			$start_time = $request->start_time ? strtotime ( $request->start_time . ' 00:00:00' ) : $_default;
			$end_time = $request->end_time ? strtotime ( $request->end_time . ' 00:00:00' ) + 24 * 3600 : $_default + 24 * 3600;
			$params ['utime'] = "`utime` >= " . $start_time . " AND `utime` < " . $end_time;
			if (strpos ( $_SERVER ['REQUEST_URI'], '&start_time=' ) === false) {
				$extUrl .= "&start_time=" . trim ( $request->start_time ) . "&end_time=" . trim ( $request->end_time );
			}
		}
		$total = \app\dao\UserInfoDao::getSlaveInstance ()->getListCnt ( $params );
		// 当前页数
		$curPageNum = $request->page ? intval ( $request->page ) : 1;
		// url
		$url = preg_replace ( '/([?|&]page=\d+)/', '', $_SERVER ['REQUEST_URI'] ) . $extUrl;
		// 分页对象
		$page = new SubPages ( $url, 10, $total, $curPageNum );
		$limit = $page->GetLimit ();
		$list = array ();
		if ($total) {
			$list = \app\dao\UserInfoDao::getSlaveInstance ()->getList ( $params, $limit );
		}
		$response->list = $list;
		$response->page = $page->GetPageHtml ();
		$this->layoutSmarty ( 'index' );
	}
	// 查看会员信息
	public function detail($request, $response) {
		$response->title = '查看会员信息';
		$user_id = intval ( $request->user_id );
		// 获取记录
		$info = \app\dao\UserInfoDao::getSlaveInstance ()->getInfo ( $user_id );
		$response->cdn_ymall = CDN_YMALL;
		$response->info = $info;
		$orderSrc = new \app\service\OrderSrv ();
		$orderDao = \app\dao\OrderDao::getSlaveInstance ();
		
		$order_str = '';
		$order_str .= $orderSrc->getStatus ( $orderSrc::PAYED_ORDER ) . ':' . $orderDao->getOrderCnt ( $user_id, $orderSrc::PAYED_ORDER );
		$order_str .= $orderSrc->getStatus ( $orderSrc::SHIPPING_ORDER ) . ':' . $orderDao->getOrderCnt ( $user_id, $orderSrc::SHIPPING_ORDER );
		$order_str .= $orderSrc->getStatus ( $orderSrc::RECEIVED_ORDER ) . ':' . $orderDao->getOrderCnt ( $user_id, $orderSrc::RECEIVED_ORDER );
		$order_str .= $orderSrc->getStatus ( $orderSrc::FINISHED_ORDER ) . ':' . $orderDao->getOrderCnt ( $user_id, $orderSrc::FINISHED_ORDER );
		
		$response->order_str = $order_str;
		
		$this->layoutSmarty ( 'detail' );
	}
	// 修改密码
	public function editpassword($request, $response) {
		$user_id = intval ( $request->user_id );
		$new_password = trim ( $request->new_password );
		$renew_password = trim ( $request->renew_password );
		if ($new_password != $renew_password) {
			$this->showError ( '两次密码不一致' );
		}
		// 获取表单变量
		$params = array (
				'user_id' => $user_id,
				'password' => md5 ( $new_password ) 
		);
		
		$result = \app\dao\UserDao::getMasterInstance ()->edit ( $user_id, $params );
		if (! $result) {
			$this->showError ( '修改密码失败' );
		}
		// 记录操作日志
		\app\dao\UserLogDao::getMasterInstance ()->add ( (array (
				'user_id' => $this->current_user ['user_id'],
				'uuid' => '',
				'type' => 'user',
				'action' => 'editpassword',
				'item_id' => 0,
				'info' => serialize ( array (
						'get' => $_GET,
						'post' => $_POST 
				) ),
				'ctime' => time () 
		)) );
		header ( "Location:index.php?_c=user&_a=index" );
	}
	// 删除会员
	public function delete($request, $response) {
		$user_id = intval ( $request->user_id );
		$result1 = \app\dao\UserInfoDao::getMasterInstance ()->delete ( $user_id );
		$result2 = \app\dao\UserDao::getMasterInstance ()->delete ( $user_id );
		if ($result1 && $result2) {
			// 记录操作日志
			\app\dao\UserLogDao::getMasterInstance ()->add ( (array (
					'user_id' => $this->current_user ['user_id'],
					'uuid' => '',
					'type' => 'user',
					'action' => 'delete',
					'item_id' => 0,
					'info' => serialize ( array (
							'get' => $_GET,
							'post' => $_POST 
					) ),
					'ctime' => time () 
			)) );
			header ( "Location: index.php?_c=user&_a=index" );
		} else {
			$this->showError ( '删除会员失败' );
		}
	}
	public function leadingin($request, $response) {
		$response->title = '导入会员';
		$this->layoutSmarty ( 'leadingin' );
	}
	public function download($request, $response) {
		if ($request->type == 'saveCsv') {
			$result = self::saveData ( self::checkCsvFile () );
			if ($result === true) {
				header ( "Location:index.php?_c=user&_a=index" );
			} else {
				$this->showError ( $result );
			}
		} else {
			$arr = array (
					'用户id',
					'用户名',
					'密码',
					'添加时间',
					'昵称',
					'设备号' 
			);
			self::csvTemplate ( $arr );
		}
	}
	public function csvTemplate($arr) {
		if (! (is_array ( $arr ) && count ( $arr ) > 0)) {
			$this->showError ( '下载模板出错' );
		}
		// 输出Excel文件头，可把user.csv换成你要的文件名
		header ( 'Content-Type: application/vnd.ms-excel' );
		header ( 'Content-Disposition: attachment;filename="user.csv"' );
		header ( 'Cache-Control: max-age=0' );
		// 打开PHP文件句柄，php://output 表示直接输出到浏览器
		$fp = fopen ( 'php://output', 'a' );
		// 输出Excel列名信息
		foreach ( $arr as $i => $v ) {
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			$arr [$i] = iconv ( 'utf-8', 'gb2312', $v );
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv ( $fp, $arr );
	}
	public function checkCsvFile() {
		$csvType = array (
				'text/x-comma-separated-values',
				'text/comma-separated-values',
				'application/octet-stream',
				'application/vnd.ms-excel',
				'text/x-sv',
				'text/csv',
				'application/csv',
				'application/excel',
				'application/vnd.msexcel' 
		);
		if ($_FILES ['csv'] ['name']) {
			if (in_array ( $_FILES ['csv'] ['type'], $csvType )) {
				return $_FILES ['csv'] ['tmp_name'];
			} else {
				$this->showError ( '文件格式有误' );
			}
		} else {
			$this->showError ( '提交信息不完整或有误' );
		}
	}
	public function saveData($fdata) {
		$file = fopen ( $fdata, 'r' );
		$k = 0;
		while ( $data = fgetcsv ( $file ) ) {
			$k ++;
			if ($k > 1) {
				// user表
				$params = array (
						'user_id' => iconv ( 'gb2312', 'utf-8', trim ( $data [0] ) ),
						'user_name' => iconv ( 'gb2312', 'utf-8', trim ( $data [1] ) ),
						'password' => md5 ( iconv ( 'gb2312', 'utf-8', trim ( $data [2] ) ) ),
						'ctime' => iconv ( 'gb2312', 'utf-8', trim ( $data [3] ) ) 
				);
				try {
					\app\dao\UserDao::getMasterInstance ()->add ( $params );
				} catch ( \Exception $e ) {
					return $e->getMessage ();
				}
				// user_info表
				$params = array (
						'user_id' => iconv ( 'gb2312', 'utf-8', trim ( $data [0] ) ),
						'user_name' => iconv ( 'gb2312', 'utf-8', trim ( $data [1] ) ),
						'utime' => iconv ( 'gb2312', 'utf-8', trim ( $data [3] ) ),
						'nick_name' => iconv ( 'gb2312', 'utf-8', trim ( $data [4] ) ),
						'uuid' => iconv ( 'gb2312', 'utf-8', trim ( $data [5] ) ) 
				);
				try {
					\app\dao\UserInfoDao::getMasterInstance ()->add ( $params );
				} catch ( \Exception $e ) {
					return $e->getMessage ();
				}
			}
		}
		fclose ( $file );
		try {
			// 记录操作日志
			\app\dao\UserLogDao::getMasterInstance ()->add ( (array (
					'user_id' => $this->current_user ['user_id'],
					'uuid' => '',
					'type' => 'user',
					'action' => 'download',
					'item_id' => 0,
					'info' => serialize ( array (
							'get' => $_GET,
							'post' => $_POST 
					) ),
					'ctime' => time () 
			)) );
		} catch ( \Exception $e ) {
			return $e->getMessage ();
		}
		return true;
	}
	public function push($request, $response) {
		$response->title = '发送PUSH';
		if ($request->ptype == 'savePush') {
			if ($request->type && $request->message) {
				// 获取表单变量
				$params = array (
						'status' => '0',
						'type' => intval ( $request->type ),
						'user_id' => $request->cookie ( 'user_id' ),
						'message' => trim ( $request->message ),
						'extra' => '',
						'ctime' => time () 
				);
				// 不是上面数组中的元素，就是extra的值
				$extra = array ();
				$pushParamList = parse_ini_file ( './push.config.ini', true );
				foreach ( $pushParamList as $val ) {
					if (! array_key_exists ( $val ['id'], $params )) {
						$params [$val ['id']] = $request->$val ['id'];
					}
				}
				var_dump ( $params );
				exit ();
				// 保存
				$result = \app\dao\PushDao::getMasterInstance ()->add ( $params );
				if (! $result) {
					$this->showError ( '保存信息失败' );
				}
				header ( "Location: index.php?_c=user&_a=index" );
			} else {
				$this->showError ( '提交信息不完整或有误' );
			}
		} else {
			$response->form = self::pushParamList ();
			$this->layoutSmarty ( 'push' );
		}
	}
	public function pushParamList($pid = '') {
		$str = '';
		$pushParamList = parse_ini_file ( './push.config.ini', true );
		foreach ( $pushParamList as $key => $val ) {
			if ($val ['pid'] == $pid) {
				switch ($val ['type']) {
					case 'select' :
						$str .= self::getSelect ( $key, $val );
						break;
					case 'input' :
						$str .= self::getInput ( $key, $val ['id'] );
						break;
					case 'textarea' :
						$str .= self::getTextarea ( $key, $val ['id'] );
						break;
				}
			}
		}
		return $str;
	}
	public function getFormExtParam($request, $response) {
		echo self::pushParamList ( $request->pid );
	}
	private function getSelect($labelName, $arr) {
		$str = "<div class='control-group'>";
		$str .= "<label class='control-label' for='inputEmail'>" . $labelName . "</label>";
		$selList = explode ( ',', $arr ['value'] );
		$str .= "<div class='controls'><select id='" . $arr ['id'] . "'  name='" . $arr ['id'] . "'>";
		foreach ( $selList as $v ) {
			$opList = explode ( '-', $v );
			$str .= "<option value='" . $opList [0] . "'>" . $opList [1] . "</option>";
		}
		$str .= "</select></div>";
		$str .= "</div>";
		$str .= "<div id='extraParam'></div>";
		return $str;
	}
	private function getInput($labelName, $id) {
		$str = "<div class='control-group'>";
		$str .= "<label class='control-label' for='inputEmail'>" . $labelName . "</label>";
		$str .= "<div class='controls'><input type='text' id='" . $id . "'  name='" . $id . "' /></div>";
		$str .= "</div>";
		return $str;
	}
	private function getTextarea($labelName, $id) {
		$str = "<div class='control-group'>";
		$str .= "<label class='control-label' for='inputEmail'>" . $labelName . "</label>";
		$str .= "<div class='controls'><textarea id='" . $id . "'  name='" . $id . "' rows=5  placeholder='建议不超过35个字'></textarea></div>";
		$str .= "</div>";
		return $str;
	}
	public function verifycode($request, $response) {
		if ($request->flag == 'search') {
			if ($request->phone) {
				$params ['phone'] = " phone = '" . $request->phone . "'";
			}
			if ($request->type) {
				$params ['type'] = " `type` = '" . $request->type . "'";
			}
			$response->result = \app\dao\VerifyCodeDao::getSlaveInstance ()->getValidByParams ( $params );
		}
		$this->layoutSmarty ( 'verifycode' );
	}
}