<?php
class GP_Thing {
	var $table = null;
	var $field_names = array();
	static $i;
	
	function reload() {
		$this->set_fields( $this->get( $this->id ) );
	}
	
	function init( $db_object ) {
		foreach( $this->field_names as $field_name )
			$this->$field_name = null;
		$this->set_fields( $db_object );
	}
	
	function set_fields( $db_object ) {
		$db_object = $this->normalize_fields( (array)$db_object );
		foreach( $db_object as $key => $value ) {
			$this->$key = $value;
		}
	}
	
	function normalize_fields( $args ) {
		return $args;
	}
	
	function map( $results ) {
		$mapped = array();
		foreach( $results as $result ) {
			$mapped[] = $this->coerce( $result );
		}
		return $mapped;
	}
	
	/**
	 * Prepares for enetering the database an array with
	 * key-value pairs, preresenting a GP_Project object.
	 * 
	 */
	function prepare_fields_for_save( $args ) {
		$args = $this->normalize_fields( $args );
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
	
	function coerce( $thing ) {
		if ( !$thing || is_wp_error( $thing ) ) {
			return false;
		} else {
			$class = new ReflectionClass( get_class( $this ) );
			return $class->newInstance( $thing );
		}
	}

	function create( $args ) {
		global $gpdb;
		$res = $gpdb->insert( $this->table, $this->prepare_fields_for_save( $args ) );
		if ( $res === false ) return false;
		$class = new ReflectionClass( get_class( $this ) );
		$inserted = $class->newInstance( $args );
		$inserted->id = $gpdb->insert_id;
		$inserted->after_create();
		return $inserted;
	}

	function create_and_select( $args ) {
		$created = $this->create( $args );
		if ( !$created ) return false;
		$created->reload();
		return $created;
	}

	function get( $thing_or_id ) {
		global $gpdb;
		if ( is_object( $thing_or_id ) ) $thing_or_id = $thing_or_id->id;
		return $this->coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $gpdb->projects WHERE `id` = '%s'", $thing_or_id ) ) );
	}

	function save( $args = false ) {
		global $gpdb;
		if ( false === $args ) $args = get_object_vars( $this );
		if ( !is_array( $args ) ) $args = (array)$args;
		$update_res  = $gpdb->update( $this->table, $this->prepare_fields_for_save( $args ), array( 'id' => $this->id ) );
		$this->set_fields( $args );
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