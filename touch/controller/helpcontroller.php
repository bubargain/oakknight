<?php

namespace touch\controller;

class HelpController extends BaseController {
	// 查看使用条款和隐私政策
	public function agreement($request, $response)  {
		try {
			$result = \app\dao\SettingDao::getSlaveInstance ()->find ( 'agreement' );
			$response->title = '使用条款和隐私政策';
			$response->agreement = nl2br($result ['uvalue']);
			$this->layoutSmarty ( 'agreement' );
		} catch ( \Exception $e ) {
			$this->showError ( $e->getMessage () );
		}
	}
}