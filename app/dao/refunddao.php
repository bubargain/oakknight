<?php

namespace app\dao;

use app\dao\YmallDao;
class RefundDao extends YmallDao {
	const REFUND_ACCEPT = 1; //退款待受理
	const REFUND_ACCEPTING = 2; //退款处理中
	const REFUND_ACCEPTED = 3; //退款同意
	const REFUND_CLOSED = 20; //退款拒绝
	const REFUND_CANCEL = 30; //退款取消
	protected static $_master;
	protected static $_slave;
	public function getTableName() {
		return 'ym_order_refund';
	}
	public function getPKey() {
		return 'refund_id';
	}
	public function getList($params, $limit = '0,9', $sort = 'utime DESC ') {
		$sql = "SELECT * FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params ) . " ORDER BY " . $sort . " LIMIT " . $limit;
		$list = $this->_pdo->getRows ( $sql );

        $options = self::getStatusArr();
		foreach ( ( array ) $list as $key => $row ) {
			$list[$key]['refund_status_str'] = $options[$row['refund_status']];
		}
		return $list;
	}

	public function getListCnt($params) {
		$sql = "SELECT COUNT(*) FROM " . self::getTableName () . " WHERE " . self::makeSql ( $params );
		return $this->_pdo->getOne( $sql );
	}

	private function makeSql($params) {
		if (is_array ( $params ) && count ( $params ) > 0) {
			return implode ( ' AND ', $params );
		} else {
			return '1';
		}
	}

	public function editStatus($status, $where) {
		if (! $where)
			throw new \Exception ( 'edit goods status must be set where', 3001 );
		
		$sql = "update " . self::getTableName () . " set `status`=$status where " . $where;
		return $this->_pdo->exec ( $sql );
	}

    static public function getStatusTxt($status) {
        $t = array(
                0=>'申请退款',
            	self::REFUND_ACCEPT =>'退款处理中', //退款待受理
            	self::REFUND_ACCEPTING =>'退款处理中',//退款处理中
            	self::REFUND_ACCEPTED =>'退款成功', //退款同意
            	self::REFUND_CLOSED =>'申请退款失败',//退款拒绝
            	self::REFUND_CANCEL =>'', //退款取消
        );
        return isset($t[$status]) ? $t[$status] : '';
    }

    static public function getStatusArr() {
        return array(
            self::REFUND_ACCEPT =>'退款处理中', //退款待受理
            self::REFUND_ACCEPTING =>'退款处理中',//退款处理中
            self::REFUND_ACCEPTED =>'退款成功', //退款同意
            self::REFUND_CLOSED =>'申请退款失败',//退款拒绝
            self::REFUND_CANCEL =>'申请退款取消', //退款取消
        );
    }


}