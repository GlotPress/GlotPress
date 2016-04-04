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

// WP
add_action( 'parse_request', 'gp_parse_request' );

// Styles and scripts
add_action( 'gp_head', 'wp_enqueue_scripts' );
add_action( 'gp_head', 'gp_print_styles' );
add_action( 'gp_head', 'gp_print_scripts' );

// Rewrite rules
add_filter( 'query_vars', 'gp_query_vars' );
add_action( 'template_redirect', 'gp_run_route' );

// Users
add_action( 'deleted_user', 'gp_delete_user_permissions' );

// Query
add_action( 'pre_get_posts', 'gp_set_is_home_false' );

// WordPress profile options
add_action( 'show_user_profile', 'gp_wp_profile_options' );
add_action( 'edit_user_profile', 'gp_wp_profile_options' );
add_action( 'personal_options_update', 'gp_wp_profile_options_update' );
add_action( 'edit_user_profile_update', 'gp_wp_profile_options_update' );
