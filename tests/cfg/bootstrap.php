<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../glotpress.php';

	define( 'GP_DIR_TESTDATA', dirname( dirname( __FILE__ ) ) . '/data' );

	global $gpdb;
	$tables = array('translations', 'translation_sets', 'glossaries', 'glossary_entries', 'originals', 'projects', 'meta', 'permissions', 'api_keys' );
	foreach ( $tables as $table ) {
		$gpdb->query( "DROP TABLE IF EXISTS {$gpdb->$table}" );
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/gp-includes/schema.php';
	require_once dirname( dirname( dirname( __FILE__ ) ) ) . '/gp-includes/install-upgrade.php';
	gp_upgrade_db();
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

require_once dirname( dirname( __FILE__ ) ) . '/lib/testcase.php';
require_once dirname( dirname( __FILE__ ) ) . '/lib/testcase-route.php';
require_once dirname( dirname( __FILE__ ) ) . '/lib/testcase-request.php';
