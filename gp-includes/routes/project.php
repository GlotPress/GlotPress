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
					if ( is_null( $entry->context ) ) unset($data['context']);
					if ( is_null( $entry->plural ) ) unset($data['plural']);
					$gpdb->insert( $gpdb->originals, $data );
				}
				// TODO: were they really added?
				gp_notice_set( sprintf(__("%s strings were added"), count($mo->entries) ) );
			}
			wp_redirect( gp_url_join( gp_url_project( $project ), 'import-originals' ) );
		}
	}
	// TODO: PO file parsing in POMO
}


function gp_route_project_translations_get( $project_path, $locale_slug ) {
	global $gpdb;
	$per_page = 1000;
	$project = &GP_Project::get_by_path( $project_path );
	$locale = GP_Locales::by_slug( $locale_slug );
	$limit = gp_limit_for_page( gp_get('page', 1), $per_page );
	$translations = $gpdb->get_results( $gpdb->prepare( "SELECT t.*, o.*, t.id as id, o.id as original_id FROM $gpdb->originals as o LEFT JOIN $gpdb->translations AS t ON o.id = t.original_id AND t.status = 'current' AND t.locale = '%s' WHERE o.project_id = %d ORDER BY t.id ASC $limit", $locale_slug, $project->id ) );
	// TODO: expose paging
	gp_tmpl_load( 'project-translations', get_defined_vars() );
}

function gp_route_project_translations_post( $project_path, $locale_slug ) {
	global $gpdb;
	$project = &GP_Project::get_by_path( $project_path );
	$locale = GP_Locales::by_slug( $locale_slug );
	//TODO: multiple insert
	foreach($_POST['translation'] as $original_id => $translations) {
	    sleep(2);
	    $data = compact('original_id');
	    $data['locale'] = $locale_slug;
	    foreach(range(0, 3) as $i) {
	        if (isset($translations[$i])) $data["translation_$i"] = $translations[$i];
	    }
	    /*
	    Since we still don't have status updates, just insert with status current
	    and set all the previous translations of the same original to sth else
	    */
	    $data['status'] = 'current';
	    $gpdb->update($gpdb->translations, array('status' => 'approved'), array('original_id' => $original_id, 'locale' => $locale_slug));
	    
        $gpdb->insert($gpdb->translations, $data);
	}
}