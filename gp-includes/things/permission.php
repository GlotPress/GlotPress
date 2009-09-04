<?php
class GP_Permission extends GP_Thing {

	var $table_basename = 'permissions';
	var $field_names = array( 'id', 'user_id', 'action', 'object_type', 'object_id', );
	var $non_updatable_attributes = array( 'id', );

	
	function normalize_fields( $args ) {
		$args = (array)$args;
		foreach( $this->field_names as $field_name ) {
			if ( isset( $args[$field_name] ) ) {
				$args[$field_name] = $this->force_false_to_null( $args[$field_name] );
			}
		}
		return $args;
	}

	/*
	function find( $args ) {
		global $gpdb;
		if ( !isset( $args['user_id'] ) ) {
			$args['user_id'] = is_object( $args['user'] )? $args['user']->id : $args['user'];
		}
		
		// TODO: load and cache all permissions of this user				
		unset( $args['user'] );
		$args_values_without_nulls = array_filter( array_values( $args ), create_function( '$v', 'return !is_null($v); ') );
		$placeholder = create_function('$k', '$a = '.var_export($args, true).'; return is_null($a[$k])? "$k IS NULL" : "$k = %s";');
		$where = implode(' AND ', array_map( $placeholder, array_keys( $args ) ) );
		$query = $gpdb->prepare( "
		    SELECT * FROM $gpdb->permissions
		    WHERE $where", array_values( $args_values_without_nulls ) );
		return $gpdb->get_row( $query );
	}
	*/
}
GP::$permission = new GP_Permission();