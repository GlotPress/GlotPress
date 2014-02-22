<?php

$config_file_path = dirname( dirname( __FILE__ ) ) . '/unittests-config.php';

if ( ! is_readable( $config_file_path ) ) {
	die( "ERROR: unittests-config.php is missing! Please use unittests-config-sample.php to create a config file.\n" );
}

define( 'GP_CONFIG_FILE', $config_file_path );

if ( ! isset( $_SERVER['SERVER_PROTOCOL'] ) ) {
	$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
}

// Load GlotPress
require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/gp-load.php';

require dirname( __FILE__ ) . '/testcase.php';
require dirname( __FILE__ ) . '/testcase-route.php';
require dirname( __FILE__ ) . '/testcase-request.php';
