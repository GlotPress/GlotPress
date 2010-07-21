<?php
/**
 * Loads needed libraries and does the preliminary work. You should not have to
 * edit this file. Everything should be configurable from the outside. Starts the
 * routing logic in the end.
 */

if ( defined( 'GP_DEBUG' ) && GP_DEBUG ) {
	error_reporting( E_ALL );
} else {
    if ( defined( 'E_RECOVERABLE_ERROR' ) )
        error_reporting( E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
    else
        error_reporting( E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING );
}

require_once( GP_PATH . GP_INC . '/system.php' );

gp_unregister_GLOBALS();

if ( !defined( 'BACKPRESS_PATH' ) ) {
	define( 'BACKPRESS_PATH', GP_PATH . GP_INC . 'backpress/' );
}

if ( !defined( 'GP_POMO_PATH' ) ) {
	define( 'GP_POMO_PATH', GP_PATH . 'pomo/' );
}

if ( !defined( 'GP_LOCALES_PATH' ) ) {
	define( 'GP_LOCALES_PATH', GP_PATH . 'locales/' );
}


if ( !defined( 'GP_LANG_PATH' ) ) {
	define( 'GP_LANG_PATH', GP_PATH . 'languages/' );
}

if ( !defined( 'GP_PLUGINS_PATH' ) ) {
	define( 'GP_PLUGINS_PATH', GP_PATH . 'plugins/' );
}

if ( !defined( 'DATE_MYSQL' ) ) {
	define( 'DATE_MYSQL', 'Y-m-d H:i:s' );
}

if ( !defined( 'GP_TESTS_PATH' ) ) {
	define( 'GP_TESTS_PATH', GP_PATH . 't/' );
}


require_once( GP_PATH . GP_INC . 'gp.php');

/*
 * In most cases the default internal encoding is latin1, which is of no use,
 * since we want to use the mb_ functions for UTF-8 strings
 */
if (function_exists('mb_internal_encoding')) {
	mb_internal_encoding('UTF-8');
}

require_once( BACKPRESS_PATH . 'class.bp-log.php' );
$gp_log = new BP_Log();
if ( defined( 'GP_LOG_LEVEL' ) ) {
	$gp_log->set_level( GP_LOG_LEVEL );
}
if ( defined( 'GP_LOG_TYPE' ) ) {
	$gp_log->set_type( GP_LOG_TYPE );
}
if ( defined( 'GP_LOG_FILENAME' ) ) {
	$gp_log->set_filename( GP_LOG_FILENAME );
}
$gp_log->notice('Logging started');

// Load core BackPress functions
require_once( BACKPRESS_PATH . 'functions.core.php' );
require_once( BACKPRESS_PATH . 'functions.compat.php' );
require_once( BACKPRESS_PATH . 'functions.formatting.php' );

// alleviate the magic_quotes_gpc effects
if ( get_magic_quotes_gpc() ) {
	$_GET    = stripslashes_deep( $_GET );
	$_POST   = stripslashes_deep( $_POST );
	$_COOKIE = stripslashes_deep( $_COOKIE );
}

$_GET = gp_urldecode_deep( $_GET );

require_once( BACKPRESS_PATH . 'class.wp-error.php' );

if ( !defined( 'GP_DATABASE_CLASS_INCLUDE' ) ) {
	define( 'GP_DATABASE_CLASS_INCLUDE', BACKPRESS_PATH . 'class.bpdb-multi.php' );
}

if ( GP_DATABASE_CLASS_INCLUDE ) {
	require_once( GP_DATABASE_CLASS_INCLUDE );
}

if ( !defined( 'GP_DATABASE_CLASS' ) ) {
	define( 'GP_DATABASE_CLASS', 'BPDB_Multi' );
}

if ( in_array( GP_DATABASE_CLASS, array( 'BPDB', 'BPDB_Multi' ) ) ) {
	/**
	 * Define BackPress Database errors if not already done - no localisation at this stage
	 */
	if ( !defined( 'BPDB__CONNECT_ERROR_MESSAGE' ) ) {
		define( 'BPDB__CONNECT_ERROR_MESSAGE', 'ERROR: Could not establish a database connection' );
	}
	if ( !defined( 'BPDB__CONNECT_ERROR_MESSAGE' ) ) {
		define( 'BPDB__SELECT_ERROR_MESSAGE', 'ERROR: Can\'t select database.' );
	}
	if ( !defined( 'BPDB__ERROR_STRING' ) ) {
		define( 'BPDB__ERROR_STRING', 'ERROR: GlotPress database error - "%s" for query "%s" via caller "%s"' );
	}
	if ( !defined( 'BPDB__ERROR_HTML' ) ) {
		define( 'BPDB__ERROR_HTML', '<div id="error"><p class="bpdberror"><strong>Database error:</strong> [%s]<br /><code>%s</code><br />Caller: %s</p></div>' );
	}
	if ( !defined( 'BPDB__DB_VERSION_ERROR' ) ) {
		define( 'BPDB__DB_VERSION_ERROR', 'ERROR: GlotPress requires MySQL 4.0.0 or higher' );
	}
	if ( !defined( 'BPDB__PHP_EXTENSION_MISSING' ) ) {
		define( 'BPDB__PHP_EXTENSION_MISSING', 'ERROR: GlotPress requires The MySQL PHP extension' );
	}
}

// Die if there is no database table prefix
if ( !$gp_table_prefix ) {
	die( 'You must specify a table prefix in your <code>gp-config.php</code> file.' );
}

// Setup the global database connection
$gpdb_class = GP_DATABASE_CLASS;
$gpdb = new $gpdb_class( array(
	'name' => GPDB_NAME,
	'user' => GPDB_USER,
	'password' => GPDB_PASSWORD,
	'host' => GPDB_HOST,
	'charset' => defined( 'GPDB_CHARSET' ) ? GPDB_CHARSET : false,
	'collate' => defined( 'GPDB_COLLATE' ) ? GPDB_COLLATE : false
) );
unset( $gpdb_class );

$gpdb->table_names = array('translations', 'translation_sets', 'originals', 'projects', 'users', 'usermeta', 'meta', 'permissions', 'api_keys', );
foreach( $gpdb->table_names as $table ) {
	$gpdb->tables[$table] = false;
}
unset( $table );

// Set the prefix on the tables
if ( is_wp_error( $gpdb->set_prefix( $gp_table_prefix ) ) ) {
	die( 'Your table prefix may only contain letters, numbers and underscores.' );
}

if ( defined( 'CUSTOM_USER_TABLE' ) )
    $gpdb->users = CUSTOM_USER_TABLE;

if ( defined( 'CUSTOM_USER_META_TABLE' ) )
    $gpdb->usermeta = CUSTOM_USER_META_TABLE;

if ( defined( 'CUSTOM_PERMISSIONS_TABLE' ) )
    $gpdb->permissions = CUSTOM_PERMISSIONS_TABLE;

if ( !function_exists( 'add_filter' ) ) {
	require_once( BACKPRESS_PATH . 'functions.plugin-api.php' );
}

if ( !defined( 'GP_TMPL_PATH' ) )
	define( 'GP_TMPL_PATH', GP_PATH . 'gp-templates/' );

require_once( GP_PATH . GP_INC . 'lambda.php');

require_once( GP_PATH . GP_INC . 'meta.php' );
require_once( GP_PATH . GP_INC . 'misc.php' );
require_once( GP_PATH . GP_INC . 'url.php' );
require_once( GP_PATH . GP_INC . 'strings.php' );

require_once( GP_PATH . GP_INC . 'template.php' );
require_once( GP_PATH . GP_INC . 'template-links.php' );

require_once( GP_PATH . GP_INC . 'cli.php' );

/**
 * Define the full path to the object cache functions include
 */
if ( !defined( 'GP_OBJECT_CACHE_FUNCTIONS_INCLUDE' ) ) {
	define( 'GP_OBJECT_CACHE_FUNCTIONS_INCLUDE', BACKPRESS_PATH . 'loader.wp-object-cache.php' );
}

// Load the database class
if ( GP_OBJECT_CACHE_FUNCTIONS_INCLUDE && !function_exists( 'wp_cache_init' ) ) {
	require_once( GP_OBJECT_CACHE_FUNCTIONS_INCLUDE );
}

// Instantiate the $wp_object_cache object using wp_cache_init()
if ( !isset( $wp_object_cache ) && function_exists( 'wp_cache_init' ) ) {
	wp_cache_init();
	if ( function_exists( 'wp_cache_add_global_groups' ) ) {
		wp_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta', 'usermail', 'usernicename' ) );
	}
}

require_once( GP_PATH . GP_INC . 'class.bp-options.php' );
require_once( BACKPRESS_PATH . 'functions.bp-options.php' );

require_once( BACKPRESS_PATH . 'class.wp-http.php' );

require_once( BACKPRESS_PATH . 'class.wp-dependencies.php' );
require_once( BACKPRESS_PATH . 'class.wp-styles.php' );
require_once( BACKPRESS_PATH . 'functions.wp-styles.php' );
require_once( BACKPRESS_PATH . 'class.wp-scripts.php' );
require_once( BACKPRESS_PATH . 'functions.wp-scripts.php' );
require_once( GP_PATH . GP_INC . 'assets-loader.php' );

require_once( GP_PATH . GP_INC . 'default-filters.php' );
require_once( BACKPRESS_PATH . 'functions.kses.php' );

require_once( GP_POMO_PATH . 'mo.php' );
require_once( GP_POMO_PATH . 'po.php' );
require_once( GP_PATH . GP_INC . 'l10n.php' );

require_once( GP_LOCALES_PATH . 'locales.php' );

// Users and authentication
if ( !class_exists( 'WP_Users' ) ) {
	require_once( BACKPRESS_PATH . 'class.wp-users.php' );
	$wp_users_object = new WP_Users( $gpdb );
}


if ( !defined( 'WP_AUTH_COOKIE_VERSION' ) ) {
	define( 'WP_AUTH_COOKIE_VERSION', 2 );
}

// WP_Pass
if ( !class_exists( 'WP_Pass' ) ) {
	require_once( BACKPRESS_PATH . 'class.wp-pass.php' );
}

// We assume all variables set in this file will be global.
// If the file is inovked inside a function, we will lose them all.
// So, make all local variables, global
gp_set_globals( get_defined_vars() );

/**
 * It is possible to define this in wp-config.php and it will be used as the domain for all cookies.
 * Set it carefully for sharing cookies amonst subdomains
 * 
 * @link http://curl.haxx.se/rfc/cookie_spec.html
 */
if ( !defined('GP_COOKIE_DOMAIN') )
	define('GP_COOKIE_DOMAIN', false);

if ( !class_exists( 'WP_Auth' ) ) {
	require_once( BACKPRESS_PATH . 'class.wp-auth.php' );
	$cookies = array();
	$cookies['auth'][] = array(
		'domain' => GP_COOKIE_DOMAIN,
		'path' => gp_url_path(),
		'name' => gp_const_get( 'GP_AUTH_COOKIE', 'glotpress_auth' ),
	);
	$cookies['secure_auth'][] = array(
		'domain' => GP_COOKIE_DOMAIN,
		'path' => gp_url_path(),
		'name' => gp_const_get( 'GP_SECURE_AUTH_COOKIE', 'glotpress_sec_auth' ),
		'secure' => 'true',
	);

	$cookies['logged_in'][] = array(
		'domain' => GP_COOKIE_DOMAIN,
		'path' => gp_url_path(),
		'name' => gp_const_get( 'GP_LOGGED_IN_COOKIE', 'glotpress_logged_in' ),
	);
	$wp_auth_object = new WP_Auth( $gpdb, $wp_users_object, $cookies );
	unset( $cookies );
}

require_once( GP_PATH . GP_INC . 'warnings.php' );
require_once( GP_PATH . GP_INC . 'validation.php' );
require_once( GP_PATH . GP_INC . 'google.php' );
require_once( GP_PATH . GP_INC . 'advanced-permissions.php' );

require_once GP_PATH . GP_INC . 'thing.php';
foreach( glob( GP_PATH . GP_INC . 'things/*.php' ) as $thing_file ) {
	require_once $thing_file;
}

require_once( GP_PATH . GP_INC . 'route.php' );
require_once( GP_PATH . GP_INC . 'router.php' );
foreach( glob( GP_PATH . GP_INC . 'routes/*.php' ) as $route_file ) {
	require_once $route_file;
}

GP::$translation_warnings = new GP_Translation_Warnings();
GP::$builtin_translation_warnings = new GP_Builtin_Translation_Warnings();
GP::$builtin_translation_warnings->add_all( GP::$translation_warnings );
GP::$router = new GP_Router();
GP::$formats = array();

foreach( glob( GP_PATH . GP_INC . 'formats/format_*.php' ) as $format_file ) {
	require_once $format_file;
}
unset( $format_file );

// Let's do it again, there are more variables added since last time we called it
gp_set_globals( get_defined_vars() );

require_once( GP_PATH . GP_INC . 'plugin.php' );

$plugins = glob( GP_PLUGINS_PATH . '*.php' );
if ( $plugins ) {
	foreach( $plugins as $plugin ) {
		require_once $plugin;
	}
}

$plugin_dirs = glob( GP_PLUGINS_PATH . '*', GLOB_ONLYDIR );
if ( $plugin_dirs ) {
	foreach( $plugin_dirs as $plugin_dir ) {
		$plugin = "$plugin_dir/" . basename( $plugin_dir ) . '.php';
		if ( is_readable( $plugin ) ) require_once $plugin;
	}
}
unset( $plugins, $plugin, $plugin_dirs, $plugin_dir );

do_action( 'plugins_loaded' );

if ( defined( 'GP_INSTALLING' ) && GP_INSTALLING )
	return;
else
	define( 'GP_INSTALLING', false );

if ( !defined( 'GP_ROUTING') ) {
	define( 'GP_ROUTING', false );
}

if ( ( !defined( 'GP_INSTALLING' ) || !GP_INSTALLING ) && !gp_is_installed() ) {
	if ( GP_ROUTING ) {
		$install_uri = preg_replace( '|/[^/]+?$|', '/', $_SERVER['PHP_SELF'] ) . 'install.php';
		header( 'Location: ' . $install_uri );
	}
	return;
}

gp_populate_notices();

function gp_shutdown_action_hook() {
	do_action( 'shutdown' );
}
register_shutdown_function( 'gp_shutdown_action_hook' );

do_action( 'init' );

if ( GP_ROUTING ) {
	GP::$router->route();
}