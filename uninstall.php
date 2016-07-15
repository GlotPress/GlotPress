<?php
/**
 * Uninstall routine for GlotPress.
 *
 * @package GlotPress
 * @since 2.2.0
 */

if ( defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	if ( '1' === get_option( 'gp_delete_on_uninstall' ) ) {
		gp_uninstall();
	}
}

/**
 * Function to remove all of the database tables and settings in the WordPress options table.
 *
 * @since 2.2.0
 */
function gp_uninstall() {
	global $wpdb;

	// Since we may have been called after GP has been disabled, make sure we have all the defines we need.
	if ( ! defined( 'GP_PATH' ) ) {
		define( 'GP_PATH', __DIR__ . '/' );
	}

	if ( ! defined( 'GP_INC' ) ) {
		define( 'GP_INC', 'gp-includes/' );
	}

	// Include the schema so we can get the list of tables.
	if ( ! function_exists( 'gp_schema_get' ) ) {
		include( GP_PATH . GP_INC . 'schema.php' );
	}

	// Get the schema and loop through each table so we can delete it.
	$schema = gp_schema_get();

	foreach ( $schema as $table => $sql ) {
		$table_name = $wpdb->prefix . 'gp_' . $table;

		// We can't use $wpdb->prepare here as the table name is not one of the support data types.
		$wpdb->query( "DROP TABLE {$table_name};" ); // WPCS: unprepared SQL ok.
	}

	// Delete the WordPress options we use.
	delete_option( 'gp_db_version' );
	delete_option( 'gp_rewrite_rule' );
	delete_option( 'gp_delete_on_uninstall' );
}
