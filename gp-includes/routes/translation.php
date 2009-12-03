<?php
class GP_Route_Translation extends GP_Route_Main {	
	function import_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'approve', 'translation-set', $translation_set->id );
		$kind = 'translations';
		gp_tmpl_load( 'project-import', get_defined_vars() );
	}

	function import_translations_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'approve', 'translation-set', $translation_set->id );
		$block = array( &$this, '_merge_translations');
		self::_import( 'mo-file', 'MO', $block, array($translation_set) ) or
		self::_import( 'pot-file', 'PO', $block, array($translation_set) );
	
		gp_redirect( gp_url_project( $project, array( $locale->slug, $translation_set->slug, 'import-translations' ) ) );
	}
	
	function _merge_translations( $translation_set, $translations ) {
		$translations_added = $translation_set->import( $translations );
		$this->notices[] = sprintf(__("%s translations were added"), $translations_added );
	}
	
	function export_translations_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();

		$filename = sprintf( '%s-%s.po', str_replace( '/', '-', $project->path ), $locale->wp_locale );
		$this->headers_for_download( $filename );
				
		echo "# Translation of {$project->name} in {$locale->english_name}\n";
		echo "# This file is distributed under the same license as the {$project->name} package.\n";
		echo $translation_set->export_as_po();
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
		$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
		$url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug ) );
		$discard_warning_url = gp_url_project( $project, gp_url_join( $locale->slug, $translation_set->slug, '_discard-warning' ) );
		$approve_action = gp_url_join( $url, '_approve' );
		gp_tmpl_load( 'translations', get_defined_vars() );
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
		    foreach( range(0, 3) as $i ) {
		        if ( isset( $translations[$i] ) ) $data["translation_$i"] = $translations[$i];
		    }
			if ( $this->can( 'approve', 'translation-set', $translation_set->id ) || $this->can( 'write', 'project', $project->id ) ) {
				$data['status'] = 'current';
			} else {
				$data['status'] = 'waiting';
			}
			$original = GP::$original->get( $original_id );
			$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );
			//if ( is_array( $warnings) ) $data['warnings'] = serialize( $warnings );
			// TODO: validate
			$translation = GP::$translation->create( $data );
			if ( 'current' == $data['status'] ) {
				$translation->set_as_current();
			}
			$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit', array('translation_id' => $translation->id), array() );
			if ( $translations ) {
				$t = $translations[0];
				$parity = returner( 'even' );
				$can_edit = GP::$user->logged_in();
				$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
				$output[$original_id] = gp_tmpl_get_output( 'translation-row', get_defined_vars() );
			} else {
				$output[$original_id] = false;
			}
		}
		echo json_encode( $output );
	}
	
	function approve_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'approve', 'translation-set', $translation_set->id );
		
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

		// TODo: refactor this, out in another method
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
		gp_redirect( $bulk['redirect_to'] );
	}

	function permissions_post( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		if ( 'add-approver' == gp_post( 'action' ) ) {
			$user = GP::$user->by_login( gp_post( 'user_login' ) );
			if ( $user ) {
				$res = GP::$permission->create( array(
					'user_id' => $user->id,
					'action' => 'approve',
					'object_type' => 'translation-set',
					'object_id' => $translation_set->id,
				) );
				$res?
					$this->notices[] = 'Validator was added.' :
					$this->errors[] = 'Error in adding validator.';
			} else {
				$this->errors[] = 'User wasn&#8217;t found!';
			}
		}
		gp_redirect( gp_url_current() );
	}
	
	function permissions_get( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$permissions = GP::$permission->by_translation_set_id( $translation_set->id );
		// we can't join on users table
		foreach( (array)$permissions as $permission ) {
			$permission->user = GP::$user->get( $permission->user_id );
		}
		gp_tmpl_load( 'translation-set-permissions', get_defined_vars() );
	}
	
	function permissions_delete( $project_path, $locale_slug, $translation_set_slug, $permission_id ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_redirect( 'write', 'project', $project->id );
		$permission = GP::$permission->get( $permission_id );
		if ( $permission ) {
			if ( $permission->delete() ) {
				$this->notices[] = 'Permissin was deleted.';
			} else {
				$this->errors[] = 'Error in deleting permission!';
			}
		} else {
			$this->errors[] = 'Permission wasn&#8217;t found!';
		}
		gp_redirect( gp_url_project( $project, array( $locale->slug, $translation_set->slug, '_permissions' ) ) );
	}
	
	function discard_warning( $project_path, $locale_slug, $translation_set_slug ) {
		$project = GP::$project->by_path( $project_path );
		$locale = GP_Locales::by_slug( $locale_slug );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( !$project || !$locale || !$translation_set ) gp_tmpl_404();
		$this->can_or_forbidden( 'write', 'project', $project->id );
		
		$translation = GP::$translation->get( gp_post( 'translation_id' ) );
		if ( !$translation ) {
			$this->die_with_error( 'Translation doesn&#8217;t exist!' );
		}
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
		
		$translations = GP::$translation->for_translation( $project, $translation_set, 'no-limit',
															array('translation_id' => gp_post( 'translation_id' ) ), array() );
		if ( $translations ) {
			$t = $translations[0];
			$parity = returner( 'even' );
			$can_edit = GP::$user->logged_in();
			$can_approve = $this->can( 'approve', 'translation-set', $translation_set->id );
			gp_tmpl_load( 'translation-row', get_defined_vars() );
		} else {
			$this->die_with_error( 'Error in retrieving translation!' );
		}
	}
	
}
