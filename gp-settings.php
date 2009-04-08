<?php
/**
 * Loads needed libraries and does the preliminary work. You should not have to
 * edit this file. Everything should be configurable from the outside. Starts the
 * routing logic in the end.
 */

require_once( GP_PATH . GP_INC . '/system.php' );

gp_unregister_GLOBALS();

if ( !defined( 'BACKPRESS_PATH' ) ) {
	define( 'BACKPRESS_PATH', GP_PATH . GP_INC . 'backpress/' );
}

if ( !defined( 'POMO_PATH' ) ) {
	define( 'POMO_PATH', GP_PATH . 'pomo/' );
}

if ( !defined( 'GP_LANG_PATH' ) ) {
	define( 'GP_LANG_PATH', GP_PATH . 'languages/' );
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
$gpdb = &new $gpdb_class( array(
	'name' => GPDB_NAME,
	'user' => GPDB_USER,
	'password' => GPDB_PASSWORD,
	'host' => GPDB_HOST,
	'charset' => defined( 'GPDB_CHARSET' ) ? GPDB_CHARSET : false,
	'collate' => defined( 'GPDB_COLLATE' ) ? GPDB_COLLATE : false
) );
unset( $gpdb_class );

$gpdb->tables = array('translations', 'translation_sets', 'originals', 'projects', 'users', 'usermeta', 'meta');

// Set the prefix on the tables
if ( is_wp_error( $gpdb->set_prefix( $gp_table_prefix ) ) ) {
	die( 'Your table prefix may only contain letters, numbers and underscores.' );
}

if ( !function_exists( 'add_filter' ) ) {
	require_once( BACKPRESS_PATH . 'functions.plugin-api.php' );
}

if ( !defined( 'GP_TMPL_PATH' ) )
	define( 'GP_TMPL_PATH', GP_PATH . 'gp-templates/' );

require_once( GP_PATH . GP_INC . 'meta.php');
require_once( GP_PATH . GP_INC . 'misc.php');
require_once( GP_PATH . GP_INC . 'wp-formatting.php');
require_once( GP_PATH . GP_INC . 'url.php');
require_once( GP_PATH . GP_INC . 'strings.php');

require_once GP_PATH . GP_INC . 'project.php';
require_once GP_PATH . GP_INC . 'translation-set.php';

require_once( GP_PATH . GP_INC . 'template.php');
require_once( GP_PATH . GP_INC . 'template-links.php');

/**
 * Define the full path to the object cache functions include
 */
if ( !defined( 'GP_OBJECT_CACHE_FUNCTIONS_INCLUDE' ) ) {
	define( 'GP_OBJECT_CACHE_FUNCTIONS_INCLUDE', BACKPRESS_PATH . 'functions.wp-object-cache.php' );
}

// Load the database class
if ( GP_OBJECT_CACHE_FUNCTIONS_INCLUDE && !function_exists( 'wp_cache_init' ) ) {
	require_once( GP_OBJECT_CACHE_FUNCTIONS_INCLUDE );
}

// Instantiate the $wp_object_cache object using wp_cache_init()
if ( !isset( $wp_object_cache ) && function_exists( 'wp_cache_init' ) ) {
	wp_cache_init();
	if ( function_exists('wp_cache_add_global_groups') ) {
		wp_cache_add_global_groups( array( 'users', 'userlogins', 'usermeta' ) );
	}
}

require_once( GP_PATH . GP_INC . 'class.bp-options.php' );
require_once( BACKPRESS_PATH . 'functions.bp-options.php' );


require_once( BACKPRESS_PATH . 'class.wp-dependencies.php' );
require_once( BACKPRESS_PATH . 'class.wp-styles.php' );
require_once( BACKPRESS_PATH . 'functions.wp-styles.php' );
require_once( BACKPRESS_PATH . 'class.wp-scripts.php' );
require_once( BACKPRESS_PATH . 'functions.wp-scripts.php' );
require_once( GP_PATH . GP_INC . 'assets-loader.php');

require_once( GP_PATH . GP_INC . 'default-filters.php');
require_once( BACKPRESS_PATH . 'functions.kses.php' );

require_once( POMO_PATH . 'mo.php' );
require_once( POMO_PATH . 'po.php' );
require_once( GP_PATH . GP_INC . 'l10n.php' );

if ( !class_exists( 'WP_Users' ) ) {
	require_once( BACKPRESS_PATH . 'class.wp-users.php' );
	$wp_users_object = new WP_Users( $gpdb );
}

require_once( GP_PATH . GP_INC . 'locales.php' );

require_once( GP_PATH . GP_INC . 'routes.php' );
foreach( glob( GP_PATH . GP_INC . 'routes/*.php') as $route ) {
	require_once $route;
}

if ( defined( 'GP_INSTALLING' ) && GP_INSTALLING )
	return;
else
	define( 'GP_INSTALLING', false );

if ( defined( 'GP_NO_ROUTING') && GP_NO_ROUTING )
	return;

if ( ( !defined( 'GP_INSTALLING' ) || !GP_INSTALLING ) && !gp_is_installed() ) {
	$install_uri = preg_replace( '|/[^/]+?$|', '/', $_SERVER['PHP_SELF'] ) . 'install.php';
	header( 'Location: ' . $install_uri );
	die();
}

gp_populate_notices();

$gp_router = new GP_Router( gp_get_routes() );
$gp_router->route();