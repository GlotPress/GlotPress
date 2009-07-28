<?php
class GP_Translation_Set extends GP_Thing {

	var $table_basename = 'translation_sets';
	var $field_names = array( 'id', 'name', 'slug', 'project_id', 'locale' );
	var $non_updatable_attributes = array( 'id' );

	
	function by_project_id_slug_and_locale( $project_id, $slug, $locale_slug ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE slug = '%s' AND project_id= %d AND locale = %s", $slug, $project_id, $locale_slug );
	}
	
	function by_project_id( $project_id ) {
		return $this->many( "
		    SELECT * FROM $this->table
		    WHERE project_id= %d", $project_id );
	}
	
}
GP::$translation_set = new GP_Translation_Set();