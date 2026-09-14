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

	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	var $table_basename = 'gp_permissions';

	/**
	 * List of field names for a validator permission.
	 *
	 * @var array $field_names
	 */
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id' );

	/**
	 * List of non-database field names.
	 *
	 * @var array $non_db_field_names
	 */
	var $non_db_field_names = array( 'project_id', 'locale_slug', 'set_slug' );

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	var $non_updatable_attributes = array( 'id' );

	/**
	 * Type of the object.
	 *
	 * @var string $object_type
	 */
	public $object_type;

	/**
	 * ID of the project.
	 *
	 * @var int $project_id
	 */
	public $project_id;

	/**
	 * Slug of the locale.
	 *
	 * @var string $locale_slug
	 */
	public $locale_slug;

	/**
	 * Slug of the translation set.
	 *
	 * @var string $set_slug
	 */
	public $set_slug;

	/**
	 * User object.
	 *
	 * @var GP_User $user
	 */
	public $user;

	/**
	 * Project object.
	 *
	 * @var GP_Project $project
	 */
	public $project;

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

	/**
	 * Sets fields of the current GP_Thing object.
	 *
	 * @param array $fields Fields for a GP_Thing object.
	 */
	public function set_fields( $fields ) {
		parent::set_fields( $fields );
		if ( $this->object_id ) {
			list( $this->project_id, $this->locale_slug, $this->set_slug ) = $this->project_id_locale_slug_set_slug( $this->object_id );
		}
		$this->object_type        = 'project|locale|set-slug';
		$this->default_conditions = "object_type = '" . $this->object_type . "'";
	}

	/**
	 * Prepares fields before saving.
	 *
	 * @param array $args Fields to prepare.
	 * @return array Prepared fields.
	 */
	public function prepare_fields_for_save( $args ) {
		$args                = (array) $args;
		$args['object_type'] = $this->object_type;
		if ( gp_array_get( $args, 'project_id' ) && gp_array_get( $args, 'locale_slug' )
				&& gp_array_get( $args, 'set_slug' ) && ! gp_array_get( $args, 'object_id' ) ) {
			$args['object_id'] = $this->object_id( $args['project_id'], $args['locale_slug'], $args['set_slug'] );
		}
		$args = parent::prepare_fields_for_save( $args );
		return $args;
	}

	/**
	 * Splits the object_id into project_id, locale_slug and set_slug.
	 *
	 * @param string $object_id The object ID.
	 * @return array Array with project_id, locale_slug and set_slug.
	 */
	public function project_id_locale_slug_set_slug( $object_id ) {
		return explode( '|', $object_id );
	}

	/**
	 * Creates the object_id from project_id, locale_slug and set_slug.
	 *
	 * @param int    $project_id  The project ID.
	 * @param string $locale_slug The locale slug.
	 * @param string $set_slug    The set slug.
	 * @return string The object ID.
	 */
	public function object_id( $project_id, $locale_slug, $set_slug = 'default' ) {
		return implode( '|', array( $project_id, $locale_slug, $set_slug ) );
	}

	/**
	 * Gets permissions by project ID.
	 *
	 * @param int $project_id The project ID.
	 * @return array Array of GP_Validator_Permission objects.
	 */
	public function by_project_id( $project_id ) {
		$project_id = (int) $project_id;
		return $this->find_many( "object_id LIKE '$project_id|%'" );
	}
}
GP::$validator_permission = new GP_Validator_Permission();
