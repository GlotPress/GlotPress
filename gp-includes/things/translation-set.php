<?php
class GP_Translation_Set extends GP_Thing {

	var $table_basename = 'translation_sets';
	var $field_names = array( 'id', 'name', 'slug', 'project_id', 'locale' );
	var $non_updatable_attributes = array( 'id' );

	function restrict_fields( $set ) {
		$set->name_should_not_be('empty');
		$set->slug_should_not_be('empty');
		$set->locale_should_not_be('empty');
		// TODO: do not allow translations sets with the same project, locale and slug. Might be a good to constraint it in the DB
	}
	
	function by_project_id_slug_and_locale( $project_id, $slug, $locale_slug ) {
		return $this->one( "
		    SELECT * FROM $this->table
		    WHERE slug = '%s' AND project_id= %d AND locale = %s", $slug, $project_id, $locale_slug );
	}
	
	function by_project_id( $project_id ) {
		return $this->many( "
		    SELECT * FROM $this->table
		    WHERE project_id = %d", $project_id );
	}
	
	function export_as_po() {
		if ( !isset( $this->project ) || !$this->project ) $this->project = GP::$project->get( $this->project_id );
		$locale = GP_Locales::by_slug( $this->locale );
		
		$po = new PO();
		// TODO: add more meta data in the project: language team, report URL
		// TODO: last updated for a translation set
		$po->set_header( 'PO-Revision-Date', gmdate('Y-m-d H:i:s+0000') );
		$po->set_header( 'MIME-Version', '1.0' );
		$po->set_header( 'Content-Type', 'text/plain; charset=UTF-8' );
		$po->set_header( 'Content-Transfer-Encoding', '8bit' );
		$po->set_header( 'Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;" );
		$po->set_header( 'X-Generator', 'GlotPress/' . gp_get_option('version') );
		
		// TODO: do not hack per_page, find a smarter way to disable paging
		$old_per_page = GP::$translation->per_page;
		GP::$translation->per_page = 'no-limit';
		$entries = GP::$translation->for_translation( $this->project, $this, null, array('status' => '+current') );
		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}
		GP::$translation->per_page = $old_per_page;
		$po->set_header( 'Project-Id-Version', $this->project->name );
		return $po->export();
	}
}
GP::$translation_set = new GP_Translation_Set();