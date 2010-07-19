<?php
/**
 * Guesses the final installed URI based on the location of the install script
 *
 * @return string The guessed URI
 */
function guess_uri()
{
	$schema = 'http://';
	if ( strtolower( gp_array_get( $_SERVER, 'HTTPS' ) ) == 'on' ) {
		$schema = 'https://';
	}
	$uri = preg_replace( '|/[^/]*$|i', '/', $schema . gp_array_get( $_SERVER, 'HTTP_HOST') . gp_array_get( $_SERVER, 'REQUEST_URI' ) );

	return rtrim( $uri, " \t\n\r\0\x0B/" ) . '/';
}

function gp_update_db_version() {
	gp_update_option( 'gp_db_version', gp_get_option( 'gp_db_version' ) );
}

function gp_upgrade_db() {
	global $gpdb;
	
	$alterations = BP_SQL_Schema_Parser::delta( $gpdb, gp_schema_get() );
	$messages = $alterations['messages'];
	$errors = $alterations['errors'];
	if ( $errors ) return $errors;
	
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

function gp_create_initial_contents() {	
	global $gpdb;
	$gpdb->insert( $gpdb->projects, array('name' => __('Sample'), 'slug' => __('sample'), 'description' => __('A Sample Project'), 'path' => __('sample') ) );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => __('GlotPress FTW'), 'comment' => __('FTW means For The Win'), 'context' => 'dashboard', 'references' => 'bigfile:666 little-dir/small-file.php:71' ) );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => __('A GlotPress'), 'plural' => __('Many GlotPresses') ) );
	
	$gpdb->insert( $gpdb->translation_sets, array( 'name' => __('My Translation'), 'slug' => __('my'), 'project_id' => 1, 'locale' => 'bg', ) );
	
	// TODO: ask the user for an e-mail -- borrow WordPress install process
	if ( !defined('CUSTOM_USER_TABLE') ) {
		$admin = GP::$user->create( array( 'user_login' => 'admin', 'user_pass' => 'a', 'user_email' => 'baba@baba.net' ) );
		GP::$permission->create( array( 'user_id' => $admin->id, 'action' => 'admin' ) );
	}
	// TODO: ask the user to choose an admin user if using custom table
}