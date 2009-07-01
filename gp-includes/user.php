<?php
class GP_User {
	/**
	 * @static
	 */
	function by_login( $login ) {
		global $wp_users_object;
		$user = $wp_users_object->get_user( $login, array( 'by' => 'login' ) );
		return GP_User::coerce( $user );
	}
	
	/**
	 * Converts a user object to the database or from $wp_users_object to GP_User
	 *
	 * @static
	 */
	function coerce( $user ) {
		if ( is_wp_error($user) || !$user )
			return $user;
		else
			return new GP_User( $user );
	}
	
	/**
	 * @static
	 */
	function logged_in() {
		global $wp_auth_object;
		return GP_User::coerce( $wp_auth_object->get_current_user() );
	}
	
	/**
	 * @static
	 */
	function current() {
		global $wp_auth_object;
		return GP_User::coerce( $wp_auth_object->get_current_user() );
	}
	
	/**
	 * @static
	 */
	function logout() {
		global $wp_auth_object;
		$wp_auth_object->clear_auth_cookie();
	}
		
	function GP_User( $user ) {
		if ( is_numeric( $user) )
			$user = $wp_users_object->get_user( $id );
		elseif ( is_array( $user ))
			$user = (object)$user;
			
		foreach ( get_object_vars( $user ) as $key => $value ) {
			$this->{$key} = $value;
		}

		$this->id = $this->ID;
	}
	
	/**
	 * Set $this as the current user if $password patches this user's password
	 */
	function login( $password ) {
 		if ( !WP_Pass::check_password( $password, $this->user_pass, $this->id ) ) {
			return false;
		}
		$this->set_as_current();
		return true;
	}
	
	function set_as_current() {
		global $wp_auth_object;
		$wp_auth_object->set_current_user( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id, 0, 0, 'logged_in');
	}
}