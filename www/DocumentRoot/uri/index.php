<?php
require_once  '../../../lib/konstrukt/konstrukt.inc.php';
require_once 'testKonstrukt.api.php';

/*
 * test konstrukt framework in sprite project
 * @authr: Daniel Ma
 */


$bootStrap = new k_Bootstrap();
$bootStrap->run('testKonstrukt');  #konstrukt 起始类地址




/*
k()
  // Enable file logging
  ->setLog(dirname(__FILE__) . '/../../log/debug.log')
  // Uncomment the next line to enable in-browser debugging
  //->setDebug()
  // Dispatch request
  ->run('testKonstrukt')
  ->out();
  
 */
  
  



