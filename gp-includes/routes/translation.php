<?php
class GP_Route_Translation extends GP_Route_Main {

	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

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

		$kind = 'translations';
		$this->tmpl( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

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

		$format = gp_array_get( GP::$formats, gp_post( 'format', 'po' ), null );

		if ( ! $format ) {
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

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$format = gp_array_get( GP::$formats, gp_get( 'format', 'po' ), null );

		if ( ! $format ) {
			return $this->die_with_404();
		}

		$export_locale = apply_filters( 'export_locale', $locale->slug, $locale );
		$filename = sprintf( '%s-%s.'.$format->extension, str_replace( '/', '-', $project->path ), $export_locale );
		$filename = apply_filters( 'export_translations_filename', $filename, $format, $locale, $project, $translation_set ); 

		$entries = GP::$translation->for_export( $project, $translation_set, gp_get( 'filters' ) );

		if ( gp_has_translation_been_updated( $translation_set ) ) {
			$last_modified = gmdate( 'D, d M Y H:i:s', backpress_gmt_strtotime( GP::$translation->last_modified( $translation_set ) ) ) . ' GMT';
			$this->headers_for_download( $filename, $last_modified );

			echo $format->print_exported_file( $project, $locale, $translation_set, $entries );

		// As has_translation_been_updated() compared against HTTP_IF_MODIFIED_SINCE here, send an appropriate header.
		} else {
			$this->header( 'HTTP/1.1 304 Not Modified' );
		}
	}

	function translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$glossary = GP::$glossary->by_set_or_parent_project( $translation_set, $project );

		$page = gp_get( 'page', 1 );
		$filters = gp_get( 'filters', array() );
		$sort = gp_get( 'sort', array() );

		if ( 'random' == gp_array_get( $sort, 'by') ) {
			add_filter( 'gp_pagination', '__return_null' );
		}

		$per_page = GP::$user->current()->get_meta('per_page');
		if ( 0 == $per_page )
			$per_page = GP::$translation->per_page;
		else
			GP::$translation->per_page = $per_page;

		$translations = GP::$translation->for_translation( $project, $translation_set, $page, $filters, $sort );
		$total_translations_count = GP::$translation->found_rows;

		$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
		$can_write = $this->can( 'write', 'project', $project->id );
		$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
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

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		$this->can_or_forbidden( 'edit', 'translation-set', $translation_set->id );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		$output = array();
		foreach( gp_post( 'translation', array() ) as $original_id => $translations) {
			$data = compact('original_id');
			$data['user_id'] = GP::$user->current()->id;
			$data['translation_set_id'] = $translation_set->id;

			foreach( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) ) as $i ) {
				if ( isset( $translations[$i] ) ) $data["translation_$i"] = $translations[$i];
			}

			if ( $this->can( 'approve', 'translation-set', $translation_set->id ) || $this->can( 'write', 'project', $project->id ) )
				$data['status'] = 'current';
			else
				$data['status'] = 'waiting';

			$original = GP::$original->get( $original_id );
			$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );


			$existing_translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('original_id' => $original_id, 'status' => 'current_or_waiting' ), array() );
			foreach( $existing_translations as $e ) {
				if ( array_pad( $translations, $locale->nplurals, null ) == $e->translations ) {
					return $this->die_with_error( __( 'Identical current or waiting translation already exists.' ), 200 );
				}
			}

			$translation = GP::$translation->create( $data );
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
				if ( 'current' == $data['status'] ) {
					$translation->set_status( 'current' );
				}

				$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id), array() );

				if ( $translations ) {
					$t = $translations[0];
					$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
					$can_write = $this->can( 'write', 'project', $project->id );
					$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
					$output[$original_id] = gp_tmpl_get_output( 'translation-row', get_defined_vars() );
				}
				else {
					$output[$original_id] = false;
				}
			}
		}
		echo gp_json_encode( $output );
	}

	function bulk_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );

		if ( ! $project || ! $locale ) {
			return $this->die_with_404();
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );

		if ( ! $translation_set ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'approve', 'translation-set', $translation_set->id ) ) return;

		$bulk = gp_post('bulk');
		$bulk['row-ids'] = array_filter( explode( ',', $bulk['row-ids'] ) );
		if ( ! empty( $bulk['row-ids'] ) ) {
			switch( $bulk['action'] ) {
				case 'approve':
				case 'reject' :
					$this->_bulk_approve( $project, $locale, $translation_set, $bulk );
					break;
				case 'set-priority':
					$this->_bulk_set_priority( $project, $locale, $translation_set, $bulk );
			}

			do_action( 'gp_translation_set_bulk_action_post', $project, $locale, $translation_set, $bulk );
		}
		else {
			$this->errors[] = 'No translations were supplied.';
		}

		// hack, until we make clean_url() to allow [ and ]
		$bulk['redirect_to'] = str_replace( array('[', ']'), array_map('urlencode', array('[', ']')), $bulk['redirect_to']);
		$this->redirect( $bulk['redirect_to'] );
	}

	function _bulk_approve( $project, $locale, $translation_set, $bulk ) {

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

	function _bulk_set_priority( $project, $locale, $translation_set, $bulk ) {

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
			$this->notices[] = sprintf( _n( 'Priority of one original modified.', 'Priority of %d originals modified.', $ok ), $ok );
		} else {
			if ( $ok > 0 ) {
				$message = sprintf( _n( 'Error modifying priority of one original.', 'Error modifying priority of %d originals.', $error ), $error );
				$message.= sprintf( _n( 'The remaining original was modified successfully.', 'The remaining %d originals were modified successfully.', $ok ), $ok );

				$this->errors[] = $message;
			} else {
				$this->errors[] = sprintf( _n( 'Error modifying priority of the original.', 'Error modifying priority of all %d originals.', $error ), $error );
			}
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
		if ( $translations ) {
			$t = $translations[0];
			$can_edit = $this->can( 'edit', 'translation-set', $translation_set->id );
			$can_write = $this->can( 'write', 'project', $project->id );
			$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
			$this->tmpl( 'translation-row', get_defined_vars() );
		} else {
			return $this->die_with_error( 'Error in retrieving translation!' );
		}
	}

	private function discard_warning_edit_function( $project, $locale, $translation_set, $translation ) {
		if ( ! isset( $translation->warnings[ gp_post( 'index' ) ][ gp_post( 'key' ) ] ) ) {
			return $this->die_with_error( 'The warning doesn&#8217;exist!' );
		}

		$warning = array(
			'project_id' => $project->id,
			'translation_set' =>$translation_set->id,
			'translation' => $translation->id,
			'warning' => gp_post( 'key' ),
			'user' => GP::$user->current()->id
		);
		do_action_ref_array( 'warning_discarded', $warning );

		unset( $translation->warnings[gp_post( 'index' )][gp_post( 'key' )] );
		if ( empty( $translation->warnings[gp_post( 'index' )] ) ) {
			unset( $translation->warnings[gp_post( 'index' )] );
		}

		$res = $translation->save();

		if ( ! $res ) {
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
		$can_reject_self = (GP::$user->current()->id == $translation->user_id && $translation->status == "waiting");
		if ( $can_reject_self ) {
			return;
		}
		$this->can_or_forbidden( 'approve', 'translation-set', $translation->translation_set_id );
	}
}
