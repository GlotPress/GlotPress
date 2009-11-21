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
	
	function by_translation_set_id( $translation_set_id ) {
		return $this->many( "SELECT * FROM $this->table WHERE object_type='translation-set' AND object_id = %d", $translation_set_id );
	}
}
GP::$permission = new GP_Permission();