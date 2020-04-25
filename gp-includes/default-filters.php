<?php
/**
 * Sets up the default filters and actions for most
 * of the GlotPress hooks.
 *
 * If you need to remove a default hook, this file will
 * give you the priority to use for removing the hook.
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

// Display filters.
add_filter( 'gp_original_extracted_comments', 'wptexturize' );
add_filter( 'gp_original_extracted_comments', 'convert_chars' );
add_filter( 'gp_original_extracted_comments', 'make_clickable' );
add_filter( 'gp_original_extracted_comments', 'convert_smilies' );

add_filter( 'gp_project_description', 'wptexturize' );
add_filter( 'gp_project_description', 'convert_chars' );
add_filter( 'gp_project_description', 'make_clickable' );
add_filter( 'gp_project_description', 'force_balance_tags' );
add_filter( 'gp_project_description', 'convert_smilies' );
add_filter( 'gp_project_description', 'wpautop' );
add_filter( 'gp_project_description', 'wp_kses_post' );

add_filter( 'gp_glossary_description', 'wptexturize' );
add_filter( 'gp_glossary_description', 'convert_chars' );
add_filter( 'gp_glossary_description', 'make_clickable' );
add_filter( 'gp_glossary_description', 'force_balance_tags' );
add_filter( 'gp_glossary_description', 'convert_smilies' );
add_filter( 'gp_glossary_description', 'wpautop' );
add_filter( 'gp_glossary_description', 'wp_kses_post' );

add_filter( 'gp_title', 'wptexturize' );
add_filter( 'gp_title', 'convert_chars' );
add_filter( 'gp_title', 'esc_html' );
