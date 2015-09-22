<?php

function gp_urldecode_deep($value) {
	$value = is_array( $value ) ? array_map( 'gp_urldecode_deep', $value ) : urldecode( $value );
	return $value;
}

/**
 * Makes all key/value pairs in $vars global variables
 */
function gp_set_globals( $vars ) {
	foreach( $vars as $name => $value ) {
		$GLOBALS[ $name ] = $value;
	}
}
