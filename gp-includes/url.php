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
	if ( is_null( $url ) ) $url = gp_url();
	$parsed = parse_url( $url );
	return $parsed['path'];
}

/**
 * Joins paths, and takes care of slashes between them
 *
 * Example: gp_url_join( '/project', array( 'wp', 'dev) ) -> '/project/wp/dev'
 *
 * The function will keep leading and trailing slashes of the whole URL, but won't
 * allow more than consecutive slash inside.
 *
 * @param mixed components... arbitrary number of string or path components
 * @return string URL, built of all the components, separated with /
 */
function gp_url_join() {
	$components = func_get_args();
	$components_in_flat_array = array_filter( gp_array_flatten( $components ) );
	$components_with_slashes = implode( '/', $components_in_flat_array );
	$components_without_consecutive_slashes = preg_replace( '|/{2,}|', '/', $components_with_slashes );
	$components_without_consecutive_slashes = str_replace( array( 'http:/', 'https:/' ), array( 'http://', 'https://' ), $components_without_consecutive_slashes );
	return $components_without_consecutive_slashes;
}

/**
 * Builds a URL relative to the GlotPress' domain root
 *
 * @param mixed $path string path or array of path components
 * @param array $query associative array of query arguments (optional)
 */
function gp_url( $path = '/', $query = null ) {
	return apply_filters( 'gp_url', gp_url_add_path_and_query( gp_url_path( gp_url_public_root() ), $path, $query ), $path, $query );
}

function gp_url_add_path_and_query( $base, $path, $query ) {
	// todo: same domain with current url?
	$url = gp_url_join( $base, $path );
	if ( $query && is_array( $query ) )
		$url = add_query_arg( urlencode_deep( $query ), $url );
	elseif ( $query )
		$url .= '?' . ltrim( $query, '?' );
	return apply_filters( 'gp_url_add_path_and_query', $url, $base, $path, $query );
}

/**
 * Converts an absolute URL to the corresponding SSL URL if the GlotPress
 * settings allow SSL
 */
function gp_url_ssl( $url ) {
	if ( defined( 'GP_SSL' ) && GP_SSL ) {
		$url = preg_replace( '/^http:/', 'https:', $url );
	}
	return $url;
}

function gp_url_public_root() {
	return home_url( gp_url_base_path() );
}

/**
 * Constructs URL for a project and locale.
 * /<project-path>/<locale>/<path>/<page>
 */
function gp_url_project_locale( $project_or_path, $locale, $path = '', $query = null ) {
	return gp_url_project( $project_or_path, array( $locale, $path ), $query );
}

/**
 * Get the URL for an image file
 *
 * @param string $file Image filename
 *
 * @return string
 */
function gp_url_img( $file ) {
	return gp_plugin_url( "assets/img/$file" );
}

/**
 * The URL of the current page
 */
function gp_url_current() {
	$protocol      = is_ssl()? 'https://' : 'http://';
	$host          = gp_array_get( $_SERVER, 'HTTP_HOST' );
	$path_and_args = gp_array_get( $_SERVER, 'REQUEST_URI' );

	return $protocol . $host . $path_and_args;
}

function gp_url_project( $project_or_path = '', $path = '', $query = null ) {
	$project_path = is_object( $project_or_path )? $project_or_path->path : $project_or_path;
	return gp_url( array( 'projects', $project_path, $path ), $query );
}

function gp_url_profile( $user_nicename ) {
	return apply_filters( 'gp_url_profile', gp_url( array( '/profile', $user_nicename ) ), $user_nicename );
}

function gp_url_base_path() {
	return '/' . trim( gp_const_get( 'GP_URL_BASE', 'glotpress' ), '/' ) . '/';
}

function gp_plugin_url( $path = '' ) {
	return plugins_url( $path, GP_PLUGIN_FILE );
}
