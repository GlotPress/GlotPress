<?php
/**
 * Defines GlotPress rewrite rules and query vars.
 *
 * @package GlotPress
 * @subpackage Rewrite
 */


/**
 * Generate the WP rewrite rules.
 *
 * @since 1.0.0
 *
 * @param string|bool $gp_base
 *
 * @return string
 */
function gp_generate_rewrite_rules( $gp_base = false ) {
	if ( false === $gp_base ) {
		$gp_base = trim( gp_url_base_path(), '/' );
	}

	if ( ! $gp_base ) {
		$match_regex = '^(.*)$';
	} else {
		$match_regex = '^' . $gp_base . '/?(.*)$';
	}

	return $match_regex;
}

/**
 * Add WP rewrite rules to avoid WP thinking that GP pages are 404.
 *
 * @since 1.0.0
 */
function gp_rewrite_rules() {
	$gp_base = trim( gp_url_base_path(), '/' );

	if ( ! $gp_base ) {
		/*
		 * When GlotPress is set to take over the root of the site,
		 * add a special rule that WordPress uses to route requests to root.
		 */
		add_rewrite_rule( '$', 'index.php?gp_route', 'top' );
	}

	$match_regex = gp_generate_rewrite_rules( $gp_base );

	add_rewrite_rule( $match_regex, 'index.php?gp_route=$matches[1]', 'top' );

	/*
	 * Check to see if the rewrite rule has changed, if so, update the option
	 * and flush the rewrite rules.
	 * Save the rewrite rule to an option so we have something to compare against later.
	 * We don't need to worry about the root rewrite rule above as it is always the same.
	 */
	if ( $match_regex != get_option( 'gp_rewrite_rule' ) ) {
		update_option( 'gp_rewrite_rule', $match_regex );
		flush_rewrite_rules( false );
	}
}

/**
 * Query vars for GP rewrite rules
 *
 * @since 1.0.0
 *
 * @param array $query_vars
 *
 * @return array
 */
function gp_query_vars( $query_vars ) {
	$query_vars[] = 'gp_route';

	return $query_vars;
}

/**
 * GP run route
 *
 * @since 1.0.0
 */
function gp_run_route() {
	gp_populate_notices();
	global $wp;
	if ( array_key_exists( 'gp_route', $wp->query_vars ) && GP_ROUTING && ! is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
		GP::$router->route();
	}
}
