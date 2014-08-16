<?php

namespace app\common\util;

class Str {
	function html2text($msg) {
		$msg = str_replace ( '&nbsp;&nbsp;', '　', $msg );
		
		$msg = str_replace ( '&amp;', '&', $msg );
		
		$msg = str_replace ( '&nbsp;', ' ', $msg );
		
		$msg = str_replace ( '"', '&quot;', $msg );
		
		$msg = str_replace ( "'", '&#39;', $msg );
		
		$msg = str_replace ( "<", "&lt;", $msg );
		
		$msg = str_replace ( ">", "&gt;", $msg );
		
		$msg = str_replace ( "\t", "&nbsp; &nbsp; ", $msg );
		
		$msg = str_replace ( "\r", "", $msg );
		
		$msg = str_replace ( "  ", "&nbsp; ", $msg );
		
		return $msg;
	}
	function text2html($msg) {
		$msg = str_replace ( "\t", "&nbsp; &nbsp; ", $msg );
		
		$msg = str_replace ( "\r", "<br/>", $msg );
		
		$msg = str_replace ( "  ", "&nbsp; ", $msg );
		
		return $msg;
	}
}