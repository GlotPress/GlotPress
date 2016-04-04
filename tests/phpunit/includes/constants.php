<?php
/**
 * Defines constants needed by unit-tests.
 *
 * @package GlotPress
 * @subpackage Tests
 */

if ( ! defined( 'GP_TESTS_DIR' ) ) {
	define( 'GP_TESTS_DIR', dirname( __DIR__ ) );
}

if ( ! defined( 'GP_DIR_TESTDATA' ) ) {
	define( 'GP_DIR_TESTDATA', GP_TESTS_DIR . '/data' );
}

/**
 * Determines where the WP test suite lives.
 *
 * - Define a WP_TESTS_DIR environment variable, which points to a checkout of
 *   WordPress test suite
 * - Assume that we are inside of a develop.svn.wordpress.org setup, and walk
 *   up the directory tree
 */
if ( false !== getenv( 'WP_TESTS_DIR' ) ) {
	define( 'WP_TESTS_DIR', getenv( 'WP_TESTS_DIR' ) );
	define( 'WP_ROOT_DIR', WP_TESTS_DIR );
} else {
	define( 'WP_ROOT_DIR', dirname( dirname( dirname( dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) ) ) ) );
	define( 'WP_TESTS_DIR', WP_ROOT_DIR . '/tests/phpunit' );
}

// Based on the tests directory, look for a config file.
if ( file_exists( WP_ROOT_DIR . '/wp-tests-config.php' ) ) {
	// Standard develop.svn.wordpress.org setup.
	define( 'WP_TESTS_CONFIG_PATH', WP_ROOT_DIR . '/wp-tests-config.php' );
} elseif ( file_exists( dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' ) ) {
	// Environment variable exists and points to tests/phpunit of develop.svn.wordpress.org setup.
	define( 'WP_TESTS_CONFIG_PATH', dirname( dirname( WP_TESTS_DIR ) ) . '/wp-tests-config.php' );
} else {
	die( "wp-tests-config.php could not be found.\n" );
}

if ( ! defined( 'GP_TESTS_PERMALINK_STRUCTURE' ) ) {
	define( 'GP_TESTS_PERMALINK_STRUCTURE', '/%postname%' );
}

if ( ! defined( 'GP_TESTS_PERMALINK_STRUCTURE_WITH_TRAILING_SLASH' ) ) {
	define( 'GP_TESTS_PERMALINK_STRUCTURE_WITH_TRAILING_SLASH', '/%postname%/' );
}
