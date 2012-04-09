<?php
class GP_Route_Translation extends GP_Route_Main {
	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) return;
		$kind = 'translations';
		$this->tmpl( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) return;

		$format = gp_array_get( GP::$formats, gp_post( 'format', 'po' ), null );
		if ( !$format ) {
			$this->redirect_with_error( __('No such format.') );
			return;
		}

		if ( !is_uploaded_file( $_FILES['import-file']['tmp_name'] ) ) {
			$this->redirect_with_error( __('Error uploading the file.') );
			return;
		}

		$translations = $format->read_translations_from_file( $_FILES['import-file']['tmp_name'], $project );
		if ( !$translations ) {
			$this->redirect_with_error( __('Couldn&#8217;t load translations from file!') );
			return;
		}

		$translations_added = $translation_set->import( $translations );
		$this->notices[] = sprintf(__("%s translations were added"), $translations_added );
				
		$this->redirect( gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) ) );		
	}
	
	function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();

		$format = gp_array_get( GP::$formats, gp_get( 'format', 'po' ), null );
		if ( !$format ) gp_tmpl_404();

		$export_locale = apply_filters( 'export_locale', $locale->slug, $locale );
		$filename = sprintf( '%s-%s.'.$format->extension, str_replace( '/', '-', $project->path ), $export_locale );
		$entries = GP::$translation->for_export( $project, $translation_set, gp_get( 'filters' ) );
		$this->headers_for_download( $filename );		
		echo $format->print_exported_file( $project, $locale, $translation_set, $entries );
	}

	function translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$page = gp_get( 'page', 1 );
		$filters = gp_get( 'filters', array() );
		$sort = gp_get( 'sort', array() );
		if ( 'random' == gp_array_get( $sort, 'by') ) {
			add_filter( 'gp_pagination', create_function( '$html', 'return "";' ) );
		}
		$translations = GP::$translation->for_translation( $project, $translation_set, $page, $filters, $sort );
		$total_translations_count = GP::$translation->found_rows;
		$per_page = GP::$translation->per_page;
		$can_edit = GP::$user->logged_in();
		$can_write = $this->can( 'write', 'project', $project->id );
		$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
		$url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) );
		$set_priority_url = gp_url( '/originals/%original-id%/set_priority');
		$discard_warning_url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, '-discard-warning' ) );
		$set_status_url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, '-set-status' ) );
		$bulk_action = gp_url_join( $url, '-bulk' );
		$this->tmpl( 'translations', get_defined_vars() );
	}

	function translations_post ( $project_path, $locale_slug, $translation_set_slug ) {
		$this->logged_in_or_forbidden();
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		
		$output = array();
		foreach( gp_post( 'translation', array() ) as $original_id => $translations) {
		    $data = compact('original_id');
			$data['user_id'] = GP::$user->current()->id;
		    $data['translation_set_id'] = $translation_set->id;
		    foreach( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) ) as $i ) {
		        if ( isset( $translations[$i] ) ) $data["translation_$i"] = $translations[$i];
		    }
			if ( $this->can( 'approve', 'translation-set', $translation_set->id ) || $this->can( 'write', 'project', $project->id ) ) {
				$data['status'] = 'current';
			} else {
				$data['status'] = 'waiting';
			}
			$original = GP::$original->get( $original_id );
			$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );
			// TODO: validate
			$translation = GP::$translation->create( $data );
			if ( 'current' == $data['status'] ) {
				$translation->set_status( 'current' );
			}
			wp_cache_delete( $translation_set->id, 'translation_set_status_breakdown' );
			$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id), array() );
			if ( $translations ) {
				$t = $translations[0];
				$parity = returner( 'even' );
				$can_edit = GP::$user->logged_in();
				$can_write = $this->can( 'write', 'project', $project->id );
				$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
				$output[$original_id] = gp_tmpl_get_output( 'translation-row', get_defined_vars() );
			} else {
				$output[$original_id] = false;
			}
		}
		echo json_encode( $output );
	}
	
	function bulk_post( $project_path, $locale_slug, $translation_set_slug ) {

		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) return;

		$bulk = gp_post('bulk');
		$bulk['row-ids'] = array_filter( explode( ',', $bulk['row-ids'] ) );
		if ( !empty( $bulk['row-ids'] ) ) {
			if ( 'approve' == $bulk['action'] || 'reject' == $bulk['action'] ) {
				$this->_bulk_approve( $project, $locale, $translation_set, $bulk );
			}

			if ( 'gtranslate' == $bulk['action'] ) {
				$this->_bulk_google_translate( $project, $locale, $translation_set, $bulk );
			}
		} else {
			$this->errors[] = 'No translations were supplied.';
		}
		
		wp_cache_delete( $translation_set->id, 'translation_set_status_breakdown' );
		
		// hack, until we make clean_url() to allow [ and ]
		$bulk['redirect_to'] = str_replace( array('[', ']'), array_map('urlencode', array('[', ']')), $bulk['redirect_to']);
		$this->redirect( $bulk['redirect_to'] );
	}
	
	function _bulk_approve( $project, $locale, $translation_set, $bulk ) {
		
		$action = $bulk['action'];
		
		$ok = $error = 0;
		$new_status = 'approve' == $action? 'current' : 'rejected';
		foreach( $bulk['row-ids'] as $row_id ) {
			$translation_id = gp_array_get( split( '-', $row_id ), 1 );
			$translation = GP::$translation->get( $translation_id );
			if ( !$translation ) continue;
			if ( $translation->set_status( $new_status ) )
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
				$this->errors[] = $message;
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
	}
	
	function _bulk_google_translate( $project, $locale, $translation_set, $bulk ) {
		$google_errors = 0;
		$insert_errors = 0;
		$ok = 0;
		$skipped = 0;
		
		$singulars = array();
		$original_ids = array();
		foreach( $bulk['row-ids'] as $row_id ) {
			if ( gp_in( '-', $row_id) ) {
				$skipped++;
				continue;
			}
			$original_id = gp_array_get( split( '-', $row_id ), 0 );
			$original = GP::$original->get( $original_id );
			if ( !$original || $original->plural ) {
				$skipped++;
				continue;
			}
			$singulars[] = $original->singular;
			$original_ids[] = $original_id;
		}
		$results = google_translate_batch( $locale, $singulars );
		if ( is_wp_error( $results ) ) {
			error_log( print_r( $results, true ) );
			$this->errors[] = $results->get_error_message();
			return;
		}
		foreach( gp_array_zip( $original_ids, $singulars, $results )  as $item ) {
			list( $original_id, $singular, $translation ) = $item;
			if ( is_wp_error( $translation ) ) {
				$google_errors++;
				error_log( $translation->get_error_message() );
				continue;
			}
			$data = compact( 'original_id' );
			$data['user_id'] = GP::$user->current()->id;
			$data['translation_set_id'] = $translation_set->id;
			$data['translation_0'] = $translation;
			$data['status'] = 'fuzzy';
			$data['warnings'] = GP::$translation_warnings->check( $singular, null, array( $translation ), $locale );
			$inserted = GP::$translation->create( $data );
			$inserted? $ok++ : $insert_errors++;
		}
		if ( $google_errors > 0 || $insert_errors > 0 ) {
			$message = array();
			if ( $ok ) $message[] = sprintf( __('Added: %d.' ), $ok );
			if ( $google_errors ) $message[] = sprintf( __('Error from Google Translate: %d.' ), $google_errors );
			if ( $insert_errors ) $message[] = sprintf( __('Error adding: %d.' ), $insert_errors );
			if ( $skipped ) $message[] = sprintf( __('Skipped: %d.' ), $skipped );
			$this->errors[] = implode( '', $message );
		} else {
			$this->notices[] = sprintf( __('%d fuzzy translation from Google Translate were added.' ), $ok );
		}
	}
			
	function discard_warning( $project_path, $locale_slug, $translation_set_slug ) {
		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, array( $this, 'discard_warning_edit_function' ) );
	}
	
	function set_status( $project_path, $locale_slug, $translation_set_slug ) {
		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, array( $this, 'set_status_edit_function' ) );
	}
			
	private function edit_single_translation( $project_path, $locale_slug, $translation_set_slug, $edit_function ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_forbidden( 'approve', 'translation-set', $translation_set->id );
		
		$translation = GP::$translation->get( gp_post( 'translation_id' ) );
		if ( !$translation ) {
			$this->die_with_error( 'Translation doesn&#8217;t exist!' );
		}
		
		call_user_func( $edit_function, $project, $locale, $translation_set, $translation );

		$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id, 'status' => 'either'), array() );
		if ( $translations ) {
			$t = $translations[0];
			$parity = returner( 'even' );
			$can_edit = GP::$user->logged_in();
			$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
			$this->tmpl( 'translation-row', get_defined_vars() );
		} else {
			$this->die_with_error( 'Error in retrieving translation!' );
		}		
	}
	
	private function discard_warning_edit_function( $project, $locale, $translation_set, $translation ) {
		if ( !isset( $translation->warnings[gp_post( 'index' )][gp_post( 'key' )] ) ) {
			$this->die_with_error( 'The warning doesn&#8217;exist!' );
		}
		unset( $translation->warnings[gp_post( 'index' )][gp_post( 'key' )] );
		if ( empty( $translation->warnings[gp_post( 'index' )] ) ) {
			unset( $translation->warnings[gp_post( 'index' )] );
		}
		$res = $translation->save();
		if ( !$res ) {
			$this->die_with_error( 'Error in saving the translation!' );
		}
		
	}

	private function set_status_edit_function( $project, $locale, $translation_set, $translation ) {
		$res = $translation->set_status( gp_post( 'status' ) );
		if ( !$res ) {
			$this->die_with_error( 'Error in saving the translation status!' );
		}
	}


	
}
