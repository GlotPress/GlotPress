<?php
/**
 * Installs GlotPress for the purpose of the unit-tests.
 *
 * @package GlotPress
 * @subpackage Tests
 */

error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );

$config_file_path = $argv[1];
$tests_dir_path = $argv[2];
$multisite = ! empty( $argv[3] );

require_once $config_file_path;
require_once $tests_dir_path . '/includes/functions.php';
require_once $tests_dir_path . '/includes/mock-mailer.php';

/**
 * Loads GlotPress.
 */
function _load_glotpress() {
	require dirname( dirname( dirname( __DIR__ ) ) ) . '/glotpress.php';
}
tests_add_filter( 'muplugins_loaded', '_load_glotpress' );

/**
 * Sets a permalink structure so GlotPress doesn't skip loading.
 *
 * @return string
 */
function _set_permalink_structure() {
	return '/%postname%';
}
tests_add_filter( 'pre_option_permalink_structure', '_set_permalink_structure' );

$_SERVER['HTTP_HOST'] = WP_TESTS_DOMAIN;
// @codingStandardsIgnoreStart
$PHP_SELF = $GLOBALS['PHP_SELF'] = $_SERVER['PHP_SELF'] = '/index.php';
// @codingStandardsIgnoreEnd

require_once ABSPATH . '/wp-settings.php';

echo "Installing GlotPress...\n";

/*
 * default_storage_engine and storage_engine are the same option, but storage_engine
 * was deprecated in MySQL (and MariaDB) 5.5.3, and removed in 5.7.
 */
if ( version_compare( $wpdb->db_version(), '5.5.3', '>=' ) ) {
	$wpdb->query( 'SET default_storage_engine = InnoDB' );
} else {
	$wpdb->query( 'SET storage_engine = InnoDB' );
}
$wpdb->select( DB_NAME, $wpdb->dbh );

// Drop GlotPress tables.
foreach ( $wpdb->get_col( "SHOW TABLES LIKE '" . $wpdb->prefix . "gp%'" ) as $gp_table ) {
	$wpdb->query( "DROP TABLE {$gp_table}" ); // WPCS: unprepared SQL ok.
}

/**
 * Installs GlotPress.
 */
function _install_glotpress() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/gp-includes/schema.php';
	require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/gp-includes/install-upgrade.php';
	gp_upgrade_db();
}
_install_glotpress();
