<?php
/**
 * Functions, which make work with strings easier
 */

function gp_startswith( $haystack, $needle ) {
	return 0 === strpos( $haystack, $needle );
}

function gp_endswith( $haystack, $needle ) {
	return $needle === substr( $haystack, -strlen( $needle ));
}

/**
 * Adds a slash after the string, while makes sure not to double it
 * if it already exists
 */
function gp_add_slash( $string ) {
	return rtrim( $string, '/' ) . '/';
}