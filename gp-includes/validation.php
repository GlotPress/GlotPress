<?php
/**
 * Translation Validation API
 *
 * @package GlotPress
 * @since 1.0.0
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Core class to handle validation of translations.
 *
 * Uses magic methods in the format of [field]_[rule].
 *
 * The below is a list of all magic methods called.
 * Note that once a method has been defined from one file it will not be redefine in subsequent file sections.
 *
 * From gp_includes/things/administrative-permissions.php:
 *
 *     @method bool user_id_should_not_be( string $name, array $args = null )
 *     @method bool action_should_not_be( string $name, array $args = null )
 *     @method bool object_type_should_be( string $name, array $args = null )
 *     @method bool object_id_should_be( string $name, array $args = null )
 *
 * From gp_includes/things/glossary-entry.php:
 *
 *     @method bool term_should_not_be( string $name, array $args = null )
 *     @method bool part_of_speech_should_not_be( string $name, array $args = null )
 *     @method bool glossary_id_should_be( string $name, array $args = null )
 *     @method bool last_edited_by_should_be( string $name, array $args = null )
 *
 * From gp_includes/things/original.php:
 *
 *     @method bool singular_should_not_be( string $name, array $args = null )
 *     @method bool status_should_not_be( string $name, array $args = null )
 *     @method bool project_id_should_be( string $name, array $args = null )
 *     @method bool priority_should_be( string $name, array $args = null )
 *
 * From gp_includes/things/translation.php:
 *
 *     @method bool translation_0_should_not_be( string $name, array $args = null )
 *     @method bool original_id_should_be( string $name, array $args = null )
 *     @method bool translation_set_id_should_be( string $name, array $args = null )
 *     @method bool user_id_should_be( string $name, array $args = null )
 *     @method bool user_id_last_modified_should_not_be( string $name, array $args = null )
 *
 * From gp_includes/things/glossary.php:
 *
 *     @method bool translation_set_id_should_not_be( string $name, array $args = null )
 *
 * From gp_includes/things/project.php:
 *
 *     @method bool name_should_not_be( string $name, array $args = null )
 *     @method bool slug_should_not_be( string $name, array $args = null )
 *
 * From gp_includes/things/translation-set.php:
 *
 *     @method bool locale_should_not_be( string $name, array $args = null )
 *     @method bool project_id_should_not_be( string $name, array $args = null )
 *
 * From gp_includes/things/validator-permission.php:
 *
 *     @method bool locale_slug_should_not_be( string $name, array $args = null )
 *     @method bool user_id_should_not_be( string $name, array $args = null )
 *     @method bool action_should_not_be( string $name, array $args = null )
 *     @method bool set_slug_should_not_be( string $name, array $args = null )
 */
class GP_Validation_Rules {

	/**
	 * An array of validation rules.
	 *
	 * Each rule is an associative array with the following keys:
	 * - 'field': The name of the field to validate.
	 * - 'rule': The name of the validation rule to apply.
	 * - 'kind': The kind of validation ('positive' or 'negative').
	 * - 'args': An array of additional arguments to pass to the validation callback.
	 *
	 * @var array $rules
	 */
	var $rules = array();

	/**
	 * An array of validation errors.
	 * Each error is a string describing the validation failure.
	 *
	 * @var array $errors
	 */
	public $errors = array();

	/**
	 * An array of field names that can be validated.
	 *
	 * @var array $field_names
	 */
	public $field_names;

	/**
	 * An array of suffices for positive validation methods.
	 *
	 * @var array $positive_suffices
	 */
	static $positive_suffices = array(
		'should_be',
		'should',
		'can',
		'can_be',
	);

	/**
	 * An array of suffices for negative validation methods.
	 *
	 * @var array $negative_suffices
	 */
	static $negative_suffices = array(
		'should_not_be',
		'should_not',
		'cant',
		'cant_be',
	);

	/**
	 * Constructor.
	 *
	 * @param array $field_names An array of field names that can be validated.
	 */
	public function __construct( $field_names ) {
		$this->field_names = $field_names;
	}

	/**
	 * Magic method to handle calls to undefined methods.
	 *
	 * This method checks if the called method matches the pattern of [field]_[rule] for either positive or negative validation.
	 * If a match is found, it adds the corresponding validation rule to the $rules array.
	 * If no match is found, it triggers a user error for an undefined method call.
	 *
	 * @param string $name The name of the called method.
	 * @param array  $args The arguments passed to the called method.
	 * @throws BadMethodCallException If the called method does not match any defined validation pattern.
	 * @return bool True if a validation rule was added, otherwise triggers an error.
	 */
	public function __call( $name, $args ) {
		foreach ( array( 'positive', 'negative' ) as $kind ) {
			$suffices = "{$kind}_suffices";
			foreach ( self::$$suffices as $suffix ) {
				foreach ( $this->field_names as $field_name ) {
					if ( "{$field_name}_{$suffix}" == $name ) {
						$this->rules[ $field_name ][] = array(
							'field' => $field_name,
							'rule'  => $args[0],
							'kind'  => $kind,
							'args'  => array_slice( $args, 1 ),
						);
						return true;
					}
				}
			}
		}
		throw new BadMethodCallException(
			sprintf(
				/* translators: %s: Method name. */
				esc_html__( 'Call to undefined method: %s.', 'glotpress' ),
				sprintf(
					'%1$s::%2$s()',
					esc_html( get_class( $this ) ),
					esc_html( $name )
				)
			)
		);
	}

	/**
	 * Runs the validation rules on a given object.
	 *
	 * This method iterates through the defined validation rules and applies them to the corresponding fields of the provided object.
	 * If a field is missing from the object, it will be skipped.
	 * The method returns true if all validations pass, and false if any validation fails. In case of failure, the $errors array will contain the error messages.
	 *
	 * @param object $thing The object to validate.
	 * @return bool True if all validations pass, false otherwise.
	 */
	public function run( $thing ) {
		$this->errors = array();
		$verdict      = true;
		foreach ( $this->field_names as $field_name ) {
			// Do not try to validate missing fields.
			if ( ! gp_object_has_var( $thing, $field_name ) ) {
				continue;
			}
			$value         = $thing->$field_name;
			$field_verdict = $this->run_on_single_field( $field_name, $value );
			$verdict       = $verdict && $field_verdict;
		}
		return $verdict;
	}

	/**
	 * Runs the validation rules for a single field.
	 *
	 * This method applies all validation rules defined for a specific field to the provided value.
	 * It retrieves the corresponding validation callbacks and executes them with the specified arguments.
	 * If any validation fails, it adds an error message to the $errors array and returns false. If all validations pass, it returns true.
	 *
	 * @param string $field The name of the field to validate.
	 * @param mixed  $value The value of the field to validate.
	 * @return bool True if all validations for the field pass, false otherwise.
	 */
	public function run_on_single_field( $field, $value ) {
		if ( ! isset( $this->rules[ $field ] ) || ! is_array( $this->rules[ $field ] ) ) {
			// No rules means always valid.
			return true;
		}
		$verdict = true;

		foreach ( $this->rules[ $field ] as $rule ) {
			$callback = GP_Validators::get( $rule['rule'] );
			if ( is_null( $callback ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				trigger_error(
					sprintf(
						/* translators: %s: Rule. */
						__( 'Non-existent validator: %s', 'glotpress' ),
						esc_html( $rule['rule'] )
					),
					WP_DEBUG ? E_USER_WARNING : E_USER_NOTICE
				);
				continue;
			}
			$args = $rule['args'];
			array_unshift( $args, $value );
			if ( 'positive' === $rule['kind'] ) {
				if ( ! $callback['positive']( ...$args ) ) {
					$this->errors[] = $this->construct_error_message( $rule );
					$verdict        = false;
				}
			} elseif ( null === $callback['negative'] ) {
				if ( $callback['positive']( ...$args ) ) {
					$this->errors[] = $this->construct_error_message( $rule );
					$verdict        = false;
				}
			} elseif ( ! $callback['negative']( ...$args ) ) {
				$this->errors[] = $this->construct_error_message( $rule );
				$verdict        = false;
			}
		}
		return $verdict;
	}

	/**
	 * Constructs an error message for a failed validation rule.
	 *
	 * This method generates a user-friendly error message based on the provided validation rule.
	 * It identifies the type of field (e.g., input or textarea) and formats the message accordingly, including the name of the field and the validation rule that failed.
	 *
	 * @param array $rule The validation rule that failed, containing 'field', 'rule', and 'kind' keys.
	 * @return string The constructed error message.
	 */
	public function construct_error_message( $rule ) {
		$type_field = 'field';
		$name_field = $rule['field'];
		$name_rule  = str_replace( '_', ' ', $rule['rule'] );

		if ( 1 === preg_match( '/translation_[0-9]/', $name_field ) ) {
			$type_field = 'textarea';
			$name_field = 'Translation ' . ( intval( substr( $name_field, 12 ) ) + 1 );
		}

		if ( 'positive' == $rule['kind'] ) {
			/* translators: 1: type of a validation field, 2: name of a validation field, 3: validation rule */
			return sprintf( __( 'The %1$s %2$s is invalid and should be %3$s!', 'glotpress' ), $type_field, '<strong>' . $name_field . '</strong>', $name_rule );
		} else { // if ( 'negative' == $rule['kind'] )
			/* translators: 1: type of a validation field, 2: name of a validation field, 3: validation rule */
			return sprintf( __( 'The %1$s %2$s is invalid and should not be %3$s!', 'glotpress' ), $type_field, '<strong>' . $name_field . '</strong>', $name_rule );
		}
	}
}

/**
 * Core class to handle validation callbacks.
 *
 * Each callback is an associative array with 'positive' and 'negative' keys, containing the respective callback functions.
 */
class GP_Validators {

	/**
	 * An array of registered validation callbacks.
	 *
	 * @var array $callbacks
	 */
	static $callbacks = array();

	/**
	 * Registers a validation callback for a specific key.
	 *
	 * @param string        $key               The key to identify the validation rule.
	 * @param callable      $callback          The callback function for positive validation. It should return true if the validation passes, false otherwise.
	 * @param callable|null $negative_callback Optional. The callback function for negative validation. It should return true if the validation fails, false otherwise. If not provided, the positive callback will be used for negative validation as well.
	 */
	public static function register( $key, $callback, $negative_callback = null ) {
		// TODO: add data for easier generation of error messages.
		self::$callbacks[ $key ] = array(
			'positive' => $callback,
			'negative' => $negative_callback,
		);
	}

	/**
	 * Unregisters a validation callback for a specific key.
	 *
	 * @param string $key The key to identify the validation rule to be unregistered.
	 */
	public static function unregister( $key ) {
		unset( self::$callbacks[ $key ] );
	}

	/**
	 * Retrieves the validation callback for a specific key.
	 *
	 * @param string $key The key to identify the validation rule.
	 * @return array|null An associative array with 'positive' and 'negative' keys containing the respective callback functions, or null if the key is not registered.
	 */
	public static function get( $key ) {
		return gp_array_get( self::$callbacks, $key, null );
	}
}

GP_Validators::register( 'empty', 'gp_is_empty' );
GP_Validators::register( 'empty_string', 'gp_is_empty_string' );
GP_Validators::register( 'positive_int', 'gp_is_positive_int' );
GP_Validators::register( 'int', 'gp_is_int' );
GP_Validators::register( 'null', 'gp_is_null' );
GP_Validators::register( 'between', 'gp_is_between' );
GP_Validators::register( 'between_exclusive', 'gp_is_between_exclusive' );
GP_Validators::register( 'one_of', 'gp_is_one_of' );
GP_Validators::register( 'consisting_only_of_ASCII_characters', 'gp_is_ascii_string' );
GP_Validators::register( 'starting_and_ending_with_a_word_character', 'gp_is_starting_and_ending_with_a_word_character' );
