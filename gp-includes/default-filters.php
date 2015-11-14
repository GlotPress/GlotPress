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

// Handle the WordPress user profile items
add_action( 'show_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'edit_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'personal_options_update', 'gp_wp_profile_update', 10, 1 );
add_action( 'edit_user_profile_update', 'gp_wp_profile_update', 10, 1 );
