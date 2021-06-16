<?php
/**
 * Bootstraps unit-tests.
 *
 * @package GlotPress
 * @subpackage Tests
 */

if ( ! defined( 'GP_TESTS_DIR' ) ) {
	define( 'GP_TESTS_DIR', __DIR__ );
}

if ( ! defined( 'GP_DIR_TESTDATA' ) ) {
	define( 'GP_DIR_TESTDATA', GP_TESTS_DIR . '/data' );
}

if ( ! defined( 'GP_TESTS_PERMALINK_STRUCTURE' ) ) {
	define( 'GP_TESTS_PERMALINK_STRUCTURE', '/%postname%' );
}

if ( ! defined( 'GP_TESTS_PERMALINK_STRUCTURE_WITH_TRAILING_SLASH' ) ) {
	define( 'GP_TESTS_PERMALINK_STRUCTURE_WITH_TRAILING_SLASH', '/%postname%/' );
}

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__, 2 ) . '/glotpress.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Sets a permalink structure so GlotPress doesn't skip loading.
 */
function _set_permalink_structure() {
	return GP_TESTS_PERMALINK_STRUCTURE;
}
tests_add_filter( 'pre_option_permalink_structure', '_set_permalink_structure' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

require_once GP_TESTS_DIR . '/lib/testcase.php';
require_once GP_TESTS_DIR . '/lib/testcase-route.php';
require_once GP_TESTS_DIR . '/lib/testcase-request.php';
