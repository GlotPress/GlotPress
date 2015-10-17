<?php

require dirname( dirname( dirname( __DIR__ ) ) ) . '/gp-wp/tests/phpunit/includes/bootstrap.php';

// Load GlotPress
require_once dirname( dirname( __DIR__ ) ) . '/gp-load.php';

define( 'GP_DIR_TESTDATA', dirname( __DIR__ ) . '/data' );

$tables = array('gp_translations', 'gp_translation_sets', 'gp_glossaries', 'gp_glossary_entries', 'gp_originals', 'gp_projects', 'gp_meta', 'gp_permissions', 'gp_api_keys' );
foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->$table}" );
}

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once dirname( dirname( __DIR__ ) ) . '/gp-includes/schema.php';
require_once dirname( dirname( __DIR__ ) ) . '/gp-includes/install-upgrade.php';

$errors = gp_install();
if ( $errors ) {
	gp_error_log_dump($errors);
	die( 'ERROR: gp_install() returned errors! Check the error_log for complete SQL error message' );
}

require dirname( __FILE__ ) . '/testcase.php';
require dirname( __FILE__ ) . '/testcase-route.php';
require dirname( __FILE__ ) . '/testcase-request.php';
