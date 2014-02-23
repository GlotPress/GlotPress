<?php
class GP_Glossary extends GP_Thing {

	var $table_basename           = 'glossaries';
	var $field_names              = array( 'id', 'translation_set_id', 'description' );
	var $non_db_field_names       = array( 'translation_set' );

	var $non_updatable_attributes = array( 'id' );

	function restrict_fields( $glossary ) {
		$glossary->translation_set_id_should_not_be('empty');
	}

	function by_set_id( $set_id ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE translation_set_id = %d LIMIT 1", $set_id );
	}

	/**
	 * Copies glossary items from a glossary to the current one
	 *
	 * This function doesn't merge then, just copies unconditionally. If a translation already exists, it will be duplicated.
	 */
	function copy_glossary_items_from( $source_glossary_id ) {
		global $gpdb;

		$current_date = $this->now_in_mysql_format();

		return $this->query("
			INSERT INTO $gpdb->glossary_items (
				id, term, type, examples, comment, suggested_translation, last_update
			)
			SELECT
				%s AS id, term, type, examples, comment, suggested_translation, %s AS last_update
			FROM $gpdb->glossary_items WHERE id = %s", $this->id, $current_date, $source_glossary_id
		);
	}

}

GP::$glossary = new GP_Glossary();