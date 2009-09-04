<?php
class GP_Thing {
	
	var $table = null;
	var $field_names = array();
	var $errors = array();
	var $validation_rules = null;
	
	function __construct( $fields = array() ) {
		global $gpdb;
		$this->table = $gpdb->{$this->table_basename};
		foreach( $this->field_names as $field_name ) {
			$this->$field_name = null;
		}
		$this->set_fields( $fields );
		$this->validation_rules = new GP_Validation_Rules( $this );
		// we give the rules as a parameter here solely as a syntax sugar
		$this->restrict_fields( $this->validation_rules );
	}
	
	// CRUD

	function all() {
		return $this->many( "SELECT * FROM $this->table" );
	}
	
	/**
	 * Reloads the object data from the database, based on its id
	 */
	function reload() {
		$this->set_fields( $this->get( $this->id ) );
	}

	/**
	 * Retrieves single row from this table
	 * 
	 * For parameters description see BPDB::prepare()
	 * @return mixed an object, containing the selected row or false on error
	 */
	function one() {
		global $gpdb;
		$args = func_get_args();
		return $this->coerce( $gpdb->get_row( call_user_func_array( array($gpdb, 'prepare'), $args ) ) );
	}

	function many() {
		global $gpdb;
		$args = func_get_args();
		return $this->map( $gpdb->get_results( call_user_func_array( array($gpdb, 'prepare'), $args ) ) );
	}
	
	function find_many( $conditions ) {
		return $this->many( $this->sql_from_conditions( $conditions ) );
	}

	function find_one( $conditions ) {
		return $this->one( $this->sql_from_conditions( $conditions ) );
	}

	function find( $conditions ) {
		return $this->find_many( $conditions );
	}
	
	function query() {
		global $gpdb;
		$args = func_get_args();
		return $gpdb->query( call_user_func_array( array($gpdb, 'prepare'), $args ) );
	}

	/**
	 * Inserts a new row
	 * 
	 * @param $args array associative array with fields as keys and values as values
	 * @return mixed the object corresponding to the inserted row or false on error
	 */
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
	
	/**
	 * Inserts a record and then selects it back based on the id
	 * 
	 * @param $args array see create()
	 * @param mixed the selected object or false on error
	 */
	function create_and_select( $args ) {
		$created = $this->create( $args );
		if ( !$created ) return false;
		$created->reload();
		return $created;
	}
	
	/**
	 * Updates a single row
	 * 
	 * @param $data array associative array with fields as keys and updated values as values
	 */
	function update( $data ) {
		global $gpdb;
		$args = func_get_args();
		return $gpdb->update( $this->table, $data, array( 'id' => $this->id ) );
	}

	function get( $thing_or_id ) {
		global $gpdb;
		if ( is_object( $thing_or_id ) ) $thing_or_id = $thing_or_id->id;
		return $this->coerce( $gpdb->get_row( $gpdb->prepare( "SELECT * FROM $this->table WHERE `id` = '%s'", $thing_or_id ) ) );
	}

	function save( $args = null ) {
		global $gpdb;
		if ( is_null( $args ) ) $args = get_object_vars( $this );
		if ( !is_array( $args ) ) $args = (array)$args;
		$args = $this->prepare_fields_for_save( $args );
		$update_res  = $gpdb->update( $this->table, $args, array( 'id' => $this->id ) );
		$this->set_fields( $args );
		if ( is_null( $update_res ) ) return null;
		$update_res = $this->after_save();
		return $update_res;
	}

	function delete() {
		return $this->query( "DELETE FROM $this->table WHERE id = %d", $this->id );
	}

	// Fields handling
	
	function set_fields( $db_object ) {
		$db_object = $this->normalize_fields( $db_object );
		foreach( $db_object as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Thing object.
	 */
	function normalize_fields( $args ) {
		return $args;
	}
			
	/**
	 * Prepares for enetering the database an array with
	 * key-value pairs, preresenting a GP_Thing object.
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

	function map( $results ) {
		$mapped = array();
		foreach( $results as $result ) {
			$mapped[] = $this->coerce( $result );
		}
		return $mapped;
	}

	// Triggers
	
	function after_create() {
		return true;
	}
	
	function after_save() {
		return true;
	}
	
	function sql_condition_from_php_value( $php_value ) {
		global $gpdb;
		$operator = '=';
		$sql_value = "'".$gpdb->escape( $php_value )."'";
		if ( is_null( $php_value ) ) {
			$operator = 'IS';
			$sql_value = 'NULL';
		}
		return "$operator $sql_value";
	}
	
	function sql_from_conditions( $conditions ) {
		$conditions = array_map( array( &$this, 'sql_condition_from_php_value' ), $conditions );
		$string_conditions = array();
		foreach( $conditions as $field => $sql_condition ) {
			$string_conditions[] = "$field $sql_condition";
		}
		return "SELECT * FROM $this->table WHERE " . implode( ' AND ', $string_conditions );
	}
	
	function restrict_fields( $thing ) {
		// Don't restrict any fields by default
	}
	
	function validate() {
		$verdict = $this->validation_rules->run();
		$this->errors = $this->validation_rules->errors;
		return $verdict;
	}
	
	function force_false_to_null( $value ) {
		return $value? $value : null;
	}
	
	function fields() {
		$result = array();
		foreach( $this->field_names as $field_name ) {
			if ( isset( $this->$field_name ) ) {
				$result[$field_name] = $this->$field_name;
			}
		}
		return $result;
	}
}