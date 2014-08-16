<?php
/**
 * 资源配置服务
 **/

$_SERVER['memcache'] = array(
    'default' => array(
        'server'=>array(
            array('host'=>'127.0.0.1','port'=>11211),

            //array('host'=>$_SERVER['MEMCACHE_SERVER1_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER1_PORT']),
           /* array('host'=>$_SERVER['MEMCACHE_SERVER2_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER2_PORT']),
            array('host'=>$_SERVER['MEMCACHE_SERVER3_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER3_PORT']),
            array('host'=>$_SERVER['MEMCACHE_SERVER4_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER4_PORT']),
            array('host'=>$_SERVER['MEMCACHE_SERVER5_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER5_PORT']),
            array('host'=>$_SERVER['MEMCACHE_SERVER6_HOST'],'port'=>$_SERVER['MEMCACHE_SERVER6_PORT']),*/
        )
    )
);
