<?php

namespace touch\controller;

class RefundController extends BaseController {
	
	/**
	 * 退款申请
	 *
	 * @param Object $request
	 *        	: $data['card_no'] , $data['refund_desc'], $data['order_id'],
	 *        	$data['user_id']
	 * @param Object $response        	
	 */
	public function apply($request, $response) {
		if ($this->isPost ()) {
			try {
				$data = array ();
				$data ['card_no'] = $request->card_no;
				$data ['refund_desc'] = $request->refund_desc;
				$data ['order_id'] = $request->order_id;
				$data ['user_id'] = $this->current_user ['user_id'];
				if ($data ['order_id'] == '')
					throw new \Exception ( "order_id can't be empty" );
				\app\service\RefundSrv::apply ( $data );
				$this->renderJson ( array (
						'ret' => array (
								'status' => 200,
								'data' => '退款申请已提交'
						) 
				) );
			} catch ( \Exception $e ) {
				$this->renderJson ( array (
						'ret' => array (
								'status' => $e->getCode (),
								'data' => $e->getMessage () 
						) 
				) );
			}
		} else {
			$response->title = "退款申请";
			$response->order_id = $request->order_id;
			$this->layoutSmarty ( 'apply' );
		}
	}
	public function detail($request, $response) {
		$response->title = "退款申请";
		try {
			$info = \app\dao\RefundDao::getSlaveInstance ()->find ( array (
					'order_id' => $request->order_id 
			) );
			if (! $info)
				throw new \Exception ( '暂未提交退款申请', '6001' );
			
			$response->refer_param = urlencode($request->refer_param);
			$response->refer = $this->get_refer ();
			
			$response->info = $info;
		} catch ( \Exception $e ) {
			$this->showError ( $e->getMessage () );
		}
		$this->layoutSmarty ( 'detail' );
	}
}