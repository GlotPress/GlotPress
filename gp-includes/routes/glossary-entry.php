<?php
class GP_Route_Glossary_Entry extends GP_Route_Main {

	function glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project         = GP::$project->by_path( $project_path );
		$locale          = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$glossary        = GP::$glossary->by_set_id( $translation_set->id );

		if ( ! $glossary ){
			$this->die_with_404();
		}

		$glossary_entries = GP::$glossary_entry->by_glossary_id( $glossary->id );

		foreach ( $glossary_entries as $key => $entry ) {
			$user = GP::$user->get( $entry->last_edited_by );

			if ( $user ) {
				$glossary_entries[$key]->user_login = $user->user_login;
				$glossary_entries[$key]->user_display_name = $user->display_name;
			}
		}

		$can_edit = $this->can( 'approve', 'translation-set', $translation_set->id );
		$url      = gp_url_join( gp_url_project_locale( $project_path, $locale_slug, $translation_set_slug ), array('glossary') );

		$this->tmpl( 'glossary-view', get_defined_vars() );
	}

	function glossary_entry_add_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project         = GP::$project->by_path( $project_path );
		$locale          = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		$new_glossary_entry = new GP_Glossary_Entry( gp_post('new_glossary_entry'), array() );
		$new_glossary_entry->last_edited_by = GP::$user->current()->id;

		if ( ! $new_glossary_entry->validate() ) {
			$this->errors = $new_glossary_entry->errors;
			$this->redirect( gp_url_join( gp_url_project_locale( $project_path, $locale_slug, $translation_set_slug ), array('glossary' ) ) );
		}
		else {
			$created_glossary_entry = GP::$glossary_entry->create_and_select( $new_glossary_entry );

			if ( ! $created_glossary_entry ) {
				$this->errors[] = __('Error in creating glossary entry!');
				$this->redirect( gp_url_join( gp_url_project_locale( $project_path, $locale_slug, $translation_set_slug ), array('glossary') ) );
			}
			else {
				$this->notices[] = __('The glossary entry was created!');
				$this->redirect( gp_url_join( gp_url_project_locale( $project_path, $locale_slug, $translation_set_slug ), array('glossary') ) );
			}
		}
	}

	function glossary_entries_post( $project_path, $locale_slug, $translation_set_slug ) {
		$ge              = array_shift( gp_post('glossary_entry') );
		$glossary_entry  = GP::$glossary_entry->get( absint( $ge['glossary_entry_id'] ) );
		$glossary        = GP::$glossary->get( $glossary_entry->glossary_id );
		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$can_edit        = $this->can( 'approve', 'translation-set', $translation_set->id );

		if ( ! $can_edit ) {
			$this->die_with_error( __('Forbidden'), 403 );
		}

		$project = GP::$project->get( $translation_set->project_id );
		$locale  = GP_Locales::by_slug( $translation_set->locale );

		$new_glossary_entry = new GP_Glossary_Entry( $ge, array() );
		$new_glossary_entry->last_edited_by = GP::$user->current()->id;

		if ( ! $new_glossary_entry->validate() ) {
			$this->errors = $new_glossary_entry->errors;
		}
		else {
			if ( ! $glossary_entry->update( $new_glossary_entry ) ) {
				$this->errors = $glossary_entry->errors;
			}
		}

		if ( $this->errors ) {
			$error_output = '<ul>';
			foreach ( $this->errors as $error ) {
				$error_output .= '<li>' . $error . '</li>';
			}
			$error_output .= '</ul>';

			$this->die_with_error( $error_output, 200 );
		}
		else {
			$ge     = $glossary_entry->reload();
			$output = gp_tmpl_get_output( 'glossary-entry-row', get_defined_vars() );

			echo json_encode( $output );
		}

		exit();
	}

	function glossary_entry_delete_post( $project_path, $locale_slug, $translation_set_slug ) {
		$ge             = array_shift( gp_post('glossary_entry') );
		$glossary_entry = GP::$glossary_entry->get( absint( $ge['glossary_entry_id'] ) );

		if ( ! $glossary_entry ) {
			$this->die_with_error( __('The glossary entry cannot be found'), 200 );
		}

		$glossary        = GP::$glossary->get( $glossary_entry->glossary_id );
		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$can_edit        = $this->can( 'approve', 'translation-set', $translation_set->id );

		if ( ! $can_edit ) {
			$this->die_with_error( __('Forbidden'), 403 );
		}

		if ( ! $glossary_entry->delete() ) {
			$this->errors[] = __('Error in deleting glossary entry!');
		}

		if ( $this->errors ) {
			$error_output = '<ul>';
			foreach ( $this->errors as $error ) {
				$error_output .= '<li>' . $error . '</li>';
			}
			$error_output .= '</ul>';

			$this->die_with_error( $error_output, 200 );
		}

		echo json_encode('');
		exit();
	}

	function export_glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			$this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$glossary        = GP::$glossary->by_set_id( $translation_set->id );

		if ( ! $translation_set || ! $glossary ) {
			$this->die_with_404();
		}

		$glossary_entries = GP::$glossary_entry->by_glossary_id( $glossary->id );
		$filename         = sprintf( '%s-%s-glossary.csv', str_replace( '/', '-', $project->path ), $locale->slug );
		$last_modified    = gmdate( 'D, d M Y H:i:s', backpress_gmt_strtotime( GP::$glossary_entry->last_modified( $glossary ) ) ) . ' GMT';

		$this->headers_for_download( $filename, $last_modified );
		$this->print_export_file( $locale->slug, $glossary_entries );
	}

	function import_glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			$this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		$this->tmpl( 'glossary-import', get_defined_vars() );
	}

	function import_glossary_entries_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			$this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		$glossary        = GP::$glossary->by_set_id( $translation_set->id );

		if ( ! $translation_set || ! $glossary ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		if ( ! is_uploaded_file( $_FILES['import-file']['tmp_name'] ) ) {
			$this->redirect_with_error( __('Error uploading the file.') );
			return;
		}

		$glossary_entries_added = $this->read_glossary_entries_from_file( $_FILES['import-file']['tmp_name'], $glossary->id, $locale->slug );

		if ( empty( $this->errors ) && is_int( $glossary_entries_added ) ) {
			$this->notices[] = sprintf( __("%s glossary entries were added"), $glossary_entries_added );
		}

		$this->redirect( gp_url_join( gp_url_project_locale( $project_path, $locale_slug, $translation_set_slug ), array('glossary') ) );
	}

	function print_export_file( $locale_slug, $entries ) {
		$outstream = fopen("php://output", 'w');

		fputcsv( $outstream, array( 'en', $locale_slug, 'pos', 'description' ) );

		foreach ( $entries as $entry ) {
			$values = array( $entry->term, $entry->translation, $entry->part_of_speech, $entry->comment );
			fputcsv( $outstream, $values );
		}

		fclose( $outstream );
	}

	function read_glossary_entries_from_file( $file, $glossary_id, $locale_slug ) {
		$f = fopen( $file, 'r' );
		$glossary_entries = 0;

		$data = fgetcsv( $f, 0, ',');

		if ( ! is_array( $data ) ) {
			return;
		}
		else if ( $data[1] !== $locale_slug ) {
			$this->redirect_with_error( __('Unexpected values in the CSV file header row.') );
			return;
		}

		while ( ( $data = fgetcsv( $f, 0, ',') ) !== FALSE ) {
			//We're only parsing one language per file right now
			if ( count ($data) > 4 ) {
				$data = array_splice( $data, 2, -2 );
			}

			$new_glossary_entry = new GP_Glossary_Entry(
				array(
					'glossary_id' => $glossary_id,
					'term' => $data[0],
					'translation' => $data[1],
					'part_of_speech' => $data[2],
					'comment' => $data[3],
					'last_edited_by' => GP::$user->current()->id
				)
			);

			if ( ! $new_glossary_entry->validate() ) {
				continue;
			}
			else {
				$created_glossary_entry = GP::$glossary_entry->create_and_select( $new_glossary_entry );

				if ( $created_glossary_entry ) {
					$glossary_entries++;
				}
			}
		}

		fclose($f);
		return $glossary_entries;
	}

}
