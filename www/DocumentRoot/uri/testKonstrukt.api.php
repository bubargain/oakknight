<?php
require_once  '../../../lib/konstrukt/konstrukt.inc.php';
require_once 'uploadImg.api.php';

class testKonstrukt extends k_Component
{
    function renderHtml()
	{
		echo "hello there! \n this is get html operation";
	}
	
	function renderJson()
	{
		echo "It should be get Json here!";
	}
	
	function postHtml()
	{
		echo "this is post operation";
	}
	
	
	function map($name)
	{
		echo $name;
		if($name == 'upload')
		{
			return 'upload_img_api';
		}
	}
	
}