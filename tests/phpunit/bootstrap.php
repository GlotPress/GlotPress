<?php
/**
 * Bootstraps unit-tests.
 *
 * @package GlotPress
 * @subpackage Tests
 */

require __DIR__ . '/includes/constants.php';

require_once WP_TESTS_DIR . '/includes/functions.php';

/**
 * Load GlotPress.
 */
function _manually_load_plugin() {
	require GP_TESTS_DIR . '/includes/loader.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

global $wp_tests_options;
$wp_tests_options['permalink_structure'] = GP_TESTS_PERMALINK_STRUCTURE;

require WP_TESTS_DIR . '/includes/bootstrap.php';

require_once GP_TESTS_DIR . '/lib/testcase.php';
require_once GP_TESTS_DIR . '/lib/testcase-route.php';
require_once GP_TESTS_DIR . '/lib/testcase-request.php';
