<?php
class GP_Route_Project extends GP_Route_Main {
	
	function index() {
		$title = __('Projects');
		$projects = GP::$project->top_level();
		$this->tmpl( 'projects', get_defined_vars() );
	}
	
	function single( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$sub_projects = $project->sub_projects();
		$translation_sets = GP::$translation_set->by_project_id( $project->id );
		usort( $translation_sets, lambda('$a, $b', 'strcmp($a->name_with_locale(), $b->name_with_locale());') );
		$title = sprintf( __('%s project '), esc_html( $project->name ) );
		$can_write = $this->can( 'write', 'project', $project->id );
		gp_tmpl_load( 'project', get_defined_vars() );
	}
	
	function personal_options_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$user = GP::$user->current();
		$source_url_templates = $user->get_meta( 'source_url_templates' );
		if ( !is_array( $source_url_templates ) ) $source_url_templates = array();
		$source_url_templates[$project->id] = gp_post( 'source-url-template' );
		if ( $user->set_meta( 'source_url_templates', $source_url_templates ) )
			$this->notices[] = 'Source URL template was successfully updated.';
		else
			$this->errors[] = 'Error in updating source URL template.';
		gp_redirect( gp_url_project( $project ) );
	}

	function import_originals_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$kind = 'originals';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_originals_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$block = array( &$this, '_merge_originals');
		self::_import( 'mo-file', 'MO', $block, array($project) ) or
		self::_import( 'pot-file', 'PO', $block, array($project) );

		gp_redirect( gp_url_project( $project, 'import-originals' ) );
	}

	function _merge_originals( $project, $translations ) {
		list( $originals_added, $originals_existing ) = GP::$original->import_for_project( $project, $translations );
		$this->notices[] = sprintf(__("%s new strings were added, %s existing were updated."), $originals_added, $originals_existing );
	}
		
	function edit_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		gp_tmpl_load( 'project-edit', get_defined_vars() );
	}
	
	function edit_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$updated_project = new GP_Project( gp_post( 'project' ) );
		$this->validate_or_redirect( $updated_project, gp_url_project( $project, '_edit' ) );
		// TODO: add id check as a validation rule
		if ( $project->id == $updated_project->parent_project_id )
			$this->errors[] = __('The project cannot be parent of itself!');
		elseif ( $project->save( $updated_project ) )
			$this->notices[] = __('The project was saved.');
		else
			$this->errors[] = __('Error in saving project!');
		$project->reload();

		gp_redirect( gp_url_project( $project, '_edit' ) );
	}

	function delete_get( $project_path ) {
		// TODO: do not delete using a GET request but POST
		// TODO: decide what to do with child projects and translation sets
		// TODO: just deactivate, do not actually delete
		$project = GP::$project->by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		if ( $project->delete() )
			$this->notices[] = __('The project was deleted.');
		else
			$this->errors[] = __('Error in deleting project!');
		gp_redirect( gp_url_project( '' ) );
	}

	
	function new_get() {
		$project = new GP_Project();
		$project->parent_project_id = gp_get( 'parent_project_id', null );
		$this->can_or_redirect( 'write', 'project', $project->parent_project_id );
		$all_project_options = self::_options_from_projects( GP::$project->all() );
		gp_tmpl_load( 'project-new', get_defined_vars() );
	}
	
	function new_post() {
		$post = gp_post( 'project' );
		$parent_project_id = gp_array_get( $post, 'parent_project_id', null );
		$this->can_or_redirect( 'write', 'project', $parent_project_id );
		$new_project = new GP_Project( $post );
		$this->validate_or_redirect( $new_project );
		$project = GP::$project->create_and_select( $new_project );
		if ( !$project ) {
			$project = new GP_Project();
			$this->errors[] = __('Error in creating project!');
			$all_project_options = self::_options_from_projects( GP::$project->all() );
			gp_tmpl_load( 'project-new', get_defined_vars() );
		} else {
			$this->notices[] = __('The project was created!');
			gp_redirect( gp_url_project( $project, '_edit' ) );
		}
	}
}