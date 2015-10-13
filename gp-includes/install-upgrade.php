<?php

function gp_upgrade_db() {
	global $wpdb;

<<<<<<< HEAD
	dbDelta( implode( "\n", gp_schema_get() ) );
=======
	$alterations = BP_SQL_Schema_Parser::delta( $gpdb, gp_schema_get() );
	$errors = $alterations['errors'];

	if ( $errors ) {
		return $errors;
	}

	gp_upgrade_data( gp_get_option_from_db( 'gp_db_version' ) );

	gp_update_db_version();
}

function gp_upgrade() {
	return gp_upgrade_db();
}

/**
 * Sets the rewrite rules
 *
 * @return bool Returns true on success and false on failure
 */
function gp_set_htaccess() {
	// The server doesn't support mod rewrite
	if ( ! apache_mod_loaded( 'mod_rewrite', true ) ) {
		return false;
	}

	if ( file_exists( '.htaccess' ) && ! is_writeable( '.htaccess' ) ) {
		return false;
	}

	// check if the .htaccess is in place or try to write it
	$htaccess_file = @fopen( '.htaccess', 'c+' );

	//error opening htaccess, inform user!
	if ( false === $htaccess_file ) {
		return false;
	}

	//'# BEGIN GlotPress' not found, write the access rules
	if ( false === strpos( stream_get_contents( $htaccess_file ), '# BEGIN GlotPress' ) ) {
		fwrite( $htaccess_file, gp_mod_rewrite_rules() );
	}
>>>>>>> Fix check when mod_rewrite isn't loaded to not set the .htaccess file. This due to debugging in the beginning.

	gp_upgrade_data( get_option( 'gp_db_version' ) );

	update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

function gp_upgrade_data( $db_version ) {
	global $wpdb;
	if ( $db_version < 190 ) {
		$wpdb->query("UPDATE $wpdb->translations SET status = REPLACE(REPLACE(status, '-', ''), '+', '');");
	}
}
