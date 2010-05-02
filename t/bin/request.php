<?php

$index = dirname( __FILE__ ) . '/../../index.php'; 

require $argv[1];
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
$_SERVER['PATH_TRANSLATED'] = realpath( $index );
$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];
require $index;

