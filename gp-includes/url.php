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
	if ( empty( $args ) ) return '';
	$start_slash = gp_startswith( $args[0], '/' ) ? '/' : '';
	$end_slash = gp_endswith( $args[ count($args) - 1 ] , '/' )? '/' : '';
	$args = array_map( create_function( '$x', 'return trim($x, "/");'), $args );
	return $start_slash . implode( '/', $args ) . $end_slash;
}

function gp_url($path, $query = null ) {
	$url = gp_url_join( gp_get_option( 'url' ), $path );
	if ( $query && is_array( $query ) ) 
		$url = add_query_arg( urlencode_deep( $query ), $url );
	elseif ( $query )
		$url .= '?' . ltrim( $query, '?' );
	return $url;
}

function gp_url_project( $project_or_slug, $path = '', $query = null ) {
	$slug = is_object( $project_or_slug )? $project_or_slug->slug : $project_or_slug;
	return gp_url( gp_url_join( $slug, $path ), $query );
}

/**
 * Constructs URL for a project and locale.
 * /<project-path>/<locale>/<path>/<page>
 */
function gp_url_project_locale( $project_or_slug, $locale, $path = '', $query = null ) {
	return gp_url_project( $project_or_slug, gp_url_join( $locale, $path ), $query );
}