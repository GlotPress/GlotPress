<?php

class GP_Validation_Rules {
	
	var $field_names = array();
	var $rules = array();
	
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
						$this->rules[] = array( 'field' => $field_name, 'rule' => $args[0], 'kind' => $kind, 'args' => array_slice( $args, 1 ) );
						return true;
					}
				}
			}
		}
		trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
	}
}