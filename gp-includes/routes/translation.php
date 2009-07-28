<?php
class GP_Route_Translation extends GP_Route_Main {
	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$kind = 'translations';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		global $gpdb;
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$block = array( 'GP_Route_Translation', '_merge_translations');
		self::_import('mo-file', 'MO', $block, array($project, $locale, $translation_set)) or
		self::_import('pot-file', 'PO', $block, array($project, $locale, $translation_set));

		wp_redirect( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ) );
	}

	function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();

		$filename = sprintf( '%s-%s.po', str_replace( '/', '-', $project->path ), $locale->wp_locale );
		// TODO: extract as Translation_Set::export
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
		$po->set_header('Plural-Forms', "nplurals=$locale->nplurals; plural=$locale->plural_expression;");
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
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$translations = GP_Translation::by_project_and_translation_set( $project, $translation_set );
		gp_tmpl_load( 'translations', get_defined_vars() );
	}

	function translations_post ($project_path, $locale_slug, $translation_set_slug ) {
		global $gpdb;
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
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
		    $gpdb->update($gpdb->translations, array('status' => '-approved'), array('original_id' => $original_id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
		    $gpdb->update($gpdb->translations, array('status' => '-approved'), array('original_id' => $original_id, 'translation_set_id' => $translation_set->id, 'status' => '+fuzzy'));
		
	    
	        $gpdb->insert($gpdb->translations, $data);
		}
	}

	function _merge_translations( $project, $locale, $translation_set, $translations ) {
		global $gpdb;
		$translations_added = 0;
		foreach( $translations->entries as $entry ) {
			if ( empty( $entry->translations )) continue;
			$original = self::_find_original( $project, $entry );
			if ( $original ) {
				$translation = self::_find_translation( $original, $translation_set, $entry );
				if ( !$translation ) {
					$data = array( 'original_id' => $original->id );
					$data['translation_set_id'] = $translation_set->id;
				    foreach(range(0, 3) as $i) {
				        if (isset($entry->translations[$i])) $data["translation_$i"] = $entry->translations[$i];
				    }
					// TODO: extract setting the current translation to GP_Translation::set_current()					
				    $data['status'] = in_array( 'fuzzy', $entry->flags )? '+fuzzy' : '+current';
				    $gpdb->update($gpdb->translations, array('status' => '-approved'), array('original_id' => $original->id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
				    $gpdb->update($gpdb->translations, array('status' => '-approved'), array('original_id' => $original->id, 'translation_set_id' => $translation_set->id, 'status' => '+fuzzy'));
				
			        $gpdb->insert($gpdb->translations, $data);
					$translations_added++;
				}
			}
		}
		gp_notice_set( sprintf(__("%s translations were added"), $translations_added ) );
	}

	function _find_translation( $original, $translation_set, $entry ) {
		global $gpdb;
		$where = array();
		$where[] = 'original_id = %s';
		$where[] = 'translation_set_id = %s';
		$tr = array_pad( $entry->translations, 4, null );
		foreach(range(0, 3) as $i) {
			$where[] = is_null($tr[$i])? "(translation_$i IS NULL OR %s IS NULL)" : "BINARY translation_$i = %s";
		}
		$where = implode( ' AND ', $where );
		$sql = $gpdb->prepare( "SELECT * FROM $gpdb->translations WHERE $where", $original->id, $translation_set->id, $tr[0], $tr[1], $tr[2], $tr[3] );
		return $gpdb->get_row( $sql );
	}
}