<?php
/**
 * Filters and actions assigned by default
 */

// Styles and scripts
add_action( 'gp_head', 'wp_enqueue_scripts' );
add_action( 'gp_head', 'gp_print_styles' );
add_action( 'gp_head', 'gp_print_scripts' );

// Rewrite rules
add_filter( 'query_vars', 'gp_query_vars' );
add_action( 'init', 'gp_rewrite_rules' );
add_action( 'template_redirect', 'gp_run_route' );