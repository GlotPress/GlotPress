<?php
/**
 * Finds and loads the config file and the bootstrapping code
 */

// Die if PHP is not new enough
if ( version_compare( PHP_VERSION, '5.2', '<' ) ) {
	die( sprintf( "Your server is running PHP version %s but GlotPress requires at least 5.2.\n", PHP_VERSION ) );
}

// Fix empty PHP_SELF
$PHP_SELF = $_SERVER['PHP_SELF'];
if ( empty($PHP_SELF) )
    $_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace("/(\?.*)?$/",'',$_SERVER["REQUEST_URI"]);

/**
 * Define GP_PATH as this file's parent directory
 */
define( 'GP_PATH', dirname( __FILE__ ) . '/' );

define( 'GP_INC', 'gp-includes/' );

if ( defined( 'GP_CONFIG_FILE' ) && GP_CONFIG_FILE ) {
	require_once GP_CONFIG_FILE;
	require_once( GP_PATH . 'gp-settings.php' );
} elseif ( file_exists( GP_PATH . 'gp-config.php') ) {
	
	require_once( GP_PATH . 'gp-config.php');
	require_once( GP_PATH . 'gp-settings.php' );
	
} elseif ( file_exists( dirname( GP_PATH ) . '/gp-config.php') ) {
	
	require_once( dirname( GP_PATH ) . '/gp-config.php' );
	require_once( GP_PATH . 'gp-settings.php' );
	
} elseif ( !defined( 'GP_INSTALLING' ) || !GP_INSTALLING ) {

	$install_uri = preg_replace( '|/[^/]+?$|', '/', $_SERVER['PHP_SELF'] ) . 'install.php';
	header( 'Location: ' . $install_uri );
	die();

} else {
	die("gp-config.php doesn't exist! Please create one on top of gp-config-sample.php");
}