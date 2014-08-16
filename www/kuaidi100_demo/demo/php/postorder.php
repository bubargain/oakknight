<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta charset="utf-8" />
<title></title>

</head>
<body>
<?
$post_data = array();
$post_data["schema"] = 'json' ;

//callbackurl请参考callback.php实现，key经常会变，请与快递100联系获取最新key
$post_data["param"] = '{"company":"yuantong", "number":"12345678","from":"广东深圳", "to":"北京朝阳", "key":"testkuaidi1031", "parameters":{"callbackurl":"http://www.yourdmain.com/kuaidi"}}';

$url='http://www.kuaidi100.com/poll';

$o=""; 
foreach ($post_data as $k=>$v)
{
    $o.= "$k=".urlencode($v)."&";		//默认UTF-8编码格式
}

$post_data=substr($o,0,-1);

$ch = curl_init();
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$result = curl_exec($ch);		//返回提交结果，格式与指定的格式一致（result=true代表成功）

?>
</body>
</html>
 