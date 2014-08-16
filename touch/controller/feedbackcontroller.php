<?php

namespace touch\controller;

class feedbackcontroller extends BaseController {
	public function index($request, $response) {
		$response->title = '意见反馈';
		$response->contact = $this->current_user ['user_name'];
		if ($this->isPost ()) {
			if (! $request->content) {
				$this->showError ( '请填写内容' );
			}
			if (! $request->contact) {
				$this->showError ( '请填写联系方式' );
			}
			try {
				$data ['user_id'] = $this->current_user ['user_id'];
				$data ['content'] = $request->content; // 反馈内容
				$data ['contact'] = $request->contact; // 联系方式
				$data ['type'] = 'feed'; // 反馈类型
				$data ['ctime'] = time ();
				\app\dao\FeedBackDao::getMasterInstance ()->add ( $data );
				header ( "Location:index.php?_c=index" );
			} catch ( \Exception $e ) {
				$this->showError ( '意见反馈操作内部错误' );
			}
		}
		$this->layoutSmarty ( 'index' );
	}
}