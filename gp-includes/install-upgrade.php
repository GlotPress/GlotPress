<?php
/**
 * Install/Upgrade routines for the database.
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Runs the install/upgrade of the database.
 *
 * @since 1.0.0
 */
function gp_upgrade_db() {
	dbDelta( implode( "\n", gp_schema_get() ) );

	$gp_db_version = get_option( 'gp_db_version' );
	if ( $gp_db_version ) {
		gp_upgrade_data( $gp_db_version );
	}

	update_option( 'gp_db_version', GP_DB_VERSION );
}

/**
 * Updates existing data in the database during an upgrade.
 *
 * @since 1.0.0
 *
 * @param int $db_version The current version of the database before the upgrade.
 */
function gp_upgrade_data( $db_version ) {
	global $wpdb;

	if ( $db_version < 950 ) {
		$wpdb->query( "UPDATE {$wpdb->gp_projects} SET `path` = SUBSTRING(`path`, 1, CHAR_LENGTH(`path`) - 1) WHERE `path` LIKE '%/';" );
	}
}
