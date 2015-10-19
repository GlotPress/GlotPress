<?php
/**
 * Sets up the default filters and actions for most
 * of the GlotPress hooks.
 *
 * If you need to remove a default hook, this file will
 * give you the priority for which to use to remove the
 * hook.
 *
 * @package GlotPress
 */

// Actions
add_action( 'init', 'gp_init' );

// Styles and scripts
add_action( 'gp_head', 'wp_enqueue_scripts' );
add_action( 'gp_head', 'gp_print_styles' );
add_action( 'gp_head', 'gp_print_scripts' );

// Rewrite rules
add_filter( 'query_vars', 'gp_query_vars' );
add_action( 'template_redirect', 'gp_run_route' );
