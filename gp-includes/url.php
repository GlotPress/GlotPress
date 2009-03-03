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

function gp_url($path, $query = null ) {
	$url = gp_get_option( 'url' ) . ltrim( $path, '/' );
	if ( $query && is_array( $query ) ) 
		$url = add_query_arg( urlencode_deep( $query ), $url );
	elseif ( $query )
		$url .= '?' . ltrim( $query, '?' );
	return $url;
}

function gp_project_url( $project_or_slug, $query = null ) {
	$slug = is_object( $project_or_slug )? $project_or_slug->slug : $project_or_slug;
	return gp_url( $slug, $query );
}