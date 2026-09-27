<?php
/**
 * Uninstall routine for GlotPress.
 *
 * @package GlotPress
 * @since 3.0.0
 */

if ( defined( 'ABSPATH' ) && defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	$options = get_option( 'gp_options' );
	if ( isset( $options['delete_data'] ) && ( true === $options['delete_data'] ) ) {
		gp_delete_data();
	}
	// As the new option to delete the GlotPress options has the default value set to true,
	// if the option doesn't exist in the options table, the GlotPress options will be deleted.
	if ( ! isset( $options['delete_data'] ) || ( isset( $options['delete_data'] ) && ( true === $options['delete_options'] ) ) ) {
		gp_delete_options();
	}
}

/**
 * Removes all GlotPress database tables.
 *
 * @since 3.0.0
 */
function gp_delete_data() {
	global $wpdb, $gp_table_prefix;

	// Since we may have been called after GP has been disabled, make sure we have all the defines we need.
	if ( ! defined( 'GP_PATH' ) ) {
		define( 'GP_PATH', __DIR__ . '/' );
	}
	if ( ! defined( 'GP_INC' ) ) {
		define( 'GP_INC', 'gp-includes/' );
	}
	if ( ! isset( $gp_table_prefix ) ) {
		$gp_table_prefix = $GLOBALS['table_prefix'] . 'gp_';
	}
	// Include the schema, so we can get the list of tables.
	if ( ! function_exists( 'gp_schema_get' ) ) {
		include GP_PATH . GP_INC . 'schema.php';
	}
	// Get the schema and loop through each table, so we can delete it.
	$schema = gp_schema_get();
	foreach ( $schema as $table => $sql ) {
		$table_name = $gp_table_prefix . $table;
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // WPCS: unprepared SQL ok.
	}
}

/**
 * Removes all GlotPress settings in the WordPress options table.
 *
 * @since 3.0.0
 */
function gp_delete_options() {
	delete_option( 'gp_db_version' );
	delete_option( 'gp_rewrite_rule' );
	delete_option( 'gp_options' );
}
