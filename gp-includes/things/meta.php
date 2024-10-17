<?php
/**
 * Things: GP_Meta class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 4.0.2
 */

/**
 * Core class used to implement meta.
 *
 * @since 4.0.2
 */
class GP_Meta extends GP_Thing {


	/**
	 * Name of the database table.
	 *
	 * @var string $table_basename
	 */
	public $table_basename = 'gp_meta';

	/**
	 * List of field names for a meta.
	 *
	 * @var array $field_names
	 */
	public $field_names = array(
		'id',
		'object_type',
		'object_id',
		'meta_key',
		'meta_value',
	);

	/**
	 * List of field names which have an integer value.
	 *
	 * @var array $int_fields
	 */
	public $int_fields = array(
		'id',
		'object_id',
	);

	/**
	 * List of field names which cannot be updated.
	 *
	 * @var array $non_updatable_attributes
	 */
	public $non_updatable_attributes = array( 'id' );

	/**
	 * ID of the meta.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Object type of the meta.
	 *
	 * @var string
	 */
	public $object_type;

	/**
	 * ID of the GlotPress object.
	 *
	 * @var int
	 */
	public $object_id;

	/**
	 * The key of the meta.
	 *
	 * @var string
	 */
	public $meta_key;

	/**
	 * The value of the meta.
	 *
	 * @var string
	 */
	public $meta_value;

	/**
	 * List of valid Object types.
	 *
	 * @var array
	 */
	public $object_types = array();


	/**
	 * Constructor.
	 *
	 * @param array $fields   The meta fields.
	 */
	public function __construct( $fields = array() ) {
		$this->setup_object_type();

		parent::__construct( $fields );
	}


	/**
	 * Sets up the object types captions.
	 */
	private function setup_object_type() {
		if ( ! empty( $this->object_types ) ) {
			return;
		}

		$this->object_types = array(
			'glossary_entry'  => _x( 'Glossary Entry', 'object-type', 'glotpress' ),
			'glossary'        => _x( 'Glossary', 'object-type', 'glotpress' ),
			'meta'            => _x( 'Meta', 'object-type', 'glotpress' ),
			'original'        => _x( 'Original', 'object-type', 'glotpress' ),
			'permission'      => _x( 'Permission', 'object-type', 'glotpress' ),
			'project'         => _x( 'Project', 'object-type', 'glotpress' ),
			'translation'     => _x( 'Translation', 'object-type', 'glotpress' ),
			'translation_set' => _x( 'Translation Set', 'object-type', 'glotpress' ),
			// Default.
			'gp_option'       => _x( 'Option', 'object-type', 'glotpress' ),
		);
	}


	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 4.0.2
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		$rules->object_type_should_not_be( 'null' );
		$rules->object_type_should_be( 'one_of', array_keys( $this->object_types ) );
		$rules->object_id_should_be( 'int' );
	}


	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Meta object.
	 *
	 * @since 4.0.2
	 *
	 * @param array $args Arguments for a GP_Meta object.
	 *
	 * @return array Normalized arguments for a GP_Meta object.
	 */
	public function normalize_fields( $args ) {

		// Map 'meta_id' from DB Schema to the standard GP_Thing 'id'.
		$id = array( 'id' => $args['meta_id'] );
		unset( $args['meta_id'] );
		$args = array_merge( $id, $args );

		$args = parent::normalize_fields( $args );

		return $args;
	}


	/**
	 * Get Meta object by 'object_type', 'object_id' and 'meta_key'.
	 *
	 * @since 4.0.2
	 *
	 * @param string $object_type   The object type.
	 * @param int    $object_id     The object ID.
	 * @param string $meta_key      The key of the meta.
	 *
	 * @return GP_Meta|false   The Meta object found, false if not found.
	 */
	public function by_object_type_object_id_and_meta_key( $object_type, $object_id, $meta_key ) {

		if ( ! $object_type ) {
			return false;
		}

		if ( ! is_numeric( $object_id ) || empty( $object_id ) ) {
			return false;
		}

		$meta_key = gp_sanitize_meta_key( $meta_key );

		$query = array(
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'meta_key'    => $meta_key,
		);

		return GP::$meta->find_one( $query );

	}


	/**
	 * Executes after creating a meta.
	 *
	 * @since 4.0.2
	 *
	 * @return bool
	 */
	public function after_create() {
		/**
		 * Fires after a new meta is created.
		 *
		 * @since 4.0.2
		 *
		 * @param GP_Meta $this   The meta that was created.
		 */
		do_action( 'gp_meta_created', $this );

		return true;
	}


	/**
	 * Executes after saving a meta.
	 *
	 * @since 4.0.2
	 *
	 * @param GP_Meta $meta_before   Meta before the update.
	 * @return bool
	 */
	public function after_save( $meta_before ) {
		/**
		 * Fires after a meta is saved.
		 *
		 * @since 4.0.2
		 *
		 * @param GP_Meta $this          Meta following the update.
		 * @param GP_Meta $meta_before   Meta before the update.
		 */
		do_action( 'gp_meta_saved', $this, $meta_before );

		return true;
	}


	/**
	 * Executes after deleting a meta.
	 *
	 * @since 4.0.2
	 *
	 * @return bool
	 */
	public function after_delete() {
		/**
		 * Fires after a meta is deleted.
		 *
		 * @since 4.0.2
		 *
		 * @param GP_Meta $this   The meta that was deleted.
		 */
		do_action( 'gp_meta_deleted', $this );

		return true;
	}


}

GP::$meta = new GP_Meta();
