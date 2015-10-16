<?php
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

if ( ! isset( $gp_table_prefix ) ) {
	$gp_table_prefix = $GLOBALS['table_prefix'] . 'gp_';
}

GLOBAL $wpdb;

$table_names = array('translations', 'translation_sets', 'glossaries', 'glossary_entries', 'originals', 'projects', 'meta', 'permissions', 'api_keys' );
foreach ( $table_names as $table ) {
	$wpdb->{'gp_' . $table} = $gp_table_prefix . $table;
}

if ( defined( 'CUSTOM_PERMISSIONS_TABLE' ) )
    $wpdb->gp_permissions = CUSTOM_PERMISSIONS_TABLE;

if ( !defined( 'GP_TMPL_PATH' ) )
	define( 'GP_TMPL_PATH', GP_PATH . 'gp-templates/' );

// Functions that aren't used anymore.
require_once( GP_PATH . GP_INC . 'deprecated.php');

require_once( GP_PATH . GP_INC . 'meta.php' );
require_once( GP_PATH . GP_INC . 'misc.php' );
require_once( GP_PATH . GP_INC . 'url.php' );
require_once( GP_PATH . GP_INC . 'strings.php' );

require_once( GP_PATH . GP_INC . 'template.php' );
require_once( GP_PATH . GP_INC . 'template-links.php' );

require_once( GP_PATH . GP_INC . 'cli.php' );

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

GP::$router->set_default_routes();

if ( !defined( 'GP_ROUTING') ) {
	define( 'GP_ROUTING', false );
}

function gp_activate_plugin() {
	if ( gp_get_option( 'gp_db_version' ) > get_option( 'gp_db_version' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once GP_PATH . GP_INC . 'install-upgrade.php';
		require_once GP_PATH . GP_INC . 'schema.php';
		gp_upgrade_db();
	}

	$admins = GP::$permission->find_one( array( 'action' => 'admin' ) );
	if ( ! $admins ) {
		GP::$permission->create( array( 'user_id' => get_current_user_id(), 'action' => 'admin' ) );
	}
}
register_activation_hook( GP_PLUGIN_FILE, 'gp_activate_plugin' );


/**
 * Add WP rewrite rules to avoid WP thinking that GP pages are 404
 *
 * @since    0.1
 */
function gp_rewrite_rules() {
	$gp_base = trim( gp_url_base_path(), '/' );
	add_rewrite_rule( '^' . $gp_base . '/?(.*)$', 'index.php?gp_route=$matches[1]', 'top' );
}
add_action( 'init', 'gp_rewrite_rules' );


/**
 * Query vars for GP rewrite rules
 *
 * @since    0.1
 */
function gp_query_vars( $query_vars ) {
	$query_vars[] = 'gp_route';
	return $query_vars;
}
add_filter( 'query_vars', 'gp_query_vars' );

function gp_run_route() {
	gp_populate_notices();
	global $wp;
	if ( array_key_exists( 'gp_route', $wp->query_vars ) && GP_ROUTING && ! is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
		GP::$router->route();
	}
}
add_action( 'template_redirect', 'gp_run_route' );
