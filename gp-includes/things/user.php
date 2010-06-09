<?php
class GP_User extends GP_Thing {
	
	var $table_basename = 'users';
	var $field_names = array( 'id', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name' );
	var $non_updatable_attributes = array( 'ID' );
	
	function create( $args ) {
		global $wp_users_object;
		if ( isset( $args['id'] ) ) {
			$args['ID'] = $args['id'];
			unset( $args['id'] );
		}
		$user = $wp_users_object->new_user( $args );
		return $this->coerce( $user );
	}
	
	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['ID'] ) ) {
			$args['id'] = $args['ID'];
			unset( $args['ID'] );
		}
		return $args;
	}
	
	function get( $user_or_id ) {
		global $wp_users_object;
		if ( is_object( $user_or_id ) ) $user_or_id = $user_or_id->id;
		return $this->coerce( $wp_users_object->get_user( $user_or_id ) );
	}
	
	function by_login( $login ) {
		global $wp_users_object;
		$user = $wp_users_object->get_user( $login, array( 'by' => 'login' ) );
		return $this->coerce( $user );
	}
	
	function logged_in() {
		global $wp_auth_object;
		$coerced = $this->coerce( $wp_auth_object->get_current_user() );
		return ( $coerced && $coerced->id );
	}
	
	function current() {
		global $wp_auth_object;
		if ( $this->logged_in() )
			return $this->coerce( $wp_auth_object->get_current_user() );
		else
			return new GP_User( array( 'id' => 0, ) );
	}
	
	function logout() {
		global $wp_auth_object;
		$wp_auth_object->clear_auth_cookie();
	}
		
	/**
	 * Determines whether the user is an admin
	 */
	function admin() {
		return $this->can( 'admin' );
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
	function can( $action, $object_type = null, $object_id = null, $extra = null ) {
		$user = null;
		if ( isset( $this ) && $this->id )
			$user = $this;
		elseif ( GP::$user->logged_in() )
			$user = GP::$user->current();
		$user_id = $user? $user->id : null;
		$args = $filter_args = compact( 'user_id', 'action', 'object_type', 'object_id' );
		$filter_args['user'] = $user;
		$filter_args['extra'] = $extra;
		$preliminary = apply_filters( 'pre_can_user', 'no-verdict', $filter_args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}
		$verdict =
			GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
			GP::$permission->find_one( $args ) ||
			GP::$permission->find_one( array_merge( $args, array( 'object_id' => null ) ) );
		return apply_filters( 'can_user', $verdict, $filter_args );
	}
	
	function get_meta( $key ) {
		global $wp_users_object;
		if ( !$user = $wp_users_object->get_user( $this->id ) ) {
			return;
		}

		$key = gp_sanitize_meta_key( $key );
		if ( !isset( $user->$key ) ) {
			return;
		}
		return $user->$key;
	}
	
	function set_meta( $key, $value ) {
		return gp_update_meta( $this->id, $key, $value, 'user' );
	}
	
	function delete_meta( $key ) {
		return gp_delete_meta( $this->id, $key, '', 'user' );
	}
	
	
	function reintialize_wp_users_object() {
		global $gpdb, $wp_auth_object, $wp_users_object;
		$wp_users_object = new WP_Users( $gpdb );
		$wp_auth_object->users = $wp_users_object;
	}
}
GP::$user = new GP_User();