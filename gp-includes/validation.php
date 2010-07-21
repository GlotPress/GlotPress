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
				trigger_error( __('Non-existent validator: ' . $rule['rule'] ) );
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
		// TODO: better error messages, should include info from callback
		return sprintf( __('The field <strong>%s</strong> has invalid value!'), $rule['field'], $value );
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

GP_Validators::register( 'empty', lambda( '$value', 'empty($value)' ) );
GP_Validators::register( 'positive_int', lambda( '$value', '((int)$value > 0)' ) );
GP_Validators::register( 'int', lambda( '$value', '(bool)preg_match("/^-?\d+$/", $value)' ) );
GP_Validators::register( 'null', lambda( '$value', 'is_null($value)' ) );
GP_Validators::register( 'between', lambda( '$value, $start, $end', '$value >= $start && $value <= $end' ) );
GP_Validators::register( 'between_exclusive', lambda( '$value, $start, $end', '$value > $start && $value < $end' ) );

