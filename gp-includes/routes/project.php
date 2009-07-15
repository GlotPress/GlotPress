<?php
class GP_Route_Project extends GP_Route_Main {
	function index( $project_path ) {
		$project = GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$sub_projects = $project->sub_projects();
		$translation_sets = GP_Translation_Set::by_project_id( $project->id );
		$title = sprintf( __('%s project '), gp_h( $project->name ) );
		gp_tmpl_load( 'project', get_defined_vars() );
	}

	function import_originals_get( $project_path ) {
		$project = GP_Project::by_path( $project_path );
		$kind = 'originals';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_originals_post( $project_path ) {
		$project = GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		
		$block = array( 'GP_Route_Project', '_merge_originals');
		self::_import('mo-file', 'MO', $block, array($project)) or
		self::_import('pot-file', 'PO', $block, array($project));

		wp_redirect( gp_url_project( $project, 'import-originals' ) );
	}

	function _merge_originals( $project, $translations ) {
		global $gpdb;
		$originals_added = 0;
		$gpdb->update( $gpdb->originals, array('status' => '+obsolete'), array('project_id' => $project->id));
		foreach( $translations->entries as $entry ) {
			$data = array('project_id' => $project->id, 'context' => $entry->context, 'singular' => $entry->singular,
				'plural' => $entry->plural, 'comment' => $entry->extracted_comments,
				'references' => implode( ' ', $entry->references ), 'status' => '+active' );
			if ( is_null( $entry->context ) ) unset($data['context']);
			if ( is_null( $entry->plural ) ) unset($data['plural']);
			// Do not insert duplicates. This is tricky, because we can't add unique index on the TEXT fields			
			$existing = self::_find_original( $project, $entry );
			if ( $existing ) {
				$gpdb->update( $gpdb->originals, $data, array('id' => $existing->id ) );
			} else {
				$gpdb->insert( $gpdb->originals, $data );
				$originals_added++;
			}
		}
		$gpdb->update( $gpdb->originals, array('status' => '-obsolete'), array('project_id' => $project->id, 'status' => '+obsolete'));
		// TODO: were they really added?
		gp_notice_set( sprintf(__("%s strings were added."), count($originals_added) ) );
	}
}