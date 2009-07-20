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
		if ( !$project ) gp_tmpl_404();
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
	
	function _options_from_projects( $projects ) {
		// TODO: mark which nodes are editable by the current user
		$tree = array();
		$top = array();
		foreach( $projects as $p ) {
			$tree[$p->id]['self'] = $p;
			if ( $p->parent_project_id ) {
				$tree[$p->parent_project_id]['children'][] = $p->id;
			} else {
				$top[] = $p->id;
			}
		}
		$options = array( '' => __('No parent') );
		$stack = array();
		foreach( $top as $top_id ) {
			$stack = array( $top_id );
			while ( !empty( $stack ) ) {
				$id = array_pop( $stack );
				$tree[$id]['level'] = gp_array_get( $tree[$id], 'level', 0 );
				$options[$id] = str_repeat( '-', $tree[$id]['level'] ) . $tree[$id]['self']->name;
				foreach( gp_array_get( $tree[$id], 'children', array() ) as $child_id ) {
					$stack[] = $child_id;
					$tree[$child_id]['level'] = $tree[$id]['level'] + 1;
				}
			}
		}
		return $options;
	}
	
	function edit_get( $project_path ) {
		$project = GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$all_project_options = self::_options_from_projects( GP_Project::all() );
		gp_tmpl_load( 'project-edit', get_defined_vars() );
	}
	
	function edit_post( $project_path ) {
		// TODO: check permissions for project and parent project
		$project = GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$updated_project = gp_post( 'project' );
		// validate here? or in GP_Project?
		if ( $project->id == $updated_project['parent_project_id'] )
			gp_notice_set( __('The project cannot be parent of itself!'), 'error' );
		elseif ( !is_null( $project->save( $updated_project ) ) )
			gp_notice_set( __('The project was saved.') );
		else
			gp_notice_set( __('Error in saving project!'), 'error' );
		$project->reload();
		wp_redirect( gp_url_project( $project, '_edit' ) );
	}
	
	function new_get() {
		// TODO: check permissions for project and parent project		
		$project = new GP_Project();
		$project->parent_project_id = gp_get( 'parent_project_id' );
		$form_action = "";
		$all_project_options = self::_options_from_projects( GP_Project::all() );
		gp_tmpl_load( 'project-new', get_defined_vars() );
	}
	
	function new_post() {
		// TODO: check permissions for project and parent project		
		$project = GP_Project::create_and_select( gp_post( 'project' ) );
		if ( !$project ) {
			$project = new GP_Project();
			gp_notice_set( __('Error in creating project!'), 'error' );
			$all_project_options = self::_options_from_projects( GP_Project::all() );
			gp_tmpl_load( 'project-new', get_defined_vars() );
		} else {
			gp_notice_set( __('The project was created!') );
			wp_redirect( gp_url_project( $project, '_edit' ) );
		}
	}
}