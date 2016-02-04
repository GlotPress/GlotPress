<?php
class GP_Route_Project extends GP_Route_Main {

	public function index() {
		$title = __( 'Projects', 'glotpress' );
		$projects = GP::$project->top_level();
		$this->tmpl( 'projects', get_defined_vars() );
	}

	public function single( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		$sub_projects = $project->sub_projects();
		$translation_sets = GP::$translation_set->by_project_id( $project->id );

		foreach( $translation_sets as $set ) {
			$locale = GP_Locales::by_slug( $set->locale );

			$set->name_with_locale = $set->name_with_locale();
			$set->current_count = $set->current_count();
			$set->untranslated_count = $set->untranslated_count();
			$set->waiting_count = $set->waiting_count();
			$set->fuzzy_count = $set->fuzzy_count();
			$set->percent_translated = $set->percent_translated();
			$set->all_count = $set->all_count();
			$set->wp_locale = $locale->wp_locale;
			if ( $this->api ) {
				$set->last_modified = $set->current_count ? $set->last_modified() : false;
			}
		}

		usort( $translation_sets, function( $a, $b ) {
			return( $a->current_count < $b->current_count );
		});
		$translation_sets = apply_filters( 'gp_translation_sets_sort', $translation_sets );

		$title = sprintf( __( '%s project ', 'glotpress' ), esc_html( $project->name ) );
		$can_write = $this->can( 'write', 'project', $project->id );
		$this->tmpl( 'project', get_defined_vars() );
	}

	public function personal_options_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$source_url_templates = get_user_meta( get_current_user_id(), 'gp_source_url_templates', true );
		if ( !is_array( $source_url_templates ) ) $source_url_templates = array();
		$source_url_templates[$project->id] = gp_post( 'source-url-template' );
		if ( update_user_meta( get_current_user_id(), 'gp_source_url_templates', $source_url_templates ) ) {
			$this->notices[] = 'Source URL template was successfully updated.';
		} else {
			$this->errors[] = 'Error in updating source URL template.';
		}
		$this->redirect( gp_url_project( $project ) );
	}

	public function import_originals_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

 		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$kind = 'originals';
		$this->tmpl( 'project-import', get_defined_vars() );
	}

	public function import_originals_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$format = gp_array_get( GP::$formats, gp_post( 'format', 'po' ), null );

		if ( ! $format ) {
			$this->redirect_with_error( __( 'No such format.', 'glotpress' ) );
			return;
		}


		if ( ! is_uploaded_file( $_FILES['import-file']['tmp_name'] ) ) {
			// TODO: different errors for different upload conditions
			$this->redirect_with_error( __( 'Error uploading the file.', 'glotpress' ) );
			return;
		}

		$translations = $format->read_originals_from_file( $_FILES['import-file']['tmp_name'] );

		if ( ! $translations ) {
			$this->redirect_with_error( __( 'Couldn&#8217;t load translations from file!', 'glotpress' ) );
			return;
		}

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = GP::$original->import_for_project( $project, $translations );
		$this->notices[] = sprintf(
			__( '%1$s new strings added, %2$s updated, %3$s fuzzied, and %4$s obsoleted.', 'glotpress' ),
			$originals_added,
			$originals_existing,
			$originals_fuzzied,
			$originals_obsoleted
		);

		$this->redirect( gp_url_project( $project ) );
	}

	public function edit_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$this->tmpl( 'project-edit', get_defined_vars() );
	}

	public function edit_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( !$project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$updated_project = new GP_Project( gp_post( 'project' ) );
		if ( $this->invalid_and_redirect( $updated_project, gp_url_project( $project, '-edit' ) ) ) {
			return;
		}

		// TODO: add id check as a validation rule
		if ( $project->id == $updated_project->parent_project_id ) {
			$this->errors[] = __( 'The project cannot be parent of itself!', 'glotpress' );
		}
		elseif ( $project->save( $updated_project ) ) {
			$this->notices[] = __( 'The project was saved.', 'glotpress' );
		}
		else {
			$this->errors[] = __( 'Error in saving project!', 'glotpress' );
		}

		$project->reload();

		$this->redirect( gp_url_project( $project ) );
	}

	public function delete_get( $project_path ) {
		// TODO: do not delete using a GET request but POST
		// TODO: decide what to do with child projects and translation sets
		// TODO: just deactivate, do not actually delete
		$project = GP::$project->by_path( $project_path );

		if ( !$project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		if ( $project->delete() ) {
			$this->notices[] = __( 'The project was deleted.', 'glotpress' );
		}
		else {
			$this->errors[] = __( 'Error in deleting project!', 'glotpress' );
		}

		$this->redirect( gp_url_project( '' ) );
	}


	public function new_get() {
		$project = new GP_Project();
		$project->parent_project_id = gp_get( 'parent_project_id', null );

		if ( $this->cannot_and_redirect( 'write', 'project', $project->parent_project_id ) ) {
			return;
		}

		$this->tmpl( 'project-new', get_defined_vars() );
	}

	public function new_post() {
		$post = gp_post( 'project' );
		$parent_project_id = gp_array_get( $post, 'parent_project_id', null );

		if ( $this->cannot_and_redirect( 'write', 'project', $parent_project_id ) ) {
			return;
		}

		$new_project = new GP_Project( $post );

		if ( $this->invalid_and_redirect( $new_project ) ) {
			return;
		}

		$project = GP::$project->create_and_select( $new_project );

		if ( ! $project ) {
			$project = new GP_Project();
			$this->errors[] = __( 'Error in creating project!', 'glotpress' );
			$this->tmpl( 'project-new', get_defined_vars() );
		} else {
			$this->notices[] = __( 'The project was created!', 'glotpress' );
			$this->redirect( gp_url_project( $project ) );
		}
	}

	public function permissions_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$path_to_root = array_slice( $project->path_to_root(), 1 );
		$permissions = GP::$validator_permission->by_project_id( $project->id );
		$cmp_fn = function( $x, $y ){
			return strcmp( $x->locale_slug, $y->locale_slug );
		};
		usort( $permissions, $cmp_fn );
		$parent_permissions = array();

		foreach( $path_to_root as $parent_project ) {
			$this_parent_permissions = GP::$validator_permission->by_project_id( $parent_project->id );
			usort( $this_parent_permissions, $cmp_fn );
			foreach( $this_parent_permissions as $permission ) {
				$permission->project = $parent_project;
			}
			$parent_permissions = array_merge( $parent_permissions, (array)$this_parent_permissions );
		}
		// we can't join on users table
		foreach( array_merge( (array)$permissions, (array)$parent_permissions ) as $permission ) {
			$permission->user = get_user_by( 'id', $permission->user_id );
		}
		$this->tmpl( 'project-permissions', get_defined_vars() );
	}

	public function permissions_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		if ( 'add-validator' == gp_post( 'action' ) ) {
			$user = get_user_by( 'login', gp_post( 'user_login' ) );
			if ( !$user ) {
				$this->redirect_with_error( __( 'User wasn&#8217;t found!', 'glotpress' ), gp_url_current() );
				return;
			}
			$new_permission = new GP_Validator_Permission( array(
				'user_id' => $user->ID,
				'action' => 'approve',
				'project_id' => $project->id,
				'locale_slug' => gp_post( 'locale' ),
				'set_slug' => gp_post( 'set-slug' ),
			) );
			if ( $this->invalid_and_redirect( $new_permission, gp_url_current() ) ) return;
			$permission = GP::$validator_permission->create( $new_permission );
			$permission?
				$this->notices[] = __( 'Validator was added.', 'glotpress' ) : $this->errors[] = __( 'Error in adding validator.', 'glotpress' );
		}
		$this->redirect( gp_url_current() );
	}

	public function permissions_delete( $project_path, $permission_id ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			$this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$permission = GP::$permission->get( $permission_id );
		if ( $permission ) {
			if ( $permission->delete() ) {
				$this->notices[] = __( 'Permission was deleted.', 'glotpress' );
			} else {
				$this->errors[] = __( 'Error in deleting permission!', 'glotpress' );
			}
		} else {
			$this->errors[] = __( 'Permission wasn&#8217;t found!', 'glotpress' );
		}
		$this->redirect( gp_url_project( $project, '-permissions' ) );
	}

	public function mass_create_sets_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$this->tmpl( 'project-mass-create-sets', get_defined_vars() );
	}

	public function mass_create_sets_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$other_project = GP::$project->get( gp_post( 'project_id' ) );

		if ( ! $other_project ) {
			return $this->die_with_error( __( 'Project wasn&#8217;found', 'glotpress' ) );
		}

		$changes = $project->set_difference_from( $other_project );

		foreach( $changes['added'] as $to_add ) {
			if ( !GP::$translation_set->create( array('project_id' => $project->id, 'name' => $to_add->name, 'locale' => $to_add->locale, 'slug' => $to_add->slug) ) ) {
				$this->errors[] = sprintf( __( 'Couldn&#8217;t add translation set named %s', 'glotpress' ), esc_html( $to_add->name ) );
			}
		}
		foreach( $changes['removed'] as $to_remove ) {
			if ( !$to_remove->delete() ) {
				$this->errors[] = sprintf( __( 'Couldn&#8217;t delete translation set named %s', 'glotpress' ), esc_html( $to_remove->name ) );
			}
		}
		if ( !$this->errors ) $this->notices[] = __( 'Translation sets were added and removed successfully', 'glotpress' );
		$this->redirect( gp_url_project( $project ) );
	}

	public function mass_create_sets_preview_post( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$other_project = GP::$project->get( gp_post( 'project_id' ) );

		if ( ! $other_project ) {
			return $this->die_with_error( __( 'Project wasn&#8217;found', 'glotpress' ) );
		}

		header('Content-Type: application/json');
		echo wp_json_encode( $project->set_difference_from( $other_project ) );
	}

	public function branch_project_get( $project_path ) {
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		if ( $this->cannot_and_redirect( 'write', 'project', $project->id ) ) {
			return;
		}

		$this->tmpl( 'project-branch', get_defined_vars() );
	}


	public function branch_project_post( $project_path ) {
		$post = gp_post( 'project' );
		$project = GP::$project->by_path( $project_path );

		if ( ! $project ) {
			return $this->die_with_404();
		}

		$parent_project_id = gp_array_get( $post, 'parent_project_id', null );

		if ( $this->cannot_and_redirect( 'write', 'project', $parent_project_id ) ) {
			return;
		}

		$new_project_data = new GP_Project( $post );
		if ( $this->invalid_and_redirect( $new_project_data ) ){
			return;
		}

		$new_project_data->active = $project->active;
		$new_project = GP::$project->create_and_select( $new_project_data );

		if ( !$new_project ) {
			$new_project = new GP_Project();
			$this->errors[] = __( 'Error in creating project!', 'glotpress' );
			$this->tmpl( 'project-branch', get_defined_vars() );
		} else {
			$new_project->duplicate_project_contents_from( $project );
		}

		$this->redirect( gp_url_project( $new_project ) );
	}

}
