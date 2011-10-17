<?php
class GP_Thing {
	
	var $table = null;
	var $field_names = array();
	var $non_db_field_names = array();
	var $errors = array();
	var $validation_rules = null;
	var $per_page = 30;
	var $map_results = true;
	var $static = array();
	
	static $static_by_class = array();
	static $validation_rules_by_class = array();
	
	function __construct( $fields = array() ) {
		global $gpdb;
		$this->class = get_class( $this );
		$this->table = $gpdb->{$this->table_basename};
		foreach( $this->field_names as $field_name ) {
			$this->$field_name = null;
		}
		$this->set_fields( $fields );
		
		if ( isset( self::$validation_rules_by_class[$this->class] ) ) {
			$this->validation_rules = &self::$validation_rules_by_class[$this->class];
		} else {
			$this->validation_rules = new GP_Validation_Rules( array_merge( $this->field_names, $this->non_db_field_names ) );
			// we give the rules as a parameter here solely as a syntax sugar
			$this->restrict_fields( $this->validation_rules );
			self::$validation_rules_by_class[$this->class] = &$this->validation_rules;
		}
		if ( !$this->get_static( 'static-vars-are-set' ) ) {
			foreach( get_class_vars( $this->class ) as $name => $value ) {
				$this->set_static( $name, $value );
			}
			$this->set_static( 'static-vars-are-set', true );
		}
	}
	
	function get_static( $name, $default = null ) {
		return isset( self::$static_by_class[$this->class][$name] )? self::$static_by_class[$this->class][$name] : $default;
	}
	
	function has_static( $name ) {
		return isset( self::$static_by_class[$this->class][$name] );
	}
	
	function set_static( $name, $value ) {
		self::$static_by_class[$this->class][$name] = $value;
	}
	
	function __call( $name, $args ) {
		$suffix = '_no_map';
		if ( gp_endswith( $name, $suffix ) ) {
			$name = substr( $name, 0, strlen( $name ) - strlen( $suffix ) );
			$this->map_results = false;
			return call_user_func_array( array( &$this, $name ), $args );
			$this->map_results = true;
		}
		trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
	}
	
	// CRUD

	/**
	 * Retrieves all rows from this table
	 */
	function all( $order = null ) {
		return $this->many( $this->select_all_from_conditions_and_order( array(), $order ) );
	}
	
	/**
	 * Reloads the object data from the database, based on its id
	 */
	function reload() {
		$this->set_fields( $this->get( $this->id ) );
		return $this;
	}

	/**
	 * Retrieves a single row from this table
	 * 
	 * For parameters description see BPDB::prepare()
	 * @return mixed an object, containing the selected row or false on error
	 */
	function one() {
		global $gpdb;
		$args = func_get_args();
		return $this->coerce( $gpdb->get_row( $this->prepare( $args ) ) );
	}

	/**
	 * Retrieves a single value from this table
	 * 
	 * For parameters description see BPDB::prepare()
	 * @return scalar the result of the query or false on error
	 */
	function value() {
		global $gpdb;
		$args = func_get_args();
		$res = $gpdb->get_var( $this->prepare( $args ) );
		return is_null( $res )? false : $res;
	}

	function prepare( $args ) {
		global $gpdb;
		if ( 1 == count( $args ) ) {
			return $args[0];
		} else {
			$query = array_shift( $args );
			return $gpdb->prepare( $query, $args );
		}
	}

	/**
	 * Retrieves multiple rows from this table
	 * 
	 * For parameters description see BPDB::prepare()
	 * @return mixed an object, containing the selected row or false on error
	 */
	function many() {
		global $gpdb;
		$args = func_get_args();
		return $this->map( $gpdb->get_results( $this->prepare( $args ) ) );
	}

	function find_many( $conditions, $order = null ) {
		return $this->many( $this->select_all_from_conditions_and_order( $conditions, $order ) );
	}

	function find_one( $conditions, $order = null ) {
		return $this->one( $this->select_all_from_conditions_and_order( $conditions, $order ) );
	}

	function find( $conditions, $order = null ) {
		return $this->find_many( $conditions, $order );
	}
	
	function query() {
		global $gpdb;
		$args = func_get_args();
		return $gpdb->query( $this->prepare( $args ) );
	}

	/**
	 * Inserts a new row
	 * 
	 * @param $args array associative array with fields as keys and values as values
	 * @return mixed the object corresponding to the inserted row or false on error
	 */
	function create( $args ) {
		global $gpdb;
		$args = $this->prepare_fields_for_save( $args );
		$args = $this->prepare_fields_for_create( $args );
		$res = $gpdb->insert( $this->table, $args );
		if ( $res === false ) return false;
		$class = $this->class;
		$inserted = new $class( $args );
		$inserted->id = $gpdb->insert_id;
		$inserted->after_create();
		return $inserted;
	}
	
	/**
	 * Inserts a record and then selects it back based on the id
	 * 
	 * @param $args array see create()
	 * @return mixed the selected object or false on error
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
	function update( $data, $where = null ) {
		global $gpdb;
		if ( !$data ) return false;
		$where = is_null( $where )? array( 'id' => $this->id ) : $where;
		$fields_for_save = $this->prepare_fields_for_save( $data );
		if ( is_array( $fields_for_save ) && empty( $fields_for_save ) ) return true;
		return !is_null( $gpdb->update( $this->table, $fields_for_save, $where ) );
	}

	function get( $thing_or_id ) {
		global $gpdb;
		if ( !$thing_or_id ) return false;
		$id = is_object( $thing_or_id )? $thing_or_id->id : $thing_or_id;
		return $this->find_one( array( 'id' => $id ) );
	}

	function save( $args = null ) {
		if ( is_null( $args ) ) $args = get_object_vars( $this );
		if ( !is_array( $args ) ) $args = (array)$args;
		$args = $this->prepare_fields_for_save( $args );
		$update_res  = $this->update( $args );
		$this->set_fields( $args );
		if ( !$update_res ) return null;
		$update_res = $this->after_save();
		return $update_res;
	}
	
	function delete() {
		return $this->delete_all( array( 'id' => $this->id ) );
	}

	function delete_all( $where = null  ) {
		$query = "DELETE FROM $this->table";
		$conditions_sql = $this->sql_from_conditions( $where );
		if ( $conditions_sql ) $query .= " WHERE $conditions_sql";
		return $this->query( $query, $this->id );
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
	 * 
	 * @todo Include default type handling. For example dates 0000-00-00 should be set to null
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
		$args = (array)$args;
		$args = $this->normalize_fields( $args );
		unset( $args['id'] );
		foreach( $this->non_updatable_attributes as $attribute ) {
			unset( $args[$attribute] );
		}
		foreach( $args as $key => $value ) {
			if ( !in_array( $key, $this->field_names ) ) {
				unset( $args[$key] );
			}
		}
		
		if ( in_array( 'date_modified', $this->field_names ) ) {
			$args['date_modified'] = $this->now_in_mysql_format();
		}
				
		return $args;
	}
	
	function now_in_mysql_format() {
		$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		return $now->format( DATE_MYSQL );		
	}
	
	function prepare_fields_for_create( $args ) {
		if ( in_array( 'date_added', $this->field_names ) ) {
			$args['date_added'] = $this->now_in_mysql_format();
		}
		return $args;
	}
	
	function coerce( $thing ) {
		if ( !$thing || is_wp_error( $thing ) ) {
			return false;
		} else {
			$class = $this->class;
			return new $class( $thing );
		}
	}

	function map( $results ) {
		if ( isset( $this->map_results ) && !$this->map_results ) return $results;
		if ( !$results || !is_array( $results ) ) $results = array();
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
		if ( is_array( $php_value ) ) {
			return array_map( array( &$this, 'sql_condition_from_php_value' ), $php_value );
		}
		$operator = '=';
		if ( is_integer( $php_value ) || ctype_digit( $php_value) )
		 	$sql_value = $php_value;
		else
			$sql_value = "'".$gpdb->escape( $php_value )."'";
		if ( is_null( $php_value ) ) {
			$operator = 'IS';
			$sql_value = 'NULL';
		}
		return "$operator $sql_value";
	}
	
	function sql_from_conditions( $conditions ) {
		if ( is_string( $conditions ) ) {
			$conditions;
		} elseif ( is_array( $conditions ) ) {
			$conditions = array_map( array( &$this, 'sql_condition_from_php_value' ), $conditions );
			$string_conditions = array();
			foreach( $conditions as $field => $sql_condition ) {
				if ( is_array( $sql_condition ) )
					$string_conditions[] = '('. implode( ' OR ', array_map( lambda( '$cond', '"$field $cond"', compact('field') ), $sql_condition ) ) . ')';
				else
					$string_conditions[] = "$field $sql_condition";
			}
			$conditions = implode( ' AND ', $string_conditions );
		}
		return $this->apply_default_conditions( $conditions );
	}
	
	function sql_from_order( $order_by, $order_how = '' ) {
		if ( is_array( $order_by ) ) {
			$order_by = implode( ' ', $order_by );
			$order_how = '';
		}
		$order_by = trim( $order_by );
		if ( !$order_by ) return gp_member_get( $this, 'default_order' );		
		return 'ORDER BY ' . $order_by . ( $order_how? " $order_how" : '' );
	}
	
	function select_all_from_conditions_and_order( $conditions, $order = null ) {
		$query = "SELECT * FROM $this->table";
		$conditions_sql = $this->sql_from_conditions( $conditions );
		if ( $conditions_sql ) $query .= " WHERE $conditions_sql";
		$order_sql = $this->sql_from_order( $order );
		if ( $order_sql ) $query .= " $order_sql";
		return $query;
	}
	
	function restrict_fields( $thing ) {
		// Don't restrict any fields by default
	}
	
	function validate() {
		$verdict = $this->validation_rules->run( $this );
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
	
	function sql_limit_for_paging( $page, $per_page = null ) {
		$per_page = is_null( $per_page )? $this->per_page : $per_page;
		if ( 'no-limit' == $per_page || 'no-limit' == $page ) return '';
		$page = intval( $page )? intval( $page ) : 1;
		return sprintf( "LIMIT %d OFFSET %d", $this->per_page, ($page-1)*$this->per_page );
	}
	
	function found_rows() {
		global $gpdb;
		return $gpdb->get_var("SELECT FOUND_ROWS();");
	}
	
	function like_escape_printf( $s ) {
		return str_replace( '%', '%%', like_escape( $s ) );
	}
	
	function apply_default_conditions( $conditions_str ) {
		$conditions = array();
		if ( isset( $this->default_conditions ) )  $conditions[] = $this->default_conditions;
		if ( $conditions_str ) $conditions[] = $conditions_str;
		return implode( ' AND ', $conditions );
	}
}