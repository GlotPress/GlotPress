<?php

function gp_install() {
	global $gpdb;
	
	$alterations = BP_SQL_Schema_Parser::delta( $gpdb, gp_schema_get() );
	$messages = $alterations['messages'];
	$errors = $alterations['errors'];
	
	if ( $errors ) return $errors;

	gp_update_db_version();
	gp_update_option( 'uri', guess_uri() );
	
	$gpdb->insert( $gpdb->projects, array('name' => 'sample', 'slug' => 'sample', 'description' => 'A Sample Project') );
	
	return array();
}