<?php

namespace touch\controller;

use \app\service\transfer\kuaidi100srv;

class TransferController extends BaseController {
	public function info($request, $response) {
		if ($request->order_id) {
			$response->order = \app\service\OrderSrv::info ( $request->order_id );
			try {
				$data = new kuaidi100srv ();
				$ret = json_decode ( $data->innerQuery ( $request->shipping_code, $request->shipping_name ), true );
				if ($ret ['status'] == 200) {
					$response->list = $ret ['data'];
				} else {
					$this->showError ( '正在为您获取物流信息,请稍后再试~' );
				}
				$this->layoutSmarty ( 'index' );
			} catch ( \Exception $e ) {
				$this->showError ( '正在为您获取物流信息,请稍后再试~' );
			}
		} else {
			$this->showError ( '正在为您获取物流信息,请稍后再试~' );
		}
	}
}