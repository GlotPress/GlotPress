<?php
/**
 * gp_unregister_GLOBALS() - Turn register globals off
 *
 * @access private
 * @return null Will return null if register_globals PHP directive was disabled
 */
function gp_unregister_GLOBALS() {
	if ( !ini_get( 'register_globals' ) ) {
		return;
	}

	if ( isset($_REQUEST['GLOBALS']) ) {
		die( 'GLOBALS overwrite attempt detected' );
	}

	// Variables that shouldn't be unset
	$noUnset = array( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', 'gp_table_prefix' );

	$input = array_merge( $_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset( $_SESSION ) && is_array( $_SESSION ) ? $_SESSION : array() );
	foreach ( $input as $k => $v ) {
		if ( !in_array( $k, $noUnset ) && isset( $GLOBALS[$k] ) ) {
			$GLOBALS[$k] = NULL;
			unset( $GLOBALS[$k] );
		}
	}
}

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
function gp_set_globals( &$vars ) {
	foreach( $vars as $name => &$value ) {
		$GLOBALS[$name] = &$value;
	}
}
