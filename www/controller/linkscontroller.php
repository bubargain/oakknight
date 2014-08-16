<?php
namespace www\controller;

class LinksController extends AppBaseController{
	public function __construt($request,$response)
	{
		
	}
	
	/**
	 * 
	 * query friendly links
	 * @param object $request: limit 返回数量
	 * @param object $response: NULL
	 */
	public function info($request,$response)
	{
		$limit = $request->limit? $request->limit : 0;
		$links = new \app\service\LinksSrv();
		$this->result( $links->searchAll($limit));
	}
}