<?php
class GP_Route_Login extends GP_Route_Main {
	function login_get() {
		if ( !GP::$user->logged_in() ) {
			gp_tmpl_load( 'login', array() );
		}  else {
			gp_redirect( gp_url( '/' ) );
		}
	}
	
	function login_post() {
		global $wp_users_object, $wp_auth_object;
				
		$user = GP::$user->by_login( $_POST['user_login'] );
				
		if ( !$user || is_wp_error($user) ) {
			$this->errors[] = __("Invalid username!");
			gp_redirect( gp_url( '/login' ) );
			return;
		}
		
		if ( $user->login( $_POST['user_pass'] ) ) {
			if ( gp_post( 'redirect_to' ) && gp_startswith( gp_post( 'redirect_to' ), gp_url_base() ) ) {
				gp_redirect( gp_post( 'redirect_to' ) );
			} else {
				$this->notices[] = sprintf( __("Welcome, %s!"), $_POST['user_login'] );
				gp_redirect( gp_url_public_root() );
			}
		} else {
			$this->errors[] = __("Invalid password!");
			gp_redirect( gp_url( '/login' ) );
		}
	}
	
	function logout() {
		GP::$user->logout();
		gp_redirect( gp_url( '/' ) );
	}
}