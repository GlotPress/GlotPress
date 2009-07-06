<?php

/**
 * Guesses the final installed URI based on the location of the install script
 *
 * @return string The guessed URI
 */
function guess_uri()
{
	$schema = 'http://';
	if ( isset( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) {
		$schema = 'https://';
	}
	$uri = preg_replace( '|/[^/]*$|i', '/', $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

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

	gp_update_db_version();
    
}

function gp_upgrade() {
    return gp_upgrade_db();
}

function gp_install() {
    global $gpdb;
    
    $errors = gp_upgrade_db();
    
	if ( $errors ) return $errors;
	
	gp_update_option( 'uri', guess_uri() );
	
	$gpdb->insert( $gpdb->projects, array('name' => 'Sample', 'slug' => 'sample', 'description' => 'A Sample Project', 'path' => 'sample') );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => 'GlotPress FTW', 'comment' => 'FTW means For The Win', 'context' => 'dashboard', 'references' => 'bigfile:666 little-dir/small-file.php:71' ) );
	$gpdb->insert( $gpdb->originals, array('project_id' => 1, 'singular' => 'A GlotPress', 'plural' => 'Many GlotPresses' ) );
	
	// TODO: ask the user for an e-mail -- copy WordPress install process
	GP_User::create( array( 'user_login' => 'admin', 'user_pass' => 'a', 'user_email' => 'baba@baba.net' ) );
	return array();
}