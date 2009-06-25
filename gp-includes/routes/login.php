<?php
class GP_Route_Login {
	function login_get() {
		#TODO: if logged in -- redirect somewhere
		gp_tmpl_load( 'login', array() );
	}
	
	function login_post() {
		global $wp_users_object, $wp_auth_object;
		$user = $wp_users_object->get_user( $_POST['user_login'], array( 'by' => 'login' ) );
		
		if ( is_wp_error($user) || !$user ) {
			gp_notice_set( __("Invalid username!"), 'error' );
			wp_redirect(gp_url('/login'));
		}
		
		if ( WP_Pass::check_password( $_POST['user_pass'], $user->user_pass, $user->ID ) ) {
			$wp_auth_object->set_current_user( $user->ID );
			$wp_auth_object->set_auth_cookie( $user->ID );
			$wp_auth_object->set_auth_cookie( $user->ID, 0, 0, 'logged_in');
			gp_notice_set( __("Welcome, gonzo &amp; bonzo!") );
			wp_redirect(gp_url('/'));
		} else {
			gp_notice_set( __("Invalid password!"), 'error' );
			wp_redirect(gp_url('/login'));
		}
	}
	
	function logout() {
		global $wp_auth_object;
		$wp_auth_object->clear_auth_cookie();
		wp_redirect(gp_url('/'));
	}
}