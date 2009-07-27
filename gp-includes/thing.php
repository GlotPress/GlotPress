<?php
class GP_Thing {
	var $table = null;
	var $field_names = array();
	var $class = __CLASS__;
	static $i;
	
	function reload() {
		$this->set_values( $this->get( $this->id ) );
	}
	
	function _init( $db_object ) {
		foreach( $this->field_names as $field_name )
			$this->$field_name = null;
		$this->set_values( $db_object );
	}
	
	function set_values( $db_object ) {
		$db_object = $this->normalize_values( (array)$db_object );
		foreach( $db_object as $key => $value ) {
			$this->$key = $value;
		}
	}
	
	function normalize_values( $args ) {
		return $args;
	}
	
	function _map( $results ) {
		return array_map( create_function( '$r', 'return '.$this->class.'::coerce($r);' ), $results );
	}
	
	/**
	 * Prepares for enetering the database an array with
	 * key-value pairs, preresenting a GP_Project object.
	 * 
	 */
	function prepare_values_for_save( $args ) {
		$args = $this->normalize_values( $args );
		unset( $args['id'] );
		foreach ($this->non_updatable_attributes as $attribute ) {
			unset( $args[$attribute] );
		}
		foreach( $args as $key => $value ) {
			if ( !in_array( $key, $this->field_names ) ) {
				unset( $args[$key] );
			}
		}
		return $args;
	}
	
	function _coerce( $thing ) {
		if ( !$thing || is_wp_error( $thing ) ) {
			return false;
		} else {
			$class = new ReflectionClass( $this->class );
			return $class->newInstance( $thing );
		}
	}

	function _create( $args ) {
		global $gpdb;
		$res = $gpdb->insert( $this->table, $this->prepare_values_for_save( $args ) );
		if ( $res === false ) return false;
		$class = new ReflectionClass( $this->class );
		$inserted = $class->newInstance( $args );
		$inserted->id = $gpdb->insert_id;
		$inserted->after_create();
		return $inserted;
	}

	function _create_and_select( $args ) {
		$created = $this->_create( $args );
		if ( !$created ) return false;
		$created->reload();
		return $created;
	}

	function _get( $thing_or_id ) {
		global $gpdb;
		if ( is_object( $thing_or_id ) ) $thing_or_id = $thing_or_id->id;
		return $this->_coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE `id` = '%s'", $thing_or_id ) ) );
	}

	function save( $args = false ) {
		global $gpdb;
		if ( false === $args ) $args = get_object_vars( $this );
		if ( !is_array( $args ) ) $args = (array)$args;
		$update_res  = $gpdb->update( $this->table, $this->prepare_values_for_save( $args ), array( 'id' => $this->id ) );
		$this->set_values( $args );
		if ( is_null( $update_res ) ) return $update_res;
		$update_res = $this->after_save();
		return $update_res;
	}

	function after_create() {
		return true;
	}
	
	function after_save() {
		return true;
	}
}