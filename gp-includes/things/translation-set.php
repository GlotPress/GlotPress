<?php
class GP_Translation_Set {
	
	function by_project_id_slug_and_locale( $project_id, $slug, $locale_slug ) {
		global $gpdb;
		return $gpdb->get_row( $gpdb->prepare( "
		    SELECT * FROM $gpdb->translation_sets
		    WHERE slug = '%s' AND project_id= %d AND locale = %s", $slug, $project_id, $locale_slug ) );
	}
	
	function by_project_id( $project_id ) {
		global $gpdb;
		return $gpdb->get_results( $gpdb->prepare( "
		    SELECT * FROM $gpdb->translation_sets
		    WHERE project_id= %d", $project_id) );
	}
	
}