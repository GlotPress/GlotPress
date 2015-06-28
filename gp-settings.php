<?php
/**
 * Loads needed libraries and does the preliminary work. You should not have to
 * edit this file. Everything should be configurable from the outside. Starts the
 * routing logic in the end.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'GlotPress requires a WordPress <code>ABSPATH</code> to operate.' );
}

require_once ABSPATH . '/wp-load.php';

require_once( GP_PATH . GP_INC . '/system.php' );

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

$_GET = gp_urldecode_deep( $_GET );

require_wp_db();
$gpdb = $GLOBALS['wpdb'];

if ( ! isset( $gp_table_prefix ) ) {
	$gp_table_prefix = $table_prefix;
}

$table_names = array('translations', 'translation_sets', 'glossaries', 'glossary_entries', 'originals', 'projects', 'meta', 'permissions', 'api_keys' );
foreach ( $table_names as $table ) {
	$gpdb->$table = $gp_table_prefix . $table;
}

if ( defined( 'CUSTOM_PERMISSIONS_TABLE' ) )
    $gpdb->permissions = CUSTOM_PERMISSIONS_TABLE;

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

wp_start_object_cache();

require_once( GP_PATH . GP_INC . 'assets-loader.php' );

require_once( GP_PATH . GP_INC . 'default-filters.php' );

require_once( ABSPATH . WPINC . '/pomo/mo.php' );
require_once( ABSPATH . WPINC . '/pomo/po.php' );

require_once( GP_LOCALES_PATH . 'locales.php' );

if ( defined('GP_LANG') )
	load_default_textdomain();

// We assume all variables set in this file will be global.
// If the file is inovked inside a function, we will lose them all.
// So, make all local variables, global
gp_set_globals( get_defined_vars() );

require_once( GP_PATH . GP_INC . 'warnings.php' );
require_once( GP_PATH . GP_INC . 'validation.php' );
require_once( GP_PATH . GP_INC . 'advanced-permissions.php' );

require_once GP_PATH . GP_INC . 'thing.php';
require_once GP_PATH . GP_INC . 'things/original.php';
require_once GP_PATH . GP_INC . 'things/permission.php';
require_once GP_PATH . GP_INC . 'things/project.php';
require_once GP_PATH . GP_INC . 'things/translation-set.php';
require_once GP_PATH . GP_INC . 'things/translation.php';
require_once GP_PATH . GP_INC . 'things/user.php';
require_once GP_PATH . GP_INC . 'things/validator-permission.php';
require_once GP_PATH . GP_INC . 'things/glossary.php';
require_once GP_PATH . GP_INC . 'things/glossary-entry.php';


require_once( GP_PATH . GP_INC . 'route.php' );
require_once( GP_PATH . GP_INC . 'router.php' );

require_once GP_PATH . GP_INC . 'routes/_main.php';
require_once GP_PATH . GP_INC . 'routes/index.php';
require_once GP_PATH . GP_INC . 'routes/login.php';
require_once GP_PATH . GP_INC . 'routes/original.php';
require_once GP_PATH . GP_INC . 'routes/profile.php';
require_once GP_PATH . GP_INC . 'routes/project.php';
require_once GP_PATH . GP_INC . 'routes/translation-set.php';
require_once GP_PATH . GP_INC . 'routes/translation.php';
require_once GP_PATH . GP_INC . 'routes/glossary.php';
require_once GP_PATH . GP_INC . 'routes/glossary-entry.php';
require_once GP_PATH . GP_INC . 'routes/locale.php';


GP::$translation_warnings = new GP_Translation_Warnings();
GP::$builtin_translation_warnings = new GP_Builtin_Translation_Warnings();
GP::$builtin_translation_warnings->add_all( GP::$translation_warnings );
GP::$router = new GP_Router();
GP::$formats = array();

require_once GP_PATH . GP_INC . 'format.php';
require_once GP_PATH . GP_INC . 'formats/format_android.php';
require_once GP_PATH . GP_INC . 'formats/format_pomo.php';
require_once GP_PATH . GP_INC . 'formats/format_resx.php';
require_once GP_PATH . GP_INC . 'formats/format_strings.php';

// Let's do it again, there are more variables added since last time we called it
gp_set_globals( get_defined_vars() );

require_once( GP_PATH . GP_INC . 'plugin.php' );

$plugins = scandir( GP_PLUGINS_PATH );
foreach ( $plugins as $plugin ) {
	if ( is_dir( GP_PLUGINS_PATH . '/' . $plugin ) ) {
		if ( is_readable( GP_PLUGINS_PATH . "/$plugin/$plugin.php" ) )
			require_once GP_PLUGINS_PATH . "/$plugin/$plugin.php";
	} else if ( substr( $plugin, -4 ) == '.php' ) {
		require_once GP_PLUGINS_PATH . $plugin;
	}
}
unset( $plugins, $plugin );

GP::$router->set_default_routes();

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
