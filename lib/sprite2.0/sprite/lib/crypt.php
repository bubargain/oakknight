<?php
namespace sprite\lib;

/**
 * @author liweiwei
 * 简单加密
 *
 */
class Crypt {
	protected $key = "哦，卖疙瘩";    //公钥
	private function keyED($txt, $encrypt_key) {
		$encrypt_key = md5($encrypt_key);
		$ctr=0;
		$tmp = "";
		for ($i=0; $i<strlen($txt); $i++)
		{
			if ($ctr==strlen($encrypt_key)) {
				$ctr=0;
			}
			$tmp.= substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1);
			$ctr++;
		}
		return $tmp;
	}

	public function encrypt($txt, $key="") {
		if(empty($key)){
			$key=$this->key;
		}
		srand((double)microtime()*1000000);
		$encrypt_key = md5(rand(0, 32000));
		$ctr=0;
		$tmp = "";
		for ($i=0;$i<strlen($txt);$i++)
		{
			if ($ctr==strlen($encrypt_key)){
				$ctr=0;
			}
			$tmp.= substr($encrypt_key, $ctr, 1) .
				(substr($txt, $i, 1) ^ substr($encrypt_key, $ctr, 1));
			$ctr++;
		}
		$out = $this->keyED($tmp, $key);
		return base64_encode($out);
	}
	
	public function decrypt($txt, $key="") {
		if(empty($key)){
			$key=$this->key;
		}

		$txt = base64_decode($txt);
		$txt = $this->keyED($txt, $key);
		$tmp = "";
		for ($i=0; $i<strlen($txt); $i++) {
			$md5 = substr($txt, $i, 1);
			$i++;
			$tmp.= (substr($txt, $i, 1) ^ $md5);
		}
		return $tmp;
	}
	
	public function setKey($key) {
		if(empty($key)) {
			return null;
		}
		$this->key=$key;
	}
	
	public function getKey() {
		return $this->key;
	}
}

/* 
$string = "141";
$crypt= new Crypt();
$crypt->setKey("塔吉克斯坦");
$enc_text = $crypt->encrypt($string, $crypt->getKey());
$dec_text = $crypt->decrypt($enc_text, $crypt->getKey());
echo "加密前 : $string \n";
echo "加密后 : $enc_text \n";
echo "解密后 : $dec_text \n";
 */
?>
