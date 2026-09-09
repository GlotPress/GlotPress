<?php
/**
 * Things: GP_Administrator_Permission class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 2.0.0
 */

/**
 * Core class used to implement the administrator permissions.
 *
 * @since 2.0.0
 */
class GP_Administrator_Permission extends GP_Permission {

	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	var $table_basename = 'gp_permissions';

	/**
	 * List of field names for an administrator permission.
	 *
	 * @var array $field_names
	 */
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id' );

	/**
	 * List of non-database field names.
	 *
	 * @var array $non_db_field_names
	 */
	var $non_db_field_names = array();

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	var $non_updatable_attributes = array( 'id' );

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 2.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->user_id_should_not_be( 'empty' );
		$rules->action_should_not_be( 'empty' );
		$rules->object_type_should_be( 'empty' );
		$rules->object_id_should_be( 'empty' );
	}
}

GP::$administrator_permission = new GP_Administrator_Permission();
