<?php

function gp_update_db_version() {
	gp_update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

function gp_upgrade_db() {
	global $gpdb;

	dbDelta( implode( "\n", gp_schema_get() ) );

	/*
	Haha you really think dbDelta gives you errors?
	if ( $errors ) {
		return $errors;
	}
	*/

	gp_upgrade_data( gp_get_option_from_db( 'gp_db_version' ) );

	gp_update_db_version();
}

function gp_upgrade() {
	return gp_upgrade_db();
}

function gp_upgrade_data( $db_version ) {
	global $gpdb;
	if ( $db_version < 190 ) {
		$gpdb->query("UPDATE $gpdb->translations SET status = REPLACE(REPLACE(status, '-', ''), '+', '');");
	}
}

function gp_install() {
	global $gpdb;

	$errors = gp_upgrade_db();

	if ( $errors ) return $errors;

	gp_update_option( 'uri', guess_uri() );
}

function gp_create_initial_contents( $user_name =  null, $admin_password = null, $admin_email = null ) {
	global $gpdb;

	$gpdb->insert( $gpdb->projects, array( 'name' => __('Sample'), 'slug' => __('sample'), 'description' => __('A Sample Project'), 'path' => __('sample') ) );
	$gpdb->insert( $gpdb->originals, array( 'project_id' => 1, 'singular' => __('GlotPress FTW'), 'comment' => __('FTW means For The Win'), 'context' => 'dashboard', 'references' => 'bigfile:666 little-dir/small-file.php:71' ) );
	$gpdb->insert( $gpdb->originals, array( 'project_id' => 1, 'singular' => __('A GlotPress'), 'plural' => __('Many GlotPresses') ) );

	$gpdb->insert( $gpdb->translation_sets, array( 'name' => __('My Translation'), 'slug' => __('my'), 'project_id' => 1, 'locale' => 'bg', ) );

	if ( isset( $user_name, $admin_password, $admin_email ) ) {
		$admin = GP::$user->create( array( 'user_login' => $user_name, 'user_pass' => $admin_password, 'user_email' => $admin_email ) );
		GP::$permission->create( array( 'user_id' => $admin->id, 'action' => 'admin' ) );
	}
}