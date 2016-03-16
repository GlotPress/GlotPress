<?php
/**
 * Things: GP_Validator_Permission class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core class used to implement the validator permissions.
 *
 * @since 1.0.0
 */
class GP_Validator_Permission extends GP_Permission {

	var $table_basename = 'gp_permissions';
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id' );
	var $non_db_field_names = array( 'project_id', 'locale_slug', 'set_slug' );
	var $non_updatable_attributes = array( 'id', );

	public $object_type;
	public $project_id;
	public $locale_slug;
	public $set_slug;

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->project_id_should_not_be( 'empty' );
		$rules->locale_slug_should_not_be( 'empty' );
		$rules->user_id_should_not_be( 'empty' );
		$rules->action_should_not_be( 'empty' );
		$rules->set_slug_should_not_be( 'empty' );
	}

	public function set_fields( $db_object ) {
		parent::set_fields( $db_object );
		if ( $this->object_id ) {
			list( $this->project_id, $this->locale_slug, $this->set_slug ) = $this->project_id_locale_slug_set_slug( $this->object_id );
		}
		$this->object_type = 'project|locale|set-slug';
		$this->default_conditions = "object_type = '".$this->object_type."'";
	}

	public function prepare_fields_for_save( $args ) {
		$args = (array)$args;
		$args['object_type'] = $this->object_type;
		if ( gp_array_get( $args, 'project_id' ) && gp_array_get( $args, 'locale_slug' )
		 		&& gp_array_get( $args, 'set_slug' ) && !gp_array_get( $args, 'object_id' ) ) {
			$args['object_id'] = $this->object_id( $args['project_id'], $args['locale_slug'], $args['set_slug'] );
		}
		$args = parent::prepare_fields_for_save( $args );
		return $args;
	}

	public function project_id_locale_slug_set_slug( $object_id ) {
		return explode( '|', $object_id );
	}

	public function object_id( $project_id, $locale_slug, $set_slug = 'default' ) {
		return implode( '|', array( $project_id, $locale_slug, $set_slug ) );
	}

	public function by_project_id( $project_id ) {
		$project_id = (int)$project_id;
		return $this->find_many( "object_id LIKE '$project_id|%'" );
	}
}
GP::$validator_permission = new GP_Validator_Permission();
