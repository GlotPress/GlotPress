<?php
class GP_Route_Profile extends GP_Route_Main {
	// For caching purposes
	private $projects = array();

	function profile_get() {
		if ( ! is_user_logged_in() ) {
			$this->redirect( wp_login_url( gp_url_profile() ) );
			return;
		}

		$this->tmpl( 'profile' );
	}

	function profile_post() {
		if ( isset( $_POST['submit'] ) ) {
			$per_page = (int) $_POST['per_page'];
			update_user_option( get_current_user_id(), 'gp_per_page', $per_page );

			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc'
			);
			$user_sort = wp_parse_args( $_POST['default_sort'], $default_sort );
			update_user_option( get_current_user_id(), 'gp_default_sort', $user_sort );
		}

		$this->redirect( gp_url( '/profile' ) );
	}

	public function profile_view( $user ) {
		$user = get_user_by( 'slug', $user );

		if ( ! $user ) {
			return $this->die_with_404();
		}

		$recent_projects = $this->get_recent_translation_sets( $user, 5 );
		$locales         = $this->locales_known( $user );

		//validate to
		$permissions = $this->get_permissions( $user );

		$this->tmpl( 'profile-public', get_defined_vars() );
	}

	private function get_recent_translation_sets( $user, $amount = 5 ) {
		global $wpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT translation_set_id, date_added
			FROM $wpdb->gp_translations as t
			WHERE
				date_added >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND
				user_id = %s AND
				status != 'rejected'
			ORDER BY date_added DESC
		", $user->ID );

		$set_ids          = array();
		$translation_sets = array();

		$i = 0;
		foreach ( $translations as $translation ) {
			if ( in_array( $translation->translation_set_id, $set_ids ) ) {
				continue;
			}

			$set_ids[] = $translation->translation_set_id;

			$set = GP::$translation_set->find_one( array( 'id' => (int) $translation->translation_set_id ) );

			if ( $set ) {
				$translation_set = $this->get_translation_set( $set );

				if ( ! $translation_set ) {
					continue;
				}

				$translation_set->set_id       = $set->id;
				$translation_set->last_updated = $translation->date_added;
				$translation_set->count        = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->gp_translations WHERE user_id = %s AND status != 'rejected' AND translation_set_id = %s", $user->ID, $translation->translation_set_id ) );

				$translation_sets[] = $translation_set;

				$i++;

				// Bail early if we have already the amount requested
				if ( $i >= $amount ) {
					break;
				}
			}
		}

		return $translation_sets;
	}

	private function locales_known( $user ) {
		global $wpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT ts.locale, count(*) AS count
			FROM $wpdb->gp_translations as t
			INNER JOIN $wpdb->gp_translation_sets AS ts ON ts.id = t.translation_set_id
			WHERE user_id = %s
			GROUP BY ts.locale
			ORDER BY count DESC
		", $user->ID );

		$locales = array();

		foreach ( $translations as $data ) {
			$locale = GP_Locales::by_slug( $data->locale );

			$locales[ $locale->english_name ] = array(
				'locale' => $data->locale,
				'count'  => (int) $data->count,
			);
		}

		return $locales;
	}

	/**
	 * Retrieve a users permissions.
	 *
	 * @return array Array of permissions
	 */
	private function get_permissions( $user ) {
		$permissions = GP::$permission->find_many_no_map( array( 'user_id' => $user->ID, 'action' => 'approve' ) );

		foreach ( $permissions as $key => &$permission ) {
			$object_id = GP::$validator_permission->project_id_locale_slug_set_slug( $permission->object_id );

			// Skip admin permissions
			if ( ! isset(  $object_id[1] ) ) {
				unset( $permissions[$key] );
				continue;
			}

			$set = GP::$translation_set->find_one(
				array(
					'project_id' => $object_id[0],
					'locale' => $object_id[1],
					'slug' => $object_id[2]
				)
			);

			// Skip permissions for non existing sets
			if ( ! $set ) {
				unset( $permissions[$key] );
				continue;
			}

			unset( $permission->id, $permission->action, $permission->object_type, $permission->object_id );

			$translation_set = $this->get_translation_set( $set );

			if ( $set && $translation_set ) {
				$permission = (object) array_merge( (array) $permission, (array) $translation_set );
				$permission->set_id = $set->id;
			} else {
				unset( $permissions[$key] );
			}
		}

		return $permissions;
	}

	private function get_translation_set( $set ) {
		if ( ! isset( $this->projects[ $set->project_id ] ) ) {
			 $this->projects[ $set->project_id ] = GP::$project->get( $set->project_id );
		}

		$project = $this->projects[$set->project_id];

		if ( ! $project ) {
			return false;
		}

		$project_url = gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) );
		$set_name = gp_project_names_from_root( $project ) . ' | ' . $set->name_with_locale();

		return (object) array(
			'project_id' => $project->id,
			'project_url' => $project_url,
			'set_name' => $set_name
		);
	}
}
