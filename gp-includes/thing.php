<?php
/**
 * Things: GP_Thing class
 *
 * @package GlotPress
 * @subpackage Things
 * @since 1.0.0
 */

/**
 * Core base class extended to register things.
 *
 * @since 1.0.0
 */
class GP_Thing {

	var $field_names        = array();
	var $non_db_field_names = array();
	var $int_fields         = array();
	var $validation_rules   = null;
	var $per_page           = 30;
	var $map_results        = true;
	var $static             = array();

	public $class;
	public $table_basename;
	public $id;
	public $non_updatable_attributes;
	public $default_conditions;
	public $table  = null;
	public $errors = array();

	static $static_by_class           = array();
	static $validation_rules_by_class = array();

	public function __construct( $fields = array() ) {
		global $wpdb;
		$this->class = get_class( $this );
		$this->table = $wpdb->{$this->table_basename};
		foreach ( $this->field_names as $field_name ) {
			$this->$field_name = null;
		}
		$this->set_fields( $fields );

		if ( isset( self::$validation_rules_by_class[ $this->class ] ) ) {
			$this->validation_rules = &self::$validation_rules_by_class[ $this->class ];
		} else {
			$this->validation_rules = new GP_Validation_Rules( array_merge( $this->field_names, $this->non_db_field_names ) );
			// we give the rules as a parameter here solely as a syntax sugar
			$this->restrict_fields( $this->validation_rules );
			self::$validation_rules_by_class[ $this->class ] = &$this->validation_rules;
		}
		if ( ! $this->get_static( 'static-vars-are-set' ) ) {
			foreach ( get_class_vars( $this->class ) as $name => $value ) {
				$this->set_static( $name, $value );
			}
			$this->set_static( 'static-vars-are-set', true );
		}
	}

	public function get_static( $name, $default = null ) {
		return isset( self::$static_by_class[ $this->class ][ $name ] ) ? self::$static_by_class[ $this->class ][ $name ] : $default;
	}

	public function has_static( $name ) {
		return isset( self::$static_by_class[ $this->class ][ $name ] );
	}

	public function set_static( $name, $value ) {
		self::$static_by_class[ $this->class ][ $name ] = $value;
	}

	// CRUD

	/**
	 * Retrieves all rows from this table
	 */
	public function all( $order = null ) {
		return $this->many( $this->select_all_from_conditions_and_order( array(), $order ) );
	}

	/**
	 * Reloads the object data from the database, based on its id
	 *
	 * @return GP_Thing Thing object.
	 */
	public function reload() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Verified table name.
		$fields = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table WHERE id = %d", $this->id ) );
		$this->set_fields( $fields );

		return $this;
	}

	/**
	 * Retrieves one row from the database.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::get_row()
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Query statement with optional sprintf()-like placeholders.
	 * @param mixed  ...$args Optional arguments to pass to the GP_Thing::prepare() function.
	 * @return GP_Thing|false Thing object on success, false on failure.
	 */
	public function one( $query, ...$args ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->coerce( $wpdb->get_row( $this->prepare( $query, ...$args ) ) );
	}

	/**
	 * Retrieves one variable from the database.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::get_var()
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Query statement with optional sprintf()-like placeholders.
	 * @param mixed  ...$args Optional arguments to pass to the GP_Thing::prepare() function.
	 * @return string|null Database query result (as string), or false on failure.
	 */
	public function value( $query, ...$args ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$res = $wpdb->get_var( $this->prepare( $query, ...$args ) );

		return null === $res ? false : $res;
	}

	/**
	 * Prepares a SQL query for safe execution. Uses sprintf()-like syntax.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Query statement with optional sprintf()-like placeholders.
	 * @param mixed  ...$args Optional arguments to pass to the GP_Thing::prepare() function.
	 * @return string Sanitized query string, if there is a query to prepare.
	 */
	public function prepare( $query, ...$args ) {
		global $wpdb;

		if ( ! $args ) {
			return $query;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->prepare( $query, ...$args );
	}


	/**
	 * Retrieves an entire result set from the database, mapped to GP_Thing.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::get_results()
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Query statement with optional sprintf()-like placeholders.
	 * @param mixed  ...$args Optional arguments to pass to the GP_Thing::prepare() function.
	 * @return GP_Thing[] A list of GP_Thing objects.
	 */
	public function many( $query, ...$args ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $this->map( $wpdb->get_results( $this->prepare( $query, ...$args ) ) );
	}

	/**
	 * Retrieves an entire result set from the database.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::get_results()
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Query statement with optional sprintf()-like placeholders.
	 * @param mixed  ...$args Optional arguments to pass to the GP_Thing::prepare() function.
	 * @return object[] Database query results.
	 */
	public function many_no_map( $query, ...$args ) {
		array_unshift( $args, $query );
		return $this->_no_map( 'many', $args );
	}

	/**
	 * [find_many description]
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $conditions
	 * @param string|array $order Optional.
	 * @return mixed
	 */
	public function find_many( $conditions, $order = null ) {
		return $this->many( $this->select_all_from_conditions_and_order( $conditions, $order ) );
	}

	/**
	 * [find_many_no_map description]
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $conditions
	 * @param string|array $order Optional.
	 * @return mixed
	 */
	public function find_many_no_map( $conditions, $order = null ) {
		return $this->_no_map( 'find_many', array( $conditions, $order ) );
	}

	/**
	 * [find_one description]
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $conditions
	 * @param string|array $order Optional.
	 * @return mixed
	 */
	public function find_one( $conditions, $order = null ) {
		return $this->one( $this->select_all_from_conditions_and_order( $conditions, $order ) );
	}

	/**
	 * [find description]
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $conditions
	 * @param string|array $order Optional.
	 * @return mixed
	 */
	public function find( $conditions, $order = null ) {
		return $this->find_many( $conditions, $order );
	}

	/**
	 * [find_no_map description]
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $conditions
	 * @param string|array $order Optional.
	 * @return mixed
	 */
	public function find_no_map( $conditions, $order = null ) {
		return $this->_no_map( 'find', array( $conditions, $order ) );
	}

	/**
	 * [_no_map description]
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Method name.
	 * @param mixed  $args Method-dependent arguments.
	 * @return mixed
	 */
	private function _no_map( $name, $args ) {
		$this->map_results = false;
		$result            = $this->$name( ...$args );
		$this->map_results = true;

		return $result;
	}

	/**
	 * [map_no_map description]
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $results The results, unmapped.
	 * @return mixed
	 */
	public function map_no_map( $results ) {
		return $this->_no_map( 'map', $results );
	}

	/**
	 * Maps database results to their GP_Thing presentations.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $results The results from the database.
	 * @return GP_Thing[]|object[] If enabled, a list of objects mapped to GP_Thing.
	 */
	public function map( $results ) {
		if ( isset( $this->map_results ) && ! $this->map_results ) {
			return $results;
		}

		if ( ! $results || ! is_array( $results ) ) {
			$results = array();
		}

		$mapped = array();
		foreach ( $results as $result ) {
			$mapped[] = $this->coerce( $result );
		}

		return $mapped;
	}

	/**
	 * Performs a database query.
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Added spread operator and require `$query` argument to be set.
	 *
	 * @see wpdb::query()
	 * @see wpdb::prepare()
	 *
	 * @param string $query   Database query.
	 * @param mixed  ...$args Optional arguments to pass to the prepare method.
	 * @return int|bool Number of rows affected/selected or false on error.
	 */
	public function query( $query, ...$args ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Custom prepare function.
		return $wpdb->query( $this->prepare( $query, ...$args ) );
	}

	/**
	 * Inserts a new row
	 *
	 * @param $args array associative array with fields as keys and values as values
	 * @return mixed the object corresponding to the inserted row or false on error
	 */
	public function create( $args ) {
		global $wpdb;
		$args          = $this->prepare_fields_for_save( $args );
		$args          = $this->prepare_fields_for_create( $args );
		$field_formats = $this->get_db_field_formats( $args );
		$res           = $wpdb->insert( $this->table, $args, $field_formats );
		if ( false === $res ) {
			return false;
		}
		$class        = $this->class;
		$inserted     = new $class( $args );
		$inserted->id = $wpdb->insert_id;
		$inserted->after_create();
		return $inserted;
	}

	/**
	 * Inserts a record and then selects it back based on the id
	 *
	 * @param $args array see create()
	 * @return mixed the selected object or false on error
	 */
	public function create_and_select( $args ) {
		$created = $this->create( $args );
		if ( ! $created ) {
			return false;
		}
		$created->reload();
		return $created;
	}

	/**
	 * Updates a single row
	 *
	 * @param $data array associative array with fields as keys and updated values as values
	 */
	public function update( $data, $where = null ) {
		global $wpdb;
		if ( ! $data ) {
			return false;
		}
		$where           = is_null( $where ) ? array( 'id' => $this->id ) : $where;
		$fields_for_save = $this->prepare_fields_for_save( $data );
		if ( is_array( $fields_for_save ) && empty( $fields_for_save ) ) {
			return true;
		}

		$field_formats = $this->get_db_field_formats( $fields_for_save );
		$where_formats = $this->get_db_field_formats( $where );

		return ! is_null( $wpdb->update( $this->table, $fields_for_save, $where, $field_formats, $where_formats ) );
	}

	/**
	 * Retrieves an existing thing.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Thing|int $thing_or_id ID of a thing or GP_Thing object.
	 * @return GP_Thing|false Thing object on success, false on failure.
	 */
	public function get( $thing_or_id ) {
		if ( ! $thing_or_id ) {
			return false;
		}

		$id = is_object( $thing_or_id ) ? $thing_or_id->id : $thing_or_id;
		return $this->find_one( array( 'id' => $id ) );
	}

	/**
	 * Saves an existing thing.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $args Values to update.
	 * @return bool|null Null and false on failure, true on success.
	 */
	public function save( $args = null ) {
		$thing_before = clone $this;

		if ( is_null( $args ) ) {
			$args = get_object_vars( $this );
		}

		if ( ! is_array( $args ) ) {
			$args = (array) $args;
		}

		$args = $this->prepare_fields_for_save( $args );

		$update_res = $this->update( $args );

		$this->set_fields( $args );

		if ( ! $update_res ) {
			return null;
		}

		$update_res = $this->after_save( $thing_before );

		return $update_res;
	}

	/**
	 * Deletes a single row
	 *
	 * @since 1.0.0
	 */
	public function delete() {
		return $this->delete_all( array( 'id' => $this->id ) );
	}

	/**
	 * Deletes all or multiple rows
	 *
	 * @since 1.0.0
	 *
	 * @param array $where An array of conditions to use to for a SQL "where" clause, if null, not used and all matching rows will be deleted.
	 */
	public function delete_all( $where = null ) {
		$query          = "DELETE FROM $this->table";
		$conditions_sql = $this->sql_from_conditions( $where );
		if ( $conditions_sql ) {
			$query .= " WHERE $conditions_sql";
		}
		$result = $this->query( $query );
		$this->after_delete();
		return $result;
	}

	/**
	 * Deletes multiple rows
	 *
	 * @since 2.0.0
	 *
	 * @param array $where An array of conditions to use to for a SQL "where" clause, if not passed, no rows will be deleted.
	 */
	public function delete_many( array $where ) {
		if ( empty( $where ) ) {
			return false;
		}

		return $this->delete_all( $where );
	}

	/**
	 * Sets fields of the current GP_Thing object.
	 *
	 * @param array $fields Fields for a GP_Thing object.
	 */
	public function set_fields( $fields ) {
		$fields = (array) $fields;
		$fields = $this->normalize_fields( $fields );
		foreach ( $fields as $key => $value ) {
			$this->$key = $value;
		}
	}

	/**
	 * Normalizes an array with key-value pairs representing
	 * a GP_Thing object.
	 *
	 * @todo Include default type handling. For example dates 0000-00-00 should be set to null
	 *
	 * @since 1.0.0
	 * @since 3.0.0 Normalizes int fields to be integers.
	 *
	 * @param array $args Arguments for a GP_Thing object.
	 * @return array Normalized arguments for a GP_Thing object.
	 */
	public function normalize_fields( $args ) {
		foreach ( $this->int_fields as $int_field ) {
			if ( isset( $args[ $int_field ] ) ) {
				$args[ $int_field ] = (int) $args[ $int_field ];
			}
		}

		return $args;
	}

	/**
	 * Prepares for enetering the database an array with
	 * key-value pairs, preresenting a GP_Thing object.
	 */
	public function prepare_fields_for_save( $args ) {
		$args = (array) $args;
		$args = $this->normalize_fields( $args );
		unset( $args['id'] );
		foreach ( $this->non_updatable_attributes as $attribute ) {
			unset( $args[ $attribute ] );
		}
		foreach ( $args as $key => $value ) {
			if ( ! in_array( $key, $this->field_names, true ) ) {
				unset( $args[ $key ] );
			}
		}

		if ( in_array( 'date_modified', $this->field_names, true ) ) {
			$args['date_modified'] = $this->now_in_mysql_format();
		}

		return $args;
	}

	public function now_in_mysql_format() {
		$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		return $now->format( DATE_MYSQL );
	}

	public function prepare_fields_for_create( $args ) {
		if ( in_array( 'date_added', $this->field_names, true ) ) {
			$args['date_added'] = $this->now_in_mysql_format();
		}
		return $args;
	}

	public function get_db_field_formats( $args ) {
		$formats = array_fill_keys( array_keys( $args ), '%s' );
		return array_merge( $formats, array_fill_keys( $this->int_fields, '%d' ) );
	}

	/**
	 * Coerces data to being a thing object.
	 *
	 * @since 1.0.0
	 *
	 * @param array|object $thing Data about the thing retrieved from the database.
	 * @return GP_Thing|false Thing object on success, false on failure.
	 */
	public function coerce( $thing ) {
		if ( ! $thing || is_wp_error( $thing ) ) {
			return false;
		} else {
			$class = $this->class;
			return new $class( $thing );
		}
	}

	// Triggers

	/**
	 * Is called after an object is created in the database.
	 *
	 * This is a placeholder function which should be implemented in the child classes.
	 *
	 * @return bool
	 */
	public function after_create() {
		return true;
	}

	/**
	 * Is called after an object is saved to the database.
	 *
	 * This is a placeholder function which should be implemented in the child classes.
	 *
	 * @param GP_Thing $thing_before Object before the update.
	 * @return bool
	 */
	public function after_save( $thing_before ) {
		return true;
	}

	/**
	 * Is called after an object is deleted from the database.
	 *
	 * This is a placeholder function which should be implemented in the child classes.
	 *
	 * @return bool
	 */
	public function after_delete() {
		return true;
	}

	/**
	 * Builds SQL conditions from a PHP value.
	 *
	 * Examples:
	 *   Input: `null`
	 *   Output: `IS NULL`
	 *
	 *   Input: `'foo'`
	 *   Output: `= 'foo'`
	 *
	 *   Input: `1` or `'1'`
	 *   Output: `= 1`
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $php_value The PHP value to convert to conditions.
	 * @return string SQL conditions.
	 */
	public function sql_condition_from_php_value( $php_value ) {
		if ( is_array( $php_value ) ) {
			return array_map( array( &$this, 'sql_condition_from_php_value' ), $php_value );
		}
		$operator = '=';
		if ( is_integer( $php_value ) || ctype_digit( $php_value ) ) {
			$sql_value = $php_value;
		} else {
			$sql_value = "'" . esc_sql( $php_value ) . "'";
		}
		if ( is_null( $php_value ) ) {
			$operator  = 'IS';
			$sql_value = 'NULL';
		}
		return "$operator $sql_value";
	}

	public function sql_from_conditions( $conditions ) {
		if ( is_string( $conditions ) ) {
			$conditions;
		} elseif ( is_array( $conditions ) ) {
			$conditions        = array_map( array( &$this, 'sql_condition_from_php_value' ), $conditions );
			$string_conditions = array();

			foreach ( $conditions as $field => $sql_condition ) {
				if ( is_array( $sql_condition ) ) {
					$string_conditions[] = '(' . implode(
						' OR ',
						array_map(
							function( $cond ) use ( $field ) {
								return "$field $cond";
							},
							$sql_condition
						)
					) . ')';
				} else {
					$string_conditions[] = "$field $sql_condition";
				}
			}

			$conditions = implode( ' AND ', $string_conditions );
		}
		return $this->apply_default_conditions( $conditions );
	}

	public function sql_from_order( $order_by, $order_how = '' ) {
		if ( is_array( $order_by ) ) {
			$order_by  = implode( ' ', $order_by );
			$order_how = '';
		}
		$order_by = trim( $order_by );
		if ( ! $order_by ) {
			return gp_member_get( $this, 'default_order' );
		}
		return 'ORDER BY ' . $order_by . ( $order_how ? " $order_how" : '' );
	}

	public function select_all_from_conditions_and_order( $conditions, $order = null ) {
		$query          = "SELECT * FROM $this->table";
		$conditions_sql = $this->sql_from_conditions( $conditions );
		if ( $conditions_sql ) {
			$query .= " WHERE $conditions_sql";
		}
		$order_sql = $this->sql_from_order( $order );
		if ( $order_sql ) {
			$query .= " $order_sql";
		}
		return $query;
	}

	/**
	 * Sets restriction rules for fields.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Validation_Rules $rules The validation rules instance.
	 */
	public function restrict_fields( $rules ) {
		// Don't restrict any fields by default.
	}

	public function validate() {
		$verdict      = $this->validation_rules->run( $this );
		$this->errors = $this->validation_rules->errors;
		return $verdict;
	}

	public function force_false_to_null( $value ) {
		return $value ? $value : null;
	}

	public function fields() {
		$result = array();
		foreach ( array_merge( $this->field_names, $this->non_db_field_names ) as $field_name ) {
			if ( isset( $this->$field_name ) ) {
				$result[ $field_name ] = $this->$field_name;
			}
		}
		return $result;
	}

	public function sql_limit_for_paging( $page, $per_page = null ) {
		$per_page = is_null( $per_page ) ? $this->per_page : $per_page;
		if ( 'no-limit' == $per_page || 'no-limit' == $page ) {
			return '';
		}
		$page = intval( $page ) ? intval( $page ) : 1;
		return sprintf( 'LIMIT %d OFFSET %d', $per_page, ( $page - 1 ) * $per_page );
	}

	public function found_rows() {
		global $wpdb;
		return $wpdb->get_var( 'SELECT FOUND_ROWS();' );
	}

	public function like_escape_printf( $s ) {
		global $wpdb;
		return str_replace( '%', '%%', $wpdb->esc_like( $s ) );
	}

	public function apply_default_conditions( $conditions_str ) {
		$conditions = array();
		if ( isset( $this->default_conditions ) ) {
			$conditions[] = $this->default_conditions;
		}
		if ( $conditions_str ) {
			$conditions[] = $conditions_str;
		}
		return implode( ' AND ', $conditions );
	}
}
