<?php
/**
 * Routes: GP_Route_Translation class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Core class used to implement the translation route.
 *
 * @since 1.0.0
 */
class GP_Route_Translation extends GP_Route_Main {

	public function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$can_import_current = $this->can( 'approve', 'translation-set', $translation_set->id );
		$can_import_waiting = $can_import_current || $this->can( 'import-waiting', 'translation-set', $translation_set->id );

		if ( ! $can_import_current && ! $can_import_waiting ) {
			$this->redirect_with_error( __( 'You are not allowed to do that!', 'glotpress' ) );
			return;
		}

		$kind = 'translations';
		$this->tmpl( 'project-import', get_defined_vars() );
	}

	public function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		if ( $this->invalid_nonce_and_redirect( 'import-translations_' . $project->id ) ) {
			return;
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$can_import_current = $this->can( 'approve', 'translation-set', $translation_set->id );
		$can_import_waiting = $can_import_current || $this->can( 'import-waiting', 'translation-set', $translation_set->id );

		if ( ! $can_import_current && ! $can_import_waiting ) {
			$this->redirect_with_error( __( 'You are not allowed to do that!', 'glotpress' ) );
			return;
		}

		$import_status = gp_post( 'status', 'waiting' );

		$allowed_import_status = array();
		if ( $can_import_current ) {
			$allowed_import_status[] = 'current';
		}
		if ( $can_import_waiting ) {
			$allowed_import_status[] = 'waiting';
		}

		if ( ! in_array( $import_status, $allowed_import_status, true ) ) {
			$this->redirect_with_error( __( 'Invalid translation status.', 'glotpress' ) );
			return;
		}

		if ( !is_uploaded_file( $_FILES['import-file']['tmp_name'] ) ) {
			$this->redirect_with_error( __( 'Error uploading the file.', 'glotpress' ) );
			return;
		}

		$format = gp_get_import_file_format( gp_post( 'format', 'po' ), $_FILES['import-file']['name'] );

		if ( ! $format ) {
			$this->redirect_with_error( __( 'No such format.', 'glotpress' ) );
			return;
		}

		$translations = $format->read_translations_from_file( $_FILES['import-file']['tmp_name'], $project );
		if ( ! $translations ) {
			$this->redirect_with_error( __( 'Couldn&#8217;t load translations from file!', 'glotpress' ) );
			return;
		}

		$translations_added = $translation_set->import( $translations, $import_status );
		$this->notices[] = sprintf( _n( '%s translation was added', '%s translations were added', $translations_added, 'glotpress' ), $translations_added );

		$this->redirect( gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) ) );
	}

	public function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$get_format = gp_get( 'format', 'po' );

		// If for some reason we were passed in an array or object from the get parameters, don't use it.
		if ( ! is_string( $get_format ) ) {
			$get_format = '.po';
		}

		$format = gp_array_get( GP::$formats, $get_format, null );

		if ( ! $format ) {
			return $this->die_with_404();
		}

		/**
		 * Filter the locale in the file name of the translation set export.
		 *
		 * @since 1.0.0
		 *
		 * @param string    $slug   Slug of the locale.
		 * @param GP_Locale $locale The current locale.
		 */
		$export_locale = apply_filters( 'gp_export_locale', $locale->slug, $locale );
		$filename = sprintf( $format->filename_pattern . '.' . $format->extension, str_replace( '/', '-', $project->path ), $export_locale );

		/**
		 * Filter the filename of the translation set export.
		 *
		 * @since 1.0.0
		 *
		 * @param string             $filename        Filename of the exported translation set.
		 * @param GP_Format          $format          Format of the export.
		 * @param GP_Locale          $locale          Locale of the export.
		 * @param GP_Project         $project         Project the translation set belongs to.
		 * @param GP_Translation_Set $translation_set The translation set to be exported.
		 */
		$filename = apply_filters( 'gp_export_translations_filename', $filename, $format, $locale, $project, $translation_set );

		$entries = GP::$translation->for_export( $project, $translation_set, gp_get( 'filters' ) );

		if ( gp_has_translation_been_updated( $translation_set ) ) {
			$last_modified = gmdate( 'D, d M Y H:i:s', gp_gmt_strtotime( GP::$translation->last_modified( $translation_set ) ) ) . ' GMT';
			$this->headers_for_download( $filename, $last_modified );

			echo $format->print_exported_file( $project, $locale, $translation_set, $entries );

		// As has_translation_been_updated() compared against HTTP_IF_MODIFIED_SINCE here, send an appropriate header.
		} else {
			$this->status_header( 304 );
		}
	}

	public function translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = $this->get_extended_glossary( $translation_set, $project );

		$page = gp_get( 'page', 1 );
		$filters = gp_get( 'filters', array() );
		$sort = gp_get( 'sort', array() );

		if ( is_array( $sort ) && 'random' === gp_array_get( $sort, 'by' ) ) {
			add_filter( 'gp_pagination', '__return_null' );
		}

		$per_page = (int) get_user_option( 'gp_per_page' );
		if ( 0 === $per_page ) {
			$per_page = GP::$translation->per_page;
		} else {
			GP::$translation->per_page = $per_page;
		}

		if ( ! is_array( $filters ) ) {
			$filters = array();
		}

		if ( ! is_array( $sort ) ) {
			$sort = array();
		}

		$translations = GP::$translation->for_translation( $project, $translation_set, $page, $filters, $sort );
		$total_translations_count = GP::$translation->found_rows;

		$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
		$can_write = $this->can( 'write', 'project', $project->id );
		$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
		$can_import_current = $can_approve;
		$can_import_waiting = $can_approve || $this->can( 'import-waiting', 'translation-set', $translation_set->id );
		$url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) );
		$set_priority_url = gp_url( '/originals/%original-id%/set_priority');
		$discard_warning_url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, '-discard-warning' ) );
		$set_status_url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, '-set-status' ) );
		$bulk_action = gp_url_join( $url, '-bulk' );

		// Add action to use different font for translations
		add_action( 'gp_head', function() use ( $locale ) {
			return gp_preferred_sans_serif_style_tag( $locale );
		} );

		$this->tmpl( 'translations', get_defined_vars() );
	}

	public function translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$original_id = gp_post( 'original_id' );

		if ( ! $this->verify_nonce( 'add-translation_' . $original_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		$this->can_or_forbidden( 'edit', 'translation-set', $translation_set->id );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = $this->get_extended_glossary( $translation_set, $project );

		$output = array();
		foreach( gp_post( 'translation', array() ) as $original_id => $translations) {
			$data = compact('original_id');
			$data['user_id'] = get_current_user_id();
			$data['translation_set_id'] = $translation_set->id;

			// Reduce range by one since we're starting at 0, see GH#516.
			foreach ( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) - 1 ) as $i ) {
				if ( isset( $translations[ $i ] ) ) {
					$data[ "translation_$i" ] = $translations[ $i ];
				}
			}

			if ( isset( $data['status'] ) ) {
				$set_status = $data['status'];
			} else {
				$set_status = 'waiting';
			}

			$data['status'] = 'waiting';

			if ( $this->can( 'approve', 'translation-set', $translation_set->id ) || $this->can( 'write', 'project', $project->id ) ) {
				$set_status = 'current';
			} else {
				$set_status = 'waiting';
			}

			$original = GP::$original->get( $original_id );
			$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );


			$existing_translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('original_id' => $original_id, 'status' => 'current_or_waiting' ), array() );
			foreach( $existing_translations as $e ) {
				if ( array_pad( $translations, $locale->nplurals, null ) == $e->translations ) {
					return $this->die_with_error( __( 'Identical current or waiting translation already exists.', 'glotpress' ), 200 );
				}
			}

			$translation = GP::$translation->create( $data );

			if ( ! $translation ) {
				return $this->die_with_error( __( 'Error in saving the translation!', 'glotpress' ) );
			}

			if ( ! $translation->validate() ) {
				$error_output = '<ul>';
				foreach ($translation->errors as $error) {
					$error_output .= '<li>' . $error . '</li>';
				}
				$error_output .= '</ul>';
				$translation->delete();

				return $this->die_with_error( $error_output, 200 );
			}
			else {
				if ( 'current' === $set_status ) {
					$translation->set_status( 'current' );
				}

				$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id), array() );

				if ( ! empty( $translations ) ) {
					$t = $translations[0];

					$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
					$can_write = $this->can( 'write', 'project', $project->id );
					$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
					$can_approve_translation = $this->can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );

					$output[ $original_id ] = gp_tmpl_get_output( 'translation-row', get_defined_vars() );
				} else {
					$output[ $original_id ] = false;
				}
			}
		}
		echo wp_json_encode( $output );
	}

	public function bulk_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		if ( $this->invalid_nonce_and_redirect( 'bulk-actions' ) ) {
			return;
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) {
			return;
		}

		$bulk = gp_post('bulk');
		$bulk['row-ids'] = array_filter( explode( ',', $bulk['row-ids'] ) );
		if ( ! empty( $bulk['row-ids'] ) ) {
			switch( $bulk['action'] ) {
				case 'approve':
				case 'reject' :
					$this->_bulk_approve( $bulk );
					break;
				case 'fuzzy':
					$this->_bulk_fuzzy( $bulk );
					break;
				case 'set-priority':
					$this->_bulk_set_priority( $project, $bulk );
			}

			/**
			 * Bulk action for translation set allows handling of custom actions.
			 *
			 * @since 1.0.0
			 *
			 * @param GP_Project         $project         The current project.
			 * @param GP_Locale          $locale          The current locale.
			 * @param GP_Translation_Set $translation_set The current translation set.
			 * @param array              $bulk            {
			 *     The bulk action data, read from the POST.
			 *
			 *     @type string $action      Action value chosen from the drop down menu.
			 *     @type string $priority    The selected value from the priority drop down menu.
			 *     @type string $redirect_to The URL that after the bulk actions are executed the
			 *                               browser is redirected to.
			 *     @type array  $row-ids     An array of strings of row IDs.
			 * }
			 */
			do_action( 'gp_translation_set_bulk_action_post', $project, $locale, $translation_set, $bulk );
		}
		else {
			$this->errors[] = 'No translations were supplied.';
		}

		$bulk['redirect_to'] = esc_url_raw( $bulk['redirect_to'] );
		$this->redirect( $bulk['redirect_to'] );
	}

	private function _bulk_approve( $bulk ) {

		$action = $bulk['action'];

		$ok = $error = 0;
		$new_status = 'approve' == $action? 'current' : 'rejected';
		foreach( $bulk['row-ids'] as $row_id ) {
			$translation_id = gp_array_get( explode( '-', $row_id ), 1 );
			$translation = GP::$translation->get( $translation_id );
			if ( !$translation ) continue;
			if ( $translation->set_status( $new_status ) )
				$ok++;
			else
				$error++;
		}

		if ( 0 === $error) {
			$this->notices[] = 'approve' == $action?
					sprintf( _n('%d translation was approved.', '%d translations were approved.', $ok, 'glotpress' ), $ok ):
					sprintf( _n('%d translation was rejected.', '%d translations were rejected.', $ok, 'glotpress' ), $ok );
		} else {
			if ( $ok > 0 ) {
				$message = 'approve' == $action?
						sprintf( _n('Error with approving %s translation.', 'Error with approving %s translations.', $error, 'glotpress' ), $error ):
						sprintf( _n('Error with rejecting %s translation.', 'Error with rejecting %s translations.', $error, 'glotpress' ), $error );
				$message .= ' ';
				$message .= 'approve' == $action?
						sprintf( _n(
								'The remaining %s translation was approved successfully.',
								'The remaining %s translations were approved successfully.', $ok, 'glotpress' ), $ok ):
						sprintf( _n(
								'The remaining %s translation was rejected successfully.',
								'The remaining %s translations were rejected successfully.', $ok, 'glotpress' ), $ok );
				$this->errors[] = $message;
			} else {
				$this->errors[] = 'approve' == $action?
						sprintf( _n(
								'Error with approving %s translation.',
								'Error with approving all %s translation.', $error, 'glotpress' ), $error ):
						sprintf( _n(
								'Error with rejecting %s translation.',
								'Error with rejecting all %s translation.', $error, 'glotpress' ), $error );
			}
		}
	}

	/**
	 * Processes the bulk action to set translations to fuzzy.
	 *
	 * @since 2.3.0
	 *
	 * @param array $bulk The bulk data to process.
	 */
	private function _bulk_fuzzy( $bulk ) {
		$ok = $error = 0;

		foreach ( $bulk['row-ids'] as $row_id ) {
			$translation_id = gp_array_get( explode( '-', $row_id ), 1 );
			$translation = GP::$translation->get( $translation_id );

			if ( ! $translation ) {
				continue;
			}

			if ( $translation->set_status( 'fuzzy' ) ) {
				$ok++;
			} else {
				$error++;
			}
		}

		if ( 0 === $error ) {
			$this->notices[] = sprintf( _n( '%d translation was marked as fuzzy.', '%d translations were marked as fuzzy.', $ok, 'glotpress' ), $ok );
		} else {
			if ( $ok > 0 ) {
				$message = sprintf( _n( 'Error with marking %s translation as fuzzy.', 'Error with marking %s translations as fuzzy.', $error, 'glotpress' ), $error );
				$message .= ' ';
				$message .= sprintf( _n( 'The remaining %s translation was marked as fuzzy successfully.', 'The remaining %s translations were marked as fuzzy successfully.', $ok, 'glotpress' ), $ok );

				$this->errors[] = $message;
			} else {
				$this->errors[] = sprintf( _n( 'Error with marking %s translation as fuzzy.', 'Error with marking all %s translation as fuzzy.', $error, 'glotpress' ), $error );
			}
		}
	}

	private function _bulk_set_priority( $project, $bulk ) {

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ){
			return;
		}

		$ok = $error = 0;
		foreach( $bulk['row-ids'] as $row_id ) {
			$original_id = gp_array_get( explode( '-', $row_id ), 0 );
			$original = GP::$original->get( $original_id );

			if ( ! $original ) {
				continue;
			}

			$original->priority = $bulk['priority'];

			if ( ! $original->validate() ) {
				return $this->die_with_error( 'Invalid priority value!' );
			}

			if ( ! $original->save() ) {
				$error++;
			} else {
				$ok ++;
			}
		}

		if ( 0 === $error) {
			$this->notices[] = sprintf( _n( 'Priority of %d original was modified.', 'Priority of %d originals were modified.', $ok, 'glotpress' ), $ok );
		} else {
			if ( $ok > 0 ) {
				$message = sprintf( _n( 'Error modifying priority of %d original.', 'Error modifying priority of %d originals.', $error, 'glotpress' ), $error );
				$message.= sprintf( _n( 'The remaining %d original was modified successfully.', 'The remaining %d originals were modified successfully.', $ok, 'glotpress' ), $ok );

				$this->errors[] = $message;
			} else {
				$this->errors[] = sprintf( _n( 'Error modifying priority of %d original.', 'Error modifying priority of all %d originals.', $error, 'glotpress' ), $error );
			}
		}

	}

	public function discard_warning( $project_path, $locale_slug, $translation_set_slug ) {
		$index = gp_post( 'index' );
		$key = gp_post( 'key' );

		if ( ! $this->verify_nonce( 'discard-warning_' . $index . $key ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, array( $this, 'discard_warning_edit_function' ) );
	}

	public function set_status( $project_path, $locale_slug, $translation_set_slug ) {
		$status         = gp_post( 'status' );
		$translation_id = gp_post( 'translation_id' );

		if ( ! $this->verify_nonce( 'update-translation-status-' . $status . '_' . $translation_id ) ) {
			return $this->die_with_error( __( 'An error has occurred. Please try again.', 'glotpress' ), 403 );
		}

		return $this->edit_single_translation( $project_path, $locale_slug, $translation_set_slug, array( $this, 'set_status_edit_function' ) );
	}

	private function edit_single_translation( $project_path, $locale_slug, $translation_set_slug, $edit_function ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$translation = GP::$translation->get( gp_post( 'translation_id' ) );

		if ( ! $translation ) {
			return $this->die_with_error( 'Translation doesn&#8217;t exist!' );
		}

		$this->can_approve_translation_or_forbidden( $translation );

		call_user_func( $edit_function, $project, $locale, $translation_set, $translation );

		$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id, 'status' => 'either'), array() );
		if ( ! empty( $translations ) ) {
			$t = $translations[0];

			$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
			$can_write = $this->can( 'write', 'project', $project->id );
			$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
			$can_approve_translation = $this->can( 'approve', 'translation', $t->id, array( 'translation' => $t ) );

			$this->tmpl( 'translation-row', get_defined_vars() );
		} else {
			return $this->die_with_error( 'Error in retrieving translation!' );
		}
	}

	/**
	 * Discard a warning.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Project         $project         The project.
	 * @param GP_Locale          $locale          The GlotPress locale.
	 * @param GP_Translation_Set $translation_set The translation set.
	 * @param GP_Translation     $translation     The translation object.
	 */
	private function discard_warning_edit_function( $project, $locale, $translation_set, $translation ) {
		if ( ! isset( $translation->warnings[ gp_post( 'index' ) ][ gp_post( 'key' ) ] ) ) {
			return $this->die_with_error( 'The warning doesn&#8217;exist!' );
		}

		$warning = array(
			'project_id' => $project->id,
			'translation_set' =>$translation_set->id,
			'translation' => $translation->id,
			'warning' => gp_post( 'key' ),
			'user' => get_current_user_id()
		);

		/**
		 * Fires before a warning gets discarded.
		 *
		 * @since 1.0.0
		 *
		 * @param array $warning {
		 *     @type string $project_id      ID of the project.
		 *     @type string $translation_set ID of the translation set.
		 *     @type string $translation     ID of the translation.
		 *     @type string $warning         The warning key.
		 *     @type int    $user            Current user's ID.
		 * }
		 */
		do_action_ref_array( 'gp_warning_discarded', $warning );

		unset( $translation->warnings[gp_post( 'index' )][gp_post( 'key' )] );
		if ( empty( $translation->warnings[gp_post( 'index' )] ) ) {
			unset( $translation->warnings[gp_post( 'index' )] );
		}

		$res = $translation->save();

		if ( false === $res || null === $res ) {
			return $this->die_with_error( 'Error in saving the translation!' );
		}
	}

	private function set_status_edit_function( $project, $locale, $translation_set, $translation ) {
		$res = $translation->set_status( gp_post( 'status' ) );

		if ( ! $res ) {
			return $this->die_with_error( 'Error in saving the translation status!' );
		}
	}

	private function can_approve_translation_or_forbidden( $translation ) {
		$can_reject_self = (get_current_user_id() == $translation->user_id && $translation->status == "waiting");
		if ( $can_reject_self ) {
			return;
		}
		$this->can_or_forbidden( 'approve', 'translation', $translation->id, null, array( 'translation' => $translation ) );
	}

	/**
	 * Get the glossary for the translation set.
	 *
	 * This also fetches contents from a potential locale glossary, as well as from a parent project.
	 *
	 * @since 2.3.0
	 *
	 * @param  GP_Translation_Set $translation_set Translation set for which to retrieve the glossary.
	 * @param  GP_Project         $project         Project for finding potential parent projects.
	 * @return GP_Glossary Extended glossary.
	 */
	protected function get_extended_glossary( $translation_set, $project ) {
		$glossary = GP::$glossary->by_set_or_parent_project( $translation_set, $project );

		$locale_glossary_project_id = 0;
		$locale_glossary_translation_set = GP::$translation_set->by_project_id_slug_and_locale( $locale_glossary_project_id, $translation_set->slug, $translation_set->locale );

		if ( ! $locale_glossary_translation_set ) {
			return $glossary;
		}

		$locale_glossary = GP::$glossary->by_set_id( $locale_glossary_translation_set->id );

		// Return locale glossary if a project has no glossary.
		if ( false === $glossary && $locale_glossary instanceof GP_Glossary ) {
			return $locale_glossary;
		}

		if ( $glossary instanceof GP_Glossary && $locale_glossary instanceof GP_Glossary && $locale_glossary->id !== $glossary->id ) {
			$glossary->merge_with_glossary( $locale_glossary );
		}

		return $glossary;
	}
}
