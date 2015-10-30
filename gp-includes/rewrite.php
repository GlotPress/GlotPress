<?php
/**
 * Defines WordPress rewrites and query vars
 */

/**
 * Add WP rewrite rules to avoid WP thinking that GP pages are 404
 *
 * @since 1.0.0
 */
function gp_rewrite_rules() {
    $gp_base = trim( gp_url_base_path(), '/' );

    if ( ! $gp_base ) {
        // When GlotPress is set to take over the root of the site,
        // add a special rule that WordPress uses to route requests to root.
        add_rewrite_rule( '$', 'index.php?gp_route', 'top' );

        $match_regex = '^(.*)$';
    } else {
        $match_regex = '^' . $gp_base . '/?(.*)$';
    }

    add_rewrite_rule( $match_regex, 'index.php?gp_route=$matches[1]', 'top' );
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
    if ( array_key_exists( 'gp_route', $wp->query_vars ) && GP_ROUTING && ! is_admin() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
        GP::$router->route();
    }
}