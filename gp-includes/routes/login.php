<?php
class GP_Route_Login extends GP_Route_Main {
	function login_get() {
		if ( GP::$user->logged_in() ) {
			$this->redirect( gp_url( '/' ) );
			return;
		}
		gp_tmpl_load( 'login', array() );
	}
	
	function login_post() {
		global $wp_users_object, $wp_auth_object;
				
		$user = GP::$user->by_login( $_POST['user_login'] );
				
		if ( !$user || is_wp_error($user) ) {
			$this->errors[] = __("Invalid username!");
			$this->redirect(  gp_url_login() );
			return;
		}
		
		if ( $user->login( gp_post( 'user_pass' ) ) ) {
			if ( gp_post( 'redirect_to' ) ) {
				$this->redirect( gp_post( 'redirect_to' ) );
			} else {
				$this->notices[] = sprintf( __("Welcome, %s!"), $_POST['user_login'] );
				$this->redirect( gp_url_public_root() );
			}
		} else {
			$this->errors[] = __("Invalid password!");
			$this->redirect(  gp_url_login() );
		}
	}
	
	function logout() {
		GP::$user->logout();
		$this->redirect( gp_url( '/' ) );
	}
}
