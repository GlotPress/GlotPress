<?php
/**
 * Translation Validation API
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Core class to handle validation of translations.
 *
 * Uses magic methods in the format of [field]_[rule].
 *
 * The below is a list of all magic methods called to ensure Scrutinizer recognizes them.
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

	var $rules = array();

	public $errors = array();
	public $field_names;

	static $positive_suffices = array(
		'should_be', 'should', 'can', 'can_be',
	);
	static $negative_suffices = array(
		'should_not_be', 'should_not', 'cant', 'cant_be',
	);

	public function __construct( $field_names ) {
		$this->field_names = $field_names;
	}

	public function __call( $name, $args ) {
		foreach( array( 'positive', 'negative' ) as $kind ) {
			$suffices = "{$kind}_suffices";
			foreach( self::$$suffices as $suffix ) {
				foreach( $this->field_names as $field_name ) {
					if ( $name == "{$field_name}_{$suffix}" ) {
						$this->rules[$field_name][] = array( 'field' => $field_name, 'rule' => $args[0], 'kind' => $kind, 'args' => array_slice( $args, 1 ) );
						return true;
					}
				}
			}
		}
		trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
	}

	public function run( $thing ) {
		$this->errors = array();
		$verdict = true;
		foreach( $this->field_names as $field_name ) {
			// do not try to validate missing fields
			if ( !gp_object_has_var( $thing, $field_name ) ) continue;
			$value = $thing->$field_name;
			$field_verdict = $this->run_on_single_field( $field_name, $value );
			$verdict = $verdict && $field_verdict;
		}
		return $verdict;
	}

	public function run_on_single_field( $field, $value ) {
		if ( !isset( $this->rules[$field] ) || !is_array( $this->rules[$field] ) ) {
			// no rules means always valid
			return true;
		}
		$verdict = true;

		foreach( $this->rules[$field] as $rule ) {
			$callback = GP_Validators::get( $rule['rule'] );
			if ( is_null( $callback ) ) {
				trigger_error( sprintf( __( 'Non-existent validator: %s', 'glotpress' ), $rule['rule'] ) );
				continue;
			}
			$args = $rule['args'];
			array_unshift( $args, $value );
			if ( 'positive' == $rule['kind'] ) {
				if ( !call_user_func_array( $callback['positive'], $args ) ) {
					$this->errors[] = $this->construct_error_message( $rule );
					$verdict = false;
				}
			} else {
				if ( is_null( $callback['negative'] ) ) {
					if ( call_user_func_array( $callback['positive'], $args ) ) {
						$this->errors[] = $this->construct_error_message( $rule );
						$verdict = false;
					}
				} else if ( !call_user_func_array( $callback['negative'], $args ) ) {
					$this->errors[] = $this->construct_error_message( $rule );
					$verdict = false;
				}
			}
		}
		return $verdict;
	}

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
		} else { //if ( 'negative' == $rule['kind'] )
			/* translators: 1: type of a validation field, 2: name of a validation field, 3: validation rule */
			return sprintf( __( 'The %1$s %2$s is invalid and should not be %3$s!', 'glotpress' ), $type_field, '<strong>' . $name_field . '</strong>', $name_rule );
		}
	}
}

class GP_Validators {
	static $callbacks = array();

	static public function register( $key, $callback, $negative_callback = null ) {
		// TODO: add data for easier generation of error messages
		self::$callbacks[$key] = array( 'positive' => $callback, 'negative' => $negative_callback );
	}

	static public function unregister( $key ) {
		unset( self::$callbacks[$key] );
	}

	static public function get( $key ) {
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

