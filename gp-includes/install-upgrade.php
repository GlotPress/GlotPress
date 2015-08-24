<?php

function gp_upgrade_db() {
	global $gpdb;

	dbDelta( implode( "\n", gp_schema_get() ) );

	gp_upgrade_data( get_option( 'gp_db_version' ) );

	update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

function gp_upgrade_data( $db_version ) {
	global $gpdb;
	if ( $db_version < 190 ) {
		$gpdb->query("UPDATE $gpdb->translations SET status = REPLACE(REPLACE(status, '-', ''), '+', '');");
	}
}
