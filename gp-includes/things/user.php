<?php
class GP_User extends GP_Thing {

	var $table_basename = 'users';
	var $field_names = array( 'id', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name' );
	var $non_updatable_attributes = array( 'ID' );

	function create( $args ) {
		if ( isset( $args['id'] ) ) {
			$args['ID'] = $args['id'];
			unset( $args['id'] );
		}
		$user = wp_insert_user( $args );
		$user = get_userdata( $user );
		return $this->coerce( $user );
	}

	function update( $data, $where = null ) {
		return false;
	}

	function delete() {
		return false;
	}

	function delete_all( $where = false ) {
		return false;
	}
	function normalize_fields( $args ) {
		if ( $args instanceof WP_User ) {
			$args = $args->data;
		}
		$args = (array)$args;
		if ( isset( $args['ID'] ) ) {
			$args['id'] = $args['ID'];
			unset( $args['ID'] );
		}
		return $args;
	}

	function current() {
		if ( is_user_logged_in() )
			return $this->coerce( wp_get_current_user() );
		else
			return new GP_User( array( 'id' => 0, ) );
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
		elseif ( is_user_logged_in() )
			$user = GP::$user->current();
		$user_id = $user? $user->id : null;
		$args = $filter_args = compact( 'user_id', 'action', 'object_type', 'object_id' );
		$filter_args['user'] = $user;
		$filter_args['extra'] = $extra;
		$preliminary = apply_filters( 'gp_pre_can_user', 'no-verdict', $filter_args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}
		$verdict =
			GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
			GP::$permission->find_one( $args ) ||
			GP::$permission->find_one( array_merge( $args, array( 'object_id' => null ) ) );
		return apply_filters( 'gp_can_user', $verdict, $filter_args );
	}

}
GP::$user = new GP_User();
