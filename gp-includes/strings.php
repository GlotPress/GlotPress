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

function gp_in( $needle, $haystack ) {
	return false !== strpos( $haystack, $needle );
}

/**
 * Adds a slash after the string, while makes sure not to double it
 * if it already exists
 */
function gp_add_slash( $string ) {
	return rtrim( $string, '/' ) . '/';
}

if ( function_exists('mb_strtolower') ):
function gp_strtolower( $str ) {
	return mb_strtolower( $str );
}
else:
function gp_strtolower( $str ) {
	return strtolower( $str );
}
endif;

if ( function_exists('mb_strlen') ):
function gp_strlen( $str ) {
	return mb_strlen( $str );
}
else:
function gp_strlen( $str ) {
	return preg_match_all("/.{1}/us", $str, $dummy);
}
endif;

function gp_sanitize_for_url( $name ) {
	$name = trim( $name );
	$name = gp_strtolower( $name );
	$name = preg_replace( '/&.+?;/', '', $name ); // kill entities
	$name = str_replace( '.', '-', $name );
	$name = preg_replace('|[#$%&~/.\-;:=,?@\[\]+]|', '', $name);
	$name = preg_replace( '/\s+/', '-', $name );
	$name = preg_replace( '|-+|', '-', $name );
	$name = trim($name, '-');
	return $name;
}

/**
 * Similar to {@link esc_attr()}, but double encode entities
 */
function gp_esc_attr_with_entities( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = _wp_specialchars( $safe_text, ENT_QUOTES, false, true );
	return apply_filters( 'attribute_escape', $safe_text, $text );
	
}