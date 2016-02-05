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

/**
 * Initializes rewrite rules and provides the 'gp_init' action.
 *
 * @since 1.0.0
 */
function gp_init() {
	gp_rewrite_rules();

	/**
	 * Fires after GlotPress has finished loading but before any headers are sent.
	 *
	 * @since 1.0.0
	 */
	do_action( 'gp_init' );
}

/**
 * Fires during the WP parse_request hook to check to see if we're on a GlotPress page, if so
 * we can abort the main WP_Query logic as we won't need it in GlotPress.
 * a matching page.
 *
 * @since 1.0.0
 */
function gp_parse_request() {
	if ( is_glotpress() ) {
		add_filter( 'posts_request', 'gp_abort_main_wp_query', 10, 2 );
	}
}

/**
 * Prevents executing WP_Query's default queries on GlotPress requests.
 *
 * The following code effectively avoid running the main WP_Query queries by setting values before
 * they are run.
 *
 * @link http://wordpress.stackexchange.com/a/145386/40969 Original source.
 *
 * @since 1.0.0
 *
 * @param array    $sql  The complete SQL query.
 * @param WP_Query $wp_query The WP_Query instance (passed by reference).
 * @return string|false False if GlotPress request, SQL query if not.
 */
function gp_abort_main_wp_query( $sql, WP_Query $wp_query ) {
	if ( $wp_query->is_main_query() ) {
		// Prevent SELECT FOUND_ROWS() query.
		$wp_query->query_vars['no_found_rows'] = true;

		// Prevent post term and meta cache update queries.
		$wp_query->query_vars['cache_results'] = false;

		return false;
	}

	return $sql;
}

/**
 * Deletes user's permissions when they are deleted from WordPress
 * via WP's 'deleted_user' action.
 *
 * @since 1.0.0
 */
function gp_delete_user_permissions( $user_id ) {
	$permissions = GP::$permission->find_many( array( 'user_id' => $user_id ) );

	foreach( $permissions as $permission ) {
		$permission->delete();
	}
}
