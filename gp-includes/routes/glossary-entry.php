<?php
/**
 * Routes: GP_Route_Glossary_Entry class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the glossary entry route.
 *
 * @since 1.0.0
 */
class GP_Route_Glossary_Entry extends GP_Route_Main {

	public function glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = GP::$glossary->by_set_or_parent_project( $translation_set, $project );

		if ( ! $glossary ) {
			return $this->die_with_404();
		}

		$glossary_entries = GP::$glossary_entry->by_glossary_id( $glossary->id );

		foreach ( $glossary_entries as $key => $entry ) {
			$user = get_userdata( $entry->last_edited_by );

			if ( $user ) {
				$glossary_entries[ $key ]->user_login        = $user->user_login;
				$glossary_entries[ $key ]->user_display_name = $user->display_name;
			}
		}

		$can_edit = $this->can( 'approve', 'translation-set', $translation_set->id );
		$url      = gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) );

		$this->tmpl( 'glossary-view', get_defined_vars() );
	}

	public function glossary_entry_add_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		if ( $this->invalid_nonce_and_redirect( 'add-glossary-entry_' . $project->path . $locale_slug . $translation_set_slug ) ) {
			return;
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}
		$new_glossary_entry                 = new GP_Glossary_Entry( gp_post( 'new_glossary_entry' ) );
		$new_glossary_entry->last_edited_by = get_current_user_id();

		$glossary = GP::$glossary->get( $new_glossary_entry->glossary_id );

		if ( ! $new_glossary_entry->validate() ) {
			$this->errors = $new_glossary_entry->errors;
			$this->redirect( gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) ) );
		} else {
			$find_params = array(
				'glossary_id'    => $glossary->id,
				'term'           => $new_glossary_entry->term,
				'translation'    => $new_glossary_entry->translation,
				'part_of_speech' => $new_glossary_entry->part_of_speech,
			);

			if ( GP::$glossary_entry->find_one( $find_params ) ) {
				// Translators: 1: the glossary term that was attempted to be added, 2: the part of speech that was attempted to be added.
				$this->errors[] = sprintf( __( 'Identical glossary entry already exists for "%1$s" as "%2$s"!', 'glotpress' ), esc_html( $new_glossary_entry->term ), esc_html( $new_glossary_entry->part_of_speech ) );
				$this->redirect( gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) ) );
			} else {
				$created_glossary_entry = GP::$glossary_entry->create_and_select( $new_glossary_entry );

				if ( ! $created_glossary_entry ) {
					$this->errors[] = __( 'Error in creating glossary entry!', 'glotpress' );
					$this->redirect( gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) ) );
				} else {
					$this->notices[] = __( 'The glossary entry was created!', 'glotpress' );
					$this->redirect( gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) ) );
				}
			}
		}
	}

	public function glossary_entries_post( $project_path, $locale_slug, $translation_set_slug ) {
		$ge_post = gp_post( 'glossary_entry' );
		if ( ! is_array( $ge_post ) ) {
			return $this->die_with_error( __( 'The glossary entry cannot be found', 'glotpress' ), 404 );
		}

		$ge = array_shift( $ge_post );
		if ( ! isset( $ge['glossary_entry_id'] ) ) {
			return $this->die_with_error( __( 'The glossary entry cannot be found', 'glotpress' ), 404 );
		}

		$glossary_entry = GP::$glossary_entry->get( absint( $ge['glossary_entry_id'] ) );
		if ( ! $glossary_entry ) {
			return $this->die_with_error( __( 'The glossary entry cannot be found', 'glotpress' ), 404 );
		}

		if ( ! $this->verify_nonce( 'edit-glossary-entry_' . $glossary_entry->id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$glossary        = GP::$glossary->get( $glossary_entry->glossary_id );
		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$can_edit        = $this->can( 'approve', 'translation-set', $translation_set->id );

		if ( ! $can_edit ) {
			return $this->die_with_error( __( 'Forbidden', 'glotpress' ), 403 );
		}

		$project = GP::$project->get( $translation_set->project_id );
		$locale  = GP_Locales::by_slug( $translation_set->locale );

		$new_glossary_entry                 = new GP_Glossary_Entry( $ge );
		$new_glossary_entry->last_edited_by = get_current_user_id();

		if ( ! $new_glossary_entry->validate() ) {
			$this->errors = $new_glossary_entry->errors;
		} else {
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

			return $this->die_with_error( $error_output, 200 );
		} else {
			$entry                    = $glossary_entry->reload();
			$user                     = get_userdata( $entry->last_edited_by );
			$entry->user_login        = $user ? $user->user_login : '';
			$entry->user_display_name = $user ? $user->display_name : '';
			$output                   = gp_tmpl_get_output( 'glossary-entry-row', get_defined_vars() );

			echo wp_json_encode( $output );
		}

		exit();
	}

	public function glossary_entry_delete_post() {
		$ge_post        = gp_post( 'glossary_entry' );
		$ge             = array_shift( $ge_post );
		$glossary_entry = GP::$glossary_entry->get( absint( $ge['glossary_entry_id'] ) );

		if ( ! $glossary_entry ) {
			return $this->die_with_error( __( 'The glossary entry cannot be found', 'glotpress' ), 200 );
		}

		if ( ! $this->verify_nonce( 'delete-glossary-entry_' . $glossary_entry->id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$glossary        = GP::$glossary->get( $glossary_entry->glossary_id );
		$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
		$can_edit        = $this->can( 'approve', 'translation-set', $translation_set->id );

		if ( ! $can_edit ) {
			return $this->die_with_error( __( 'Forbidden', 'glotpress' ), 403 );
		}

		if ( ! $glossary_entry->delete() ) {
			$this->errors[] = __( 'Error in deleting glossary entry!', 'glotpress' );
		}

		if ( ! empty( $this->errors ) ) {
			$error_output = '<ul>';
			foreach ( $this->errors as $error ) {
				$error_output .= '<li>' . $error . '</li>';
			}
			$error_output .= '</ul>';

			return $this->die_with_error( $error_output, 200 );
		}

		exit();
	}

	public function export_glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = GP::$glossary->by_set_id( $translation_set->id );

		if ( ! $glossary ) {
			return $this->die_with_404();
		}

		$glossary_entries = GP::$glossary_entry->by_glossary_id( $glossary->id );
		$filename         = sprintf( '%s-%s-glossary.csv', str_replace( '/', '-', $project->path ), $locale->slug );
		$last_modified    = gmdate( 'D, d M Y H:i:s', gp_gmt_strtotime( GP::$glossary_entry->last_modified( $glossary ) ) ) . ' GMT';

		$this->headers_for_download( $filename, $last_modified );
		$this->print_export_file( $locale->slug, $glossary_entries );
	}

	public function import_glossary_entries_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		$can_edit = $this->can( 'approve', 'translation-set', $translation_set->id );

		$this->tmpl( 'glossary-import', get_defined_vars() );
	}

	public function import_glossary_entries_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale  = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = GP::$glossary->by_set_id( $translation_set->id );

		if ( ! $glossary ) {
			return $this->die_with_404();
		}

		if ( $this->invalid_nonce_and_redirect( 'import-glossary-entries_' . $project->path . $locale_slug . $translation_set_slug ) ) {
			return;
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		if ( ! is_uploaded_file( $_FILES['import-file']['tmp_name'] ) ) {
			$this->redirect_with_error( __( 'Error uploading the file.', 'glotpress' ) );
			return;
		}

		$replace = gp_post( 'import-flush' );
		if ( 'on' === $replace && $this->can( 'approve', 'translation-set', $translation_set->id ) ) {
			GP::$glossary_entry->delete_many( array( 'glossary_id' => $glossary->id ) );
		}

		$glossary_entries_added = $this->read_glossary_entries_from_file( $_FILES['import-file']['tmp_name'], $glossary->id, $locale->slug );

		if ( empty( $this->errors ) && is_int( $glossary_entries_added ) ) {
			$this->notices[] = sprintf(
				/* translators: %s: Count number. */
				__( '%s glossary entries were added', 'glotpress' ),
				$glossary_entries_added
			);
		}

		$this->redirect( gp_url_join( gp_url_project_locale( $project->path, $locale_slug, $translation_set_slug ), array( 'glossary' ) ) );
	}

	private function print_export_file( $locale_slug, $entries ) {
		$outstream = fopen( 'php://output', 'w' );

		fputcsv( $outstream, array( 'en', $locale_slug, 'pos', 'description' ) );

		foreach ( $entries as $entry ) {
			$values = array( $entry->term, $entry->translation, $entry->part_of_speech, $entry->comment );
			fputcsv( $outstream, $values );
		}

		fclose( $outstream );
	}

	public function read_glossary_entries_from_file( $file, $glossary_id, $locale_slug ) {
		$f                = fopen( $file, 'r' );
		$glossary_entries = 0;

		$data = fgetcsv( $f, 0, ',' );

		if ( ! is_array( $data ) ) {
			return;
		} elseif ( $data[1] !== $locale_slug ) {
			$this->redirect_with_error( __( 'Unexpected values in the CSV file header row.', 'glotpress' ) );
			return;
		}

		while ( ( $data = fgetcsv( $f, 0, ',' ) ) !== false ) {
			// We're only parsing one locale per file right now
			if ( count( $data ) > 4 ) {
				$data = array_splice( $data, 2, -2 );
			}

			$entry_data = array(
				'glossary_id'    => $glossary_id,
				'term'           => $data[0],
				'translation'    => $data[1],
				'part_of_speech' => $data[2],
				'comment'        => $data[3],
				'last_edited_by' => get_current_user_id(),
			);

			$new_glossary_entry = new GP_Glossary_Entry( $entry_data );

			if ( ! $new_glossary_entry->validate() ) {
				continue;
			} else {
				$entry_exists = GP::$glossary_entry->find_one( $entry_data );
				if ( $entry_exists ) {
					continue;
				}
				$created_glossary_entry = GP::$glossary_entry->create_and_select( $new_glossary_entry );
				if ( $created_glossary_entry ) {
					$glossary_entries++;
				}
			}
		}

		fclose( $f );
		return $glossary_entries;
	}

}
