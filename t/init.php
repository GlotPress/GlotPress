<?php
require_once 'PHPUnit/Autoload.php';

define( 'GP_CONFIG_FILE', dirname( __FILE__ ) . '/unittests-config.php' );

if ( !isset( $_SERVER['SERVER_PROTOCOL'] ) ) {
	$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
}

require_once dirname( __FILE__ ) . '/../gp-load.php';

require dirname( __FILE__ ) . '/lib/testcase.php';
require dirname( __FILE__ ) . '/lib/testcase-route.php';
require dirname( __FILE__ ) . '/lib/testcase-request.php';
