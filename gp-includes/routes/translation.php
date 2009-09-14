<?php
class GP_Route_Translation extends GP_Route_Main {	
	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		// TODO: permissions
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$kind = 'translations';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		// TODO: permissions
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$block = array( &$this, '_merge_translations');
		self::_import('mo-file', 'MO', $block, array($project, $locale, $translation_set)) or
		self::_import('pot-file', 'PO', $block, array($project, $locale, $translation_set));

		wp_redirect( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ) );
	}

	function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();

		$filename = sprintf( '%s-%s.po', str_replace( '/', '-', $project->path ), $locale->wp_locale );
		// TODO: extract as Translation_Set::export
		$po = new PO();
		// TODO: do not hack per_page, find a smarter way to disable paging
		$old_per_page = GP::$translation->per_page;
		GP::$translation->per_page = 'no-limit';
		$entries = GP::$translation->for_translation( $project, $translation_set, null, array('status' => '+current') );
		foreach( $entries as $entry ) {
			$po->add_entry( $entry );
		}
		GP::$translation->per_page = $old_per_page;
		$po->set_header('Project-Id-Version', $project->name);
		// TODO: add more meta data in the project
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
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$page = gp_get( 'page', 1 );
		$filters = gp_get( 'filters', array() );
		$sort = gp_get( 'sort', array() );
		$translations = GP::$translation->for_translation( $project, $translation_set, $page, $filters, $sort );
		$total_translations_count = GP::$translation->found_rows();
		$per_page = GP::$translation->per_page;
		$can_edit = GP::$user->logged_in();
		$can_approve = $this->can( 'approve', 'project', $project->id );
		$url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) );
		$approve_action = gp_url_join( $url, '_approve' );
		gp_tmpl_load( 'translations', get_defined_vars() );
	}

	function translations_post ( $project_path, $locale_slug, $translation_set_slug ) {
		if ( !GP::$user->logged_in() ) {
			status_header( 403 );
			die('Forbidden');
		}
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		
		foreach( gp_post( 'translation', array() ) as $original_id => $translations) {
		    $data = compact('original_id');
			$data['user_id'] = GP::$user->current()->id;
		    $data['translation_set_id'] = $translation_set->id;
		    foreach(range(0, 3) as $i) {
		        if (isset($translations[$i])) $data["translation_$i"] = $translations[$i];
		    }
			if ( $this->can( 'approve', 'project', $project->id ) || $this->can( 'write', 'project', $project->id ) ) {
				$data['status'] = '+current';
			} else {
				$data['status'] = '-waiting';
			}
			// TODO: validate
			if ( '+current' == $data['status'] ) {
			    GP::$translation->update( array('status' => '-old'),
					array('original_id' => $original_id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
			    GP::$translation->update( array('status' => '-old'),
					array('original_id' => $original_id, 'translation_set_id' => $translation_set->id, 'status' => '-fuzzy'));
			}
			GP::$translation->create( $data );
		}
	}
	
	function approve_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$bulk = gp_post('bulk');
		$action = gp_startswith( $bulk['action'], 'approve-' )? 'approve' : 'reject';
		$bulk['translation-ids'] = array_filter( explode( ',', $bulk['translation-ids'] ) );
		if ( empty( $bulk['translation-ids'] ) ) {
			$this->errors[] = 'No translations were supplied.';
		}
		$ok = $error = 0;
		$method_name = 'approve' == $action? 'set_as_current' : 'reject';
		foreach( $bulk['translation-ids'] as $translation_id ) {
			$translation = GP::$translation->get( $translation_id );
			if ( !$translation ) continue;
			if ( $translation->$method_name() )
				$ok++;
			else
				$error++;
		}

		if ( 0 === $error) {
			$this->notices[] = 'approve' == $action?
					sprintf( _n('One translation approved.', '%d translations approved.', $ok), $ok ):
					sprintf( _n('One translation rejected.', '%d translations rejected.', $ok), $ok );
		} else {
			if ( $ok > 0 ) {
				$message = 'approve' == $action?
						sprintf( _n('Error with approving one translation.', 'Error with approving %s translations.', $error), $error ):
						sprintf( _n('Error with rejecting one translation.', 'Error with rejecting %s translations.', $error), $error );
				$message .= ' ';
				$message .= 'approve' == $action?
						sprintf( _n(
								'The remaining translation was approved successfully.',
								'The remaining %s translations were approved successfully.', $ok), $ok ):
						sprintf( _n(
								'The remaining translation was rejected successfully.',
								'The remaining %s translations were rejected successfully.', $ok), $ok );
			} else {
				$this->errors[] = 'approve' == $action?
						sprintf( _n(
								'Error with approving the translation.',
								'Error with approving all %s translation.', $error), $error ):
						sprintf( _n(
								'Error with rejecting the translation.',
								'Error with rejecting all %s translation.', $error), $error );
			}
		}
		// hack, until we make clean_url() to allow [ and ]
		$bulk['redirect_to'] = str_replace( array('[', ']'), array_map('urlencode', array('[', ']')), $bulk['redirect_to']);
		wp_redirect( $bulk['redirect_to'] );
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
				    $gpdb->update($gpdb->translations, array('status' => '-old'), array('original_id' => $original->id, 'translation_set_id' => $translation_set->id, 'status' => '+current'));
				    $gpdb->update($gpdb->translations, array('status' => '-old'), array('original_id' => $original->id, 'translation_set_id' => $translation_set->id, 'status' => '+fuzzy'));
				
			        $gpdb->insert($gpdb->translations, $data);
					$translations_added++;
				}
			}
		}
		$this->notices[] = sprintf(__("%s translations were added"), $translations_added );
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