<?php
/**
 * Loads GlotPress for the purpose of the unit-tests.
 *
 * @package GlotPress
 * @subpackage Tests
 */

require_once __DIR__ . '/constants.php';

$multisite = (int) ( defined( 'WP_TESTS_MULTISITE' ) && WP_TESTS_MULTISITE );
system( WP_PHP_BINARY . ' ' . escapeshellarg( __DIR__ . '/install.php' ) . ' ' . escapeshellarg( WP_TESTS_CONFIG_PATH ) . ' ' . escapeshellarg( WP_TESTS_DIR ) . ' ' . $multisite );

// Bootstrap GlotPress.
require dirname( dirname( dirname( __DIR__ ) ) ) . '/glotpress.php';
