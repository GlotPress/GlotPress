<?php
class GP_Route_Project {
	function index( $project_path ) {
		global $gpdb;
		$project = &GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		$sub_projects = $project->sub_projects();
		$translation_sets = GP_Translation_Set::by_project_id( $project->id );
		$title = sprintf( __('%s project '), gp_h( $project->name ) );
		gp_tmpl_load( 'project', get_defined_vars() );
	}

	function import_originals_get( $project_path ) {
		global $gpdb;
		$project = &GP_Project::by_path( $project_path );
		$kind = 'originals';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_originals_post( $project_path ) {
		global $gpdb;
		$project = &GP_Project::by_path( $project_path );
		if ( !$project ) gp_tmpl_404();
		
		$block = array( 'GP_Route_Project', '_merge_originals');
		GP_Route_Project::_import('mo-file', 'MO', $block, array($project)) or
		GP_Route_Project::_import('pot-file', 'PO', $block, array($project));

		wp_redirect( gp_url_join( gp_url_project( $project ), 'import-originals' ) );
	}

	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP_Project::by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$kind = 'translations';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		global $gpdb;
		$project = GP_Project::by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$block = array( 'GP_Route_Project', '_merge_translations');
		GP_Route_Project::_import('mo-file', 'MO', $block, array($project, $locale, $translation_set)) or
		GP_Route_Project::_import('pot-file', 'PO', $block, array($project, $locale, $translation_set));

		wp_redirect( gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, 'import-translations' ) ) );
	}

	function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP_Project::by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();

		$filename = sprintf( '%s-%s.po', str_replace( '/', '-', $project->path ), $locale->wp_locale );
		$po = new PO();
		$po->merge_with(GP_Translation::by_project_and_translation_set_and_status( $project, $translation_set, '+current' ));
		$po->set_header('Project-Id-Version', $project->name);
		// TODO: add more meta info in the project
		$po->set_header('Report-Msgid-Bugs-To', 'wp-polyglots@lists.automattic.com');
		// TODO: last updated for a translation set
		$po->set_header('PO-Revision-Date', gmdate('Y-m-d H:i:s+0000'));
		// TODO: Language Team
		$po->set_header('MIME-Version', '1.0');
		$po->set_header('Content-Type', 'text/plain; charset=UTF-8');
		$po->set_header('Content-Transfer-Encoding', '8bit');
		// TODO: get from locale
		$po->set_header('Plural-Forms', 'nplurals=2; plural=n != 1;');
		$po->set_header('X-Generator', 'GlotPress/' . gp_get_option('version'));
				
		header('Content-Description: File Transfer');
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: application/octet-stream", true);
		echo "# Translation of {$project->name} in {$locale->english_name}\n";
		echo "# This file is distributed under the same license as the {$project->name} package.\n";
		echo $po->export();
		
	}

	function translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP_Project::by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$translations = GP_Translation::by_project_and_translation_set( $project, $translation_set );
		gp_tmpl_load( 'translations', get_defined_vars() );
	}

	function translations_post ($project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP_Project::by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = &GP_Translation_Set::by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		//TODO: multiple insert
		foreach($_POST['translation'] as $original_id => $translations) {
		    $data = compact('original_id');
		    $data['translation_set_id'] = $translation_set->id;
		    foreach(range(0, 3) as $i) {
		        if (isset($translations[$i])) $data["translation_$i"] = $translations[$i];
		    }
		    /*
		    Since we still don't have status updates, just insert with status current
		    and set all the previous translations of the same original to sth else
		    */
		    $data['status'] = '+current';
		    $gpdb->update($gpdb->translations, array('status' => 'approved'), array('original_id' => $original_id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
	    
	        $gpdb->insert($gpdb->translations, $data);
		}
	}

	function _import($file_key, $class, $block, $block_args) {
		global $gpdb;
		if ( is_uploaded_file( $_FILES[$file_key]['tmp_name'] ) ) {
			$translations = new $class();
			$result = $translations->import_from_file( $_FILES[$file_key]['tmp_name'] );
			if ( !$result ) {
				gp_notice_set( __("Couldn&#8217;t load translations from file!"), 'error' );
			} else {
				$block_args[] = $translations;
				call_user_func_array( $block, $block_args );
			}
			return true;
		}
		return false;
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
			$existing = GP_Route_Project::_find_original( $project, $entry );
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
	
	function _merge_translations( $project, $locale, $translation_set, $translations ) {
		global $gpdb;
		$translations_added = 0;
		foreach( $translations->entries as $entry ) {
			if ( empty( $entry->translations )) continue;
			$original = GP_Route_Project::_find_original( $project, $entry );
			if ( $original ) {
				$translation = GP_Route_Project::_find_translation( $original, $translation_set, $entry );
				if ( !$translation ) {
					$data = array( 'original_id' => $original->id );
					$data['translation_set_id'] = $translation_set->id;
				    foreach(range(0, 3) as $i) {
				        if (isset($entry->translations[$i])) $data["translation_$i"] = $entry->translations[$i];
				    }
					// TODO: extract setting the current translation to GP_Translation::set_current()					
				    $data['status'] = in_array( 'fuzzy', $entry->flags )? '+fuzzy' : '+current';
				    $gpdb->update($gpdb->translations, array('status' => '-approved'), array('original_id' => $original->id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
			        $gpdb->insert($gpdb->translations, $data);
					$translations_added++;
				}
			}
		}
		gp_notice_set( sprintf(__("%s translations were added"), $translations_added ) );
	}
	
	function _find_original( $project, $entry ) {
		global $gpdb;
		$where = array();
		// TODO: fix db::prepare to understand %1$s
		// now each condition has to contain a %s not to break the sequence
		$where[] = is_null( $entry->context )? '(context IS NULL OR %s IS NULL)' : 'context = %s';
		$where[] = 'singular = %s';
		$where[] = is_null( $entry->plural )? '(plural IS NULL OR %s IS NULL)' : 'plural = %s';
		$where[] = 'project_id = %d';
		$where = implode( ' AND ', $where );
		$sql = $gpdb->prepare( "SELECT * FROM $gpdb->originals WHERE $where", $entry->context, $entry->singular, $entry->plural, $project->id );
		return $gpdb->get_row( $sql );
	}
	
	function _find_translation( $original, $translation_set, $entry ) {
		global $gpdb;
		$where = array();
		$where[] = 'original_id = %s';
		$where[] = 'translation_set_id = %s';
		$tr = array_pad( $entry->translations, 4, null );
		foreach(range(0, 3) as $i) {
			$where[] = is_null($tr[$i])? "(translation_$i IS NULL OR %s is NULL)" : "translation_$i = %s";
		}
		$where = implode( ' AND ', $where );
		$sql = $gpdb->prepare( "SELECT * FROM $gpdb->translations WHERE $where", $original->id, $translation_set->id, $tr[0], $tr[1], $tr[2], $tr[3] );
		return $gpdb->get_row( $sql );
	}
}