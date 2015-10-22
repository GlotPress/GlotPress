<?php

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
	
	$gp_user = GP::$user->get($user_id);
	
	// Unlike the profile edit function, we only get the user id passed in as a parameter.
	$per_page = (int) $_POST['per_page'];
	$gp_user->set_meta( 'per_page', $per_page );

	$default_sort = array(
		'by'  => 'priority',
		'how' => 'desc'
	);
	$user_sort = wp_parse_args( $_POST['default_sort'], $default_sort );

	$gp_user->set_meta( 'default_sort', $user_sort );
}

// Handle the WordPress user profile items
add_action( 'show_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'edit_user_profile', 'gp_wp_profile', 10, 1 );
add_action( 'personal_options_update', 'gp_wp_profile_update', 10, 1 );
add_action( 'edit_user_profile_update', 'gp_wp_profile_update', 10, 1 );
