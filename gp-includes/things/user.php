<?php
class GP_User {
	
	/**
	 * @static
	 */
	function create( $args ) {
		global $wp_users_object;
		if ( isset( $args['id'] ) ) {
			$args['ID'] = $args['id'];
			unset( $args['id'] );
		}
		return GP_User::coerce( $wp_users_object->new_user( $args ) );
	}
	
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
		if ( is_wp_error( $user ) || !$user )
			return false;
		else
			return new GP_User( $user );
	}
	
	/**
	 * @static
	 */
	function logged_in() {
		global $wp_auth_object;
		$coerced = GP_User::coerce( $wp_auth_object->get_current_user() );
		return ( $coerced && $coerced->id );
	}
	
	/**
	 * @static
	 */
	function current() {
		global $wp_auth_object;
		if ( self::logged_in() )
			return GP_User::coerce( $wp_auth_object->get_current_user() );
		else
			return new GP_User( array( 'id' => 0, ) );
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

		if ( !isset( $this->id) && isset( $this->ID ) ) {
			$this->id = $this->ID;
		}
	}
	
	/**
	 * Determines whether the user is an admin
	 */
	function admin() {
		return (bool)GP_Permission::find( array( 'user' => $this, 'action' => 'admin' ));
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
	
	/**
	 * Makes the user the current user of this session. Sets the cookies and such.
	 */
	function set_as_current() {
		global $wp_auth_object;
		$wp_auth_object->set_current_user( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id, 0, 0, 'logged_in');
	}
	
	/**
	 * Determines whether the user can do $action on the instance of $object_type with id $object_id.
	 * 
	 * If the method is called statically, it uses the current session user.
	 * 
	 * Example: $user->can( 'read', 'translation-set', 11 );
	 */
	function can( $action, $object_type = null, $object_id = null) {
		$user = null;
		if ( isset( $this ) )
			$user = $this;
		elseif ( GP_User::logged_in() )
			$user = GP_User::current();
		$args = compact( 'user', 'action', 'object_type', 'object_id' );
		$preliminary = apply_filters( 'pre_can_user', 'no-verdict', $args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}
		if ( $user && $user->admin() ) {
			$verdict = true;
		} else {
			$verdict = (bool) GP_Permission::find( $args );
		}
		return apply_filters( 'can_user', $verdict, $args );
	}
}