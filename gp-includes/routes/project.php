<?php
class GP_Route_Project extends GP_Route_Main {
	
	function index() {
		$title = __('Projects');
		$projects = GP::$project->top_level();
		gp_tmpl_load( 'projects', get_defined_vars() );
	}
	
	function single( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$sub_projects = $project->sub_projects();
		$translation_sets = GP::$translation_set->by_project_id( $project->id );
		$title = sprintf( __('%s project '), gp_h( $project->name ) );
		gp_tmpl_load( 'project', get_defined_vars() );
	}

	function import_originals_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$kind = 'originals';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_originals_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		
		$block = array( 'GP_Route_Project', '_merge_originals');
		self::_import('mo-file', 'MO', $block, array($project)) or
		self::_import('pot-file', 'PO', $block, array($project));

		wp_redirect( gp_url_project( $project, 'import-originals' ) );
	}

	function _merge_originals( $project, $translations ) {
		global $gpdb;
		$originals_added = $originals_existing = 0;
		$gpdb->update( $gpdb->originals, array( 'status' => '+obsolete' ), array( 'project_id' => $project->id ) );
		foreach( $translations->entries as $entry ) {
			$data = array('project_id' => $project->id, 'context' => $entry->context, 'singular' => $entry->singular,
				'plural' => $entry->plural, 'comment' => $entry->extracted_comments,
				'references' => implode( ' ', $entry->references ), 'status' => '+active' );
			// TODO: fuzzy
			// Do not insert duplicates. This is tricky, because we can't add unique index on the TEXT fields			
			$existing = self::_find_original( $project, $entry );
			if ( $existing ) {
				$gpdb->update( $gpdb->originals, $data, array( 'id' => $existing->id ) );
				$originals_existing++;
			} else {
				$gpdb->insert( $gpdb->originals, $data );
				$originals_added++;
			}
		}
		$gpdb->update( $gpdb->originals, array('status' => '-obsolete'), array('project_id' => $project->id, 'status' => '+obsolete'));
		// TODO: were they really added?
		gp_notice_set( sprintf(__("%s new strings were added, %s existing were updated."), $originals_added, $originals_existing ) );
	}
		
	function edit_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		gp_tmpl_load( 'project-edit', get_defined_vars() );
	}
	
	function edit_post( $project_path ) {
		// TODO: check permissions for project and parent project
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$updated_project = new GP_Project( gp_post( 'project' ) );
		$redirect_url = gp_url_project( $project, '_edit' );
		$this->validate_or_redirect( $updated_project, $redirect_url );
		// TODO: add id check as a validation rule
		if ( $project->id == $updated_project->parent_project_id )
			gp_notice_set( __('The project cannot be parent of itself!'), 'error' );
		elseif ( !is_null( $project->save( $updated_project ) ) )
			$this->notices[] = __('The project was saved.');
		else
			gp_notice_set( __('Error in saving project!'), 'error' );
		$project->reload();

		wp_redirect( $redirect_url );
	}

	function delete_get( $project_path ) {
		// TODO: check permissions for project and parent project
		// TODO: do not delete using a GET request but POST
		// TODO: decide what to do with child projects and translation sets
		// TODO: just deactivate, do not actually delete
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		if ( $project->delete() )
			gp_notice_set( __('The project was deleted.') );
		else
			gp_notice_set( __('Error in deleting project!'), 'error' );
		wp_redirect( gp_url_project( '' ) );
	}

	
	function new_get() {
		// TODO: check permissions for project and parent project		
		$project = new GP_Project();
		$project->parent_project_id = gp_get( 'parent_project_id' );
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		gp_tmpl_load( 'project-new', get_defined_vars() );
	}
	
	function new_post() {
		// TODO: check permissions for project and parent project		
		$project = GP::$project->create_and_select( gp_post( 'project' ) );
		if ( !$project ) {
			$project = new GP_Project();
			gp_notice_set( __('Error in creating project!'), 'error' );
			$all_project_options = self::_options_from_projects( GP::$project->all() );
			gp_tmpl_load( 'project-new', get_defined_vars() );
		} else {
			gp_notice_set( __('The project was created!') );
			wp_redirect( gp_url_project( $project, '_edit' ) );
		}
	}
}