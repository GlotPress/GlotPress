<?php
/**
 * Filters and actions assigned by default
 */

// Styles and scripts
add_action( 'gp_head', 'wp_enqueue_scripts' );
add_action( 'gp_head', 'gp_print_styles' );
add_action( 'gp_head', 'gp_print_scripts' );

function gp_wp_profile( $user ) {
	// If the user cannot edit their profile, then don't show the settings.
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
?>
	<h3 id="gp-profile"><?php _e('GlotPress Profile'); ?></h3>
<?php		
	
	include( GP_PATH . './gp-templates/profile-edit.php' );
}

function gp_wp_profile_update( $user_id ) {
	// If the user cannot edit their profile, then don't save the settings
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
	
	$gp_route_profile = new GP_Route_Profile;
	
	$gp_route_profile->profile_post( $user_id );
	
	return true;
}

// Handle the WordPress user profile items
add_action( 'show_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'edit_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'personal_options_update', 'gp_wp_profile_update', 10, 1 );
add_action( 'edit_user_profile_update', 'gp_wp_profile_update', 10, 1 );
// Rewrite rules
add_filter( 'query_vars', 'gp_query_vars' );
add_action( 'init', 'gp_rewrite_rules' );
add_action( 'template_redirect', 'gp_run_route' );