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
 * @param string|bool $gp_base Optional. The base of all GlotPress URLs.
 *                             Defaults to the `GP_URL_BASE` constant.
 * @return array Rewrite rules that transform the URL structure
 *               to a set of query vars
 */
function gp_generate_rewrite_rules( $gp_base = false ) {
	if ( false === $gp_base ) {
		$gp_base = trim( gp_url_base_path(), '/' );
	}

	$rules = array();
	if ( ! $gp_base ) {
		$rules['$'] = 'index.php?gp_route';
		$rules['^(.*)$'] = 'index.php?gp_route=$matches[1]';
	} else {
		$rules['^' . $gp_base . '$'] = 'index.php?gp_route';
		$rules['^' . $gp_base . '\/+(.*)$'] = 'index.php?gp_route=$matches[1]';
	}

	return $rules;
}

/**
 * Add WP rewrite rules to avoid WP thinking that GP pages are 404.
 *
 * @since 1.0.0
 */
function gp_rewrite_rules() {
	$rewrite_rules = gp_generate_rewrite_rules();
	foreach ( $rewrite_rules as $regex => $query ) {
		add_rewrite_rule( $regex, $query, 'top' );
	}

	/*
	 * Check to see if the rewrite rule has changed, if so, update the option
	 * and flush the rewrite rules.
	 * Save the rewrite rule to an option so we have something to compare against later.
	 * We don't need to worry about the root rewrite rule above as it is always the same.
	 */
	if ( $rewrite_rules != get_option( 'gp_rewrite_rule' ) ) {
		update_option( 'gp_rewrite_rule', $rewrite_rules );
		flush_rewrite_rules( false );
	}
}

/**
 * Query vars for GP rewrite rules
 *
 * @since 1.0.0
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

	if ( is_glotpress() ) {
		GP::$router->route();
	}
}

/**
 * Determine if the page requested is handled by GlotPress.
 *
 * @since 1.0.0
 *
 * @return bool Whether the request is handled by GlotPress.
 */
function is_glotpress() {
	global $wp;

	if ( ! is_admin() && GP_ROUTING && null != $wp->query_vars && array_key_exists( 'gp_route', $wp->query_vars ) ) {
		return true;
	}
	return false;
}

/**
 * Sets `WP_Query->is_home` to false during GlotPress requests.
 *
 * @since 1.0.0
 *
 * @param WP_Query $query The WP_Query instance.
 */
function gp_set_is_home_false( $query ) {
	if ( is_glotpress() && $query->is_home() && $query->is_main_query() ) {
		$query->is_home = false;
	}
}
