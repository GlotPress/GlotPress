<?php
class GP_Administrator_Permission extends GP_Permission {

	var $table_basename = 'gp_permissions';
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id' );
	var $non_db_field_names = array();
	var $non_updatable_attributes = array( 'id' );

	/**
	 * Adds restrictions to the fields in the object.
	 *
	 * @since 1.1.0
	 */
	function restrict_fields( $permission ) {
		$permission->user_id_should_not_be( 'empty' );
		$permission->action_should_not_be( 'empty' );
		$permission->object_type_should_be( 'empty' );
		$permission->object_id_should_be( 'empty' );
	}
}

GP::$administrator_permission = new GP_Administrator_Permission();
