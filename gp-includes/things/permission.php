<?php
class GP_Permission extends GP_Thing {

	var $table_basename = 'gp_permissions';
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id', );
	var $int_fields = array( 'id', 'user_id' );
	var $non_updatable_attributes = array( 'id', );

	function normalize_fields( $args ) {
		$args = (array)$args;
		foreach( $this->field_names as $field_name ) {
			if ( isset( $args[$field_name] ) ) {
				$args[$field_name] = $this->force_false_to_null( $args[$field_name] );
			}
		}
		return $args;
	}


	/**
	 * Determines whether the current user can do $action on the instance of $object_type with id $object_id.
	 *
	 * Example: GP::$permission->current_user_can( 'read', 'translation-set', 11 );
	 *
	 * @param string $action
	 * @param string $object_type
	 * @param int    $object_id
	 * @param mixed  $extra
	 */
	public function current_user_can( $action, $object_type = null, $object_id = null, $extra = null ) {
		$user = wp_get_current_user();

		return $this->user_can( $user, $action, $object_type, $object_id, $extra );
	}

	/**
	 * Determines whether the user can do $action on the instance of $object_type with id $object_id.
	 *
	 * Example: GP::$permission->user_can( $user, 'read', 'translation-set', 11 );
	 *
	 * @param int|object $user
	 * @param string     $action
	 * @param string     $object_type
	 * @param int        $object_id
	 * @param mixed      $extra
	 */
	public function user_can( $user, $action, $object_type = null, $object_id = null, $extra = null ) {
		if ( ! is_object( $user ) ) {
			$user = get_userdata( $user );
		}

		$user_id = null;
		if ( $user && $user->exists() ) {
			$user_id = $user->ID;
		}

		$args = $filter_args = compact( 'user_id', 'action', 'object_type', 'object_id' );
		$filter_args['user'] = $user;
		$filter_args['extra'] = $extra;
		$preliminary = apply_filters( 'gp_pre_can_user', 'no-verdict', $filter_args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}

		$verdict =
			$this->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
			$this->find_one( $args ) ||
			$this->find_one( array_merge( $args, array( 'object_id' => null ) ) );

		return apply_filters( 'gp_can_user', $verdict, $filter_args );
	}
}
GP::$permission = new GP_Permission();
