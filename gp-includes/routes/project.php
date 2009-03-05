<?php
function gp_route_project( $project_path ) {
	global $gpdb;
	$project = &GP_Project::get_by_path( $project_path );
	if ( !$project ) gp_tmpl_404();
	// TODO: list subprojects
	$title = sprintf( __('%s project '), gp_h( $project->name ) );	
	gp_tmpl_load( 'project', get_defined_vars() );
}

function gp_route_project_import_originals_get( $project_path ) {
	global $gpdb;
	$project = &GP_Project::get_by_path( $project_path );
	$title = sprintf( __('Import originals for %s' ), $project->name );
	gp_tmpl_load( 'project-import-originals', get_defined_vars() );
}

function gp_route_project_import_originals_post( $project_path ) {
	global $gpdb;
	$project = &GP_Project::get_by_path( $project_path );
	if ( !$project ) gp_tmpl_404();
	$source = gp_post( 'source' );
	if ( 'mo' == $source ) {
		if ( is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
			$mo = new MO();
			$result = $mo->import_from_file( $_FILES['file']['tmp_name'] );
			if ( !$result ) {
				gp_notice_set( __("Couldn&#8217;t load translations from MO file!"), 'error' );
			} else {
				// TODO: do not insert duplicates. This is tricky, because we can't add unique index on the TEXT fields
				foreach( $mo->entries as $entry ) {
					$data = array('project_id' => $project->id, 'context' => $entry->context,
						'singular' => $entry->singular, 'plural' => $entry->plural );
					if ( is_null( $entry->context) ) unset($data['context']);
					if ( is_null( $entry->plural) ) unset($data['plural']);
					$gpdb->insert($gpdb->originals, $data );
				}
				// TODO: were they really added?
				gp_notice_set( sprintf(__("%s strings were added"), count($mo->entries) ) );
			}
			gp_redirect( gp_url_join( gp_url_project( $project ), 'import-originals' ) );
		}
	}
	// TODO: PO file parsing in POMO
}


function gp_route_project_translations( $project_path, $locale_slug, $page = null ) {
	global $gpdb;
	$per_page = 50;
	$project = &GP_Project::get_by_path( $project_path );
	$locale = GP_Locales::by_slug( $locale_slug );
	$limit = gp_limit_for_page( $page, $per_page );
	$translations = $gpdb->get_results( $gpdb->prepare( "SELECT t.*, o.* FROM $gpdb->originals as o LEFT JOIN $gpdb->translations as t ON o.id = t.original_id WHERE o.project_id = %d AND ( locale IS NULL OR locale = '%s') ORDER BY t.id ASC $limit", $project->id, $locale_slug ) );
	// TODO: expose paging
	gp_tmpl_load( 'project-translations', get_defined_vars() );
}