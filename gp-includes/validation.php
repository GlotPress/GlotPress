<?php

class GP_Validation_Rules {

	var $rules = array();
	var $errors = array();

	static $positive_suffices = array(
		'should_be', 'should', 'can', 'can_be',
	);
	static $negative_suffices = array(
		'should_not_be', 'should_not', 'cant', 'cant_be',
	);

	function __construct( $field_names ) {
		$this->field_names = $field_names;
	}

	function __call( $name, $args ) {
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

	function run( $thing ) {
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

	function run_on_single_field( $field, $value ) {
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
					$this->errors[] = $this->construct_error_message( $rule, $value );
					$verdict = false;
				}
			} else {
				if ( is_null( $callback['negative'] ) ) {
					if ( call_user_func_array( $callback['positive'], $args ) ) {
						$this->errors[] = $this->construct_error_message( $rule, $value );
						$verdict = false;
					}
				} else if ( !call_user_func_array( $callback['negative'], $args ) ) {
					$this->errors[] = $this->construct_error_message( $rule, $value );
					$verdict = false;
				}
			}
		}
		return $verdict;
	}

	function construct_error_message( $rule, $value ) {
		$type_field = 'field';
		$name_field = $rule['field'];
		$name_rule  = str_replace( '_', ' ', $rule['rule'] );

		if( strpos( $name_field, 'translation_' ) === 0 ) {
			$type_field = 'textarea';
			$name_field = 'Translation ' . ( intval( substr( $name_field, 12 ) ) + 1 );
		}

		if ( 'positive' == $rule['kind'] )
			return sprintf( __( 'The %s <strong>%s</strong> is invalid and should be %s!', 'glotpress' ), $type_field, $name_field, $name_rule );
		else //if ( 'negative' == $rule['kind'] )
			return sprintf( __( 'The %s <strong>%s</strong> is invalid and should not be %s!', 'glotpress' ), $type_field, $name_field, $name_rule );
	}
}

class GP_Validators {
	static $callbacks = array();

	static function register( $key, $callback, $negative_callback = null ) {
		// TODO: add data for easier generation of error messages
		self::$callbacks[$key] = array( 'positive' => $callback, 'negative' => $negative_callback );
	}

	static function unregister( $key ) {
		unset( self::$callbacks[$key] );
	}

	static function get( $key ) {
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

