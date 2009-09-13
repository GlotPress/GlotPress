<?php
/**
 * Functions, which deal with URLs: manipulation, generation
 */

/**
 * Gives the path of an URL
 *
 * @param string $url Optional. The default is the GlotPress URL
 */
function gp_url_path( $url = null ) {
	if ( is_null( $url ) ) $url = gp_get_option( 'url' );
	$parsed = parse_url( $url );
	return $parsed['path'];
}

/**
 * Joins paths, and takes care of slashes between them
 */
function gp_url_join() {
	$args = func_get_args();
	// we need array_values() in order to make sure the indices of $args are consecutive from 0 to count()-1
	$args = array_values( array_filter( gp_array_flatten( $args ) ) );
	if ( empty( $args ) ) return '';
	$start_slash = gp_startswith( $args[0], '/' ) && trim( $args[0], '/' ) != '' ? '/' : '';
	$end_slash = gp_endswith( $args[ count($args) - 1 ] , '/' ) && trim($args[ count($args) - 1 ], '/') != '' ? '/' : '';
	$args = array_map( create_function( '$x', 'return trim($x, "/");'), $args );
	return $start_slash . implode( '/', $args ) . $end_slash;
}

function gp_url( $path, $query = null ) {
	$url = gp_url_join( gp_url_path( gp_get_option( 'url' ) ), $path );
	if ( $query && is_array( $query ) )
		$url = add_query_arg( urlencode_deep( $query ), $url );
	elseif ( $query )
		$url .= '?' . ltrim( $query, '?' );
	return $url;
}

function gp_url_project( $project_or_path, $path = '', $query = null ) {
	$project_path = is_object( $project_or_path )? $project_or_path->path : $project_or_path;
	return gp_url( array( 'projects', $project_path, $path ), $query );
}

/**
 * Constructs URL for a project and locale.
 * /<project-path>/<locale>/<path>/<page>
 */
function gp_url_project_locale( $project_or_path, $locale, $path = '', $query = null ) {
	return gp_url_project( $project_or_path, array( $locale, $path ), $query );
}

function gp_url_img( $file ) {
	return gp_url( array( 'img', $file ) );
}

function gp_url_current() {
	// TODO: https
	// TODO: port
	$host = gp_array_get( $_SERVER, 'HTTP_HOST' );
	$path_and_args = gp_array_get( $_SERVER, 'REQUEST_URI');
	return "http://{$host}{$path_and_args}";
}