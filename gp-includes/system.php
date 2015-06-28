<?php

function gp_urldecode_deep($value) {
	$value = is_array( $value ) ? array_map( 'gp_urldecode_deep', $value ) : urldecode( $value );
	return $value;
}

//TODO: add server-guessing code from bb-load.php in a function here

function gp_is_installed() {
	// Check cache first. If the tables go away and we have true cached, oh well.
	if ( wp_cache_get( 'gp_is_installed' ) )
		return true;

	global $gpdb;
	$gpdb->flush();
	$gpdb->suppress_errors();
	$gpdb->query("SELECT id FROM $gpdb->translations WHERE 1=0");
	$gpdb->suppress_errors(false);
	$installed = ! (bool) $gpdb->last_error;
	wp_cache_set( 'gp_is_installed', $installed );
	return $installed;
}

/**
 * Makes all key/value pairs in $vars global variables
 */
function gp_set_globals( $vars ) {
	foreach( $vars as $name => $value ) {
		$GLOBALS[ $name ] = $value;
	}
}
