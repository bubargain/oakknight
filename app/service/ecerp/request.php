<?php
/**
 * @author wanjilong@yoka.com
 * @desc
 */

namespace app\service\ecerp;

class request {
    //const URL = 'http://192.168.55.99:8087/data.dpk';
	const URL = 'http://122.112.10.130:8087/data.dpk';
    
    public function init($info) {}

    static public function post($info) {
		
		$cs = curl_init();
        curl_setopt($cs, CURLOPT_URL, self::URL);
        curl_setopt($cs, CURLOPT_HEADER, false);
        curl_setopt($cs, CURLOPT_FAILONERROR, true);
        curl_setopt($cs, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cs, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($cs, CURLOPT_TIMEOUT, 10);
        curl_setopt($cs, CURLOPT_POST, 1);
        curl_setopt($cs, CURLOPT_POSTFIELDS, $info);
        $ret = curl_exec($cs);

        curl_close($cs);
		
		if(!$ret)
            return null;

        return simplexml_load_string($ret);
	}
	
	/**
	 * 
	 * get xml data from API
	 * @param array $info : params send in URL
	 */
	static public function get($info) {
		
		$data = "method=".$info['method'];
		foreach($info as $name=>$val)
		{
			if($name != 'method')
				$data =$data. "&".$name."=".$val;
		}
		
		$cURL = self::URL. "?" . $data ;
		//curl 访问网址
		$cs = curl_init();
        curl_setopt($cs, CURLOPT_URL, $cURL);
        curl_setopt($cs, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($cs, CURLOPT_FAILONERROR, true);
        curl_setopt($cs, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cs, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($cs, CURLOPT_TIMEOUT, 30);
        $ret_code = curl_exec($cs);
        curl_close($cs);
        //echo $ret_code;
        if(!$ret_code)
            return NULL;
        return simplexml_load_string($ret_code);
	}
}

