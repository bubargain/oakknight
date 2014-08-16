<?php

namespace touch\controller;

use  app\dao\RegionDao;

class regioncontroller extends BaseController {
	
	function index($request, $response) {
		$pid = $request->get('pid', 0);
		
		$list = RegionDao::getSlaveInstance()->getList($pid);
        if($list) {
            foreach($list as $k=>$r) {
                $list[$k]['region_name'] = htmlspecialchars($r['region_name']);
            }
        }
        $this->renderJson(array('status'=>200, 'retval'=>$list) );
    }
}