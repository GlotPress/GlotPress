<?php
/**
 * Used to set up variables, constants and to include the GlotPress
 * procedural and class library.
 *
 * @package GlotPress
 * @since 1.0.0
 */

require_once GP_PATH . GP_INC . '/system.php';

if ( ! defined( 'GP_LOCALES_PATH' ) ) {
	define( 'GP_LOCALES_PATH', GP_PATH . 'locales/' );
}

if ( ! defined( 'DATE_MYSQL' ) ) {
	define( 'DATE_MYSQL', 'Y-m-d H:i:s' );
}

if ( ! defined( 'GP_TESTS_PATH' ) ) {
	define( 'GP_TESTS_PATH', GP_PATH . 't/' );
}

require_once GP_PATH . GP_INC . 'gp.php';

global $wpdb, $gp_table_prefix;

if ( ! isset( $gp_table_prefix ) ) {
	$gp_table_prefix = $GLOBALS['table_prefix'] . 'gp_';
}

$table_names = array( 'translations', 'translation_sets', 'glossaries', 'glossary_entries', 'originals', 'projects', 'meta', 'permissions' );
foreach ( $table_names as $table ) {
	$wpdb->{'gp_' . $table} = $gp_table_prefix . $table;
}

if ( defined( 'CUSTOM_PERMISSIONS_TABLE' ) ) {
	$wpdb->gp_permissions = CUSTOM_PERMISSIONS_TABLE;
}

if ( ! defined( 'GP_TMPL_PATH' ) ) {
	define( 'GP_TMPL_PATH', GP_PATH . 'gp-templates/' );
}

require_once GP_PATH . GP_INC . 'local.php';
require_once GP_PATH . GP_INC . 'meta.php';
require_once GP_PATH . GP_INC . 'misc.php';
require_once GP_PATH . GP_INC . 'url.php';
require_once GP_PATH . GP_INC . 'strings.php';

require_once GP_PATH . GP_INC . 'template.php';
require_once GP_PATH . GP_INC . 'template-links.php';

require_once GP_PATH . GP_INC . 'cli.php';

require_once GP_PATH . GP_INC . 'assets-loader.php';

require_once GP_PATH . GP_INC . 'rewrite.php';

require_once GP_PATH . GP_INC . 'default-filters.php';

require_once ABSPATH . WPINC . '/pomo/mo.php';
require_once ABSPATH . WPINC . '/pomo/po.php';

if ( ! class_exists( 'GP_Locale' ) || ! class_exists( 'GP_Locales' ) ) {
	require_once GP_LOCALES_PATH . 'locales.php';
}

/*
 * We assume all variables set in this file will be global.
 * If the file is invoked inside a function, we will lose them all.
 * So, make all local variables, global.
 */
gp_set_globals( get_defined_vars() );

require_once GP_PATH . GP_INC . 'warnings.php';
require_once GP_PATH . GP_INC . 'errors.php';
require_once GP_PATH . GP_INC . 'validation.php';
require_once GP_PATH . GP_INC . 'advanced-permissions.php';

require_once GP_PATH . GP_INC . 'thing.php';
require_once GP_PATH . GP_INC . 'things/original.php';
require_once GP_PATH . GP_INC . 'things/permission.php';
require_once GP_PATH . GP_INC . 'things/project.php';
require_once GP_PATH . GP_INC . 'things/translation-set.php';
require_once GP_PATH . GP_INC . 'things/translation.php';
require_once GP_PATH . GP_INC . 'things/validator-permission.php';
require_once GP_PATH . GP_INC . 'things/administrator-permission.php';
require_once GP_PATH . GP_INC . 'things/glossary.php';
require_once GP_PATH . GP_INC . 'things/glossary-entry.php';

require_once GP_PATH . GP_INC . 'route.php';
require_once GP_PATH . GP_INC . 'router.php';

require_once GP_PATH . GP_INC . 'routes/_main.php';
require_once GP_PATH . GP_INC . 'routes/index.php';
require_once GP_PATH . GP_INC . 'routes/original.php';
require_once GP_PATH . GP_INC . 'routes/profile.php';
require_once GP_PATH . GP_INC . 'routes/settings.php';
require_once GP_PATH . GP_INC . 'routes/project.php';
require_once GP_PATH . GP_INC . 'routes/translation-set.php';
require_once GP_PATH . GP_INC . 'routes/translation.php';
require_once GP_PATH . GP_INC . 'routes/glossary.php';
require_once GP_PATH . GP_INC . 'routes/glossary-entry.php';
require_once GP_PATH . GP_INC . 'routes/local.php';
require_once GP_PATH . GP_INC . 'routes/locale.php';


GP::$translation_warnings         = new GP_Translation_Warnings();
GP::$builtin_translation_warnings = new GP_Builtin_Translation_Warnings();
GP::$builtin_translation_warnings->add_all( GP::$translation_warnings );
GP::$translation_errors         = new GP_Translation_Errors();
GP::$builtin_translation_errors = new GP_Builtin_Translation_Errors();
GP::$builtin_translation_errors->add_all( GP::$translation_errors );
GP::$router  = new GP_Router();
GP::$local   = new GP_Local();
GP::$formats = array();

require_once GP_PATH . GP_INC . 'format.php';
require_once GP_PATH . GP_INC . 'formats/format-android.php';
require_once GP_PATH . GP_INC . 'formats/format-pomo.php';
require_once GP_PATH . GP_INC . 'formats/format-resx.php';
require_once GP_PATH . GP_INC . 'formats/format-strings.php';
require_once GP_PATH . GP_INC . 'formats/format-properties.php';
require_once GP_PATH . GP_INC . 'formats/format-json.php';
require_once GP_PATH . GP_INC . 'formats/format-jed1x.php';
require_once GP_PATH . GP_INC . 'formats/format-ngx.php';
require_once GP_PATH . GP_INC . 'formats/format-php.php';

if ( GP_Local::is_active() ) {
	require_once GP_PATH . GP_INC . 'rest-api.php';
	GP::$rest = new GP_Rest_API();
	require_once GP_PATH . GP_INC . 'inline-translation.php';
	GP_Inline_Translation::init();
}

// Let's do it again, there are more variables added since last time we called it.
gp_set_globals( get_defined_vars() );

GP::$router->set_default_routes();

if ( ! defined( 'GP_ROUTING' ) ) {
	define( 'GP_ROUTING', false );
}

// Let's check to see if we need to run the upgrade routine but only run it on the admin side.
if ( is_admin() && GP_DB_VERSION > get_option( 'gp_db_version' ) ) {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once GP_PATH . GP_INC . 'install-upgrade.php';
	require_once GP_PATH . GP_INC . 'schema.php';
	gp_upgrade_db();
}
