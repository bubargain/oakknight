<?php

/*
$a = "new string";
$c = $b = $a;
xdebug_debug_zval( 'a' );
//$b .= '---';
xdebug_debug_zval( 'b' );
unset( $b, $c );
xdebug_debug_zval( 'a' );

echo "<br />";

$a = array( 'meaning' => 'life', 'number' => 42 );

$b = $c = $a;
xdebug_debug_zval( 'a' );

*/

$sku = 'ffsd fdffddfdfdd';
//$sku = 'ffsdfdffddfdfdd';
//preg_match_all('/\w{4,20}/', $sku, $_g);
preg_match_all('/\w{5,}/', $sku, $_g);

var_dump($_g);
