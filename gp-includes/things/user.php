<?php
class GP_User extends GP_Thing {
	// For caching purposes
	private $projects = array();

	var $table_basename = 'users';
	var $field_names = array( 'id', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name' );
	var $non_updatable_attributes = array( 'ID' );

	function current() {
		if ( is_user_logged_in() )
			return $this->coerce( wp_get_current_user() );
		else
			return new GP_User( array( 'id' => 0, ) );
	}

	/**
	 * Determines whether the user is an admin
	 */
	function admin() {
		return $this->can( 'admin' );
	}

	/**
	 * Makes the user the current user of this session.
	 */
	function set_as_current() {
		wp_set_current_user( $this->id );
	}

	/**
	 * Determines whether the user can do $action on the instance of $object_type with id $object_id.
	 *
	 * If the method is called statically, it uses the current session user.
	 *
	 * Example: $user->can( 'read', 'translation-set', 11 );
	 */
	function can( $action, $object_type = null, $object_id = null, $extra = null ) {
		$user = null;
		if ( isset( $this ) && $this->id )
			$user = $this;
		elseif ( is_user_logged_in() )
			$user = GP::$user->current();
		$user_id = $user? $user->id : null;
		$args = $filter_args = compact( 'user_id', 'action', 'object_type', 'object_id' );
		$filter_args['user'] = $user;
		$filter_args['extra'] = $extra;
		$preliminary = apply_filters( 'gp_pre_can_user', 'no-verdict', $filter_args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}
		$verdict =
			GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
			GP::$permission->find_one( $args ) ||
			GP::$permission->find_one( array_merge( $args, array( 'object_id' => null ) ) );
		return apply_filters( 'gp_can_user', $verdict, $filter_args );
	}

	function get_meta( $key ) {
		if ( !$user = get_userdata( $this->id ) ) {
			return;
		}

		if ( !isset( $user->$key ) ) {
			return;
		}
		return $user->$key;
	}

	function set_meta( $key, $value ) {
		return gp_update_meta( $this->id, $key, $value, 'user' );
	}

	function delete_meta( $key ) {
		return gp_delete_meta( $this->id, $key, '', 'user' );
	}

	public function sort_defaults() {
		$defaults = $this->get_meta('default_sort');

		if ( ! is_array( $defaults ) ) {
			$defaults = array(
				'by' => 'priority',
				'how' => 'desc'
			);
		}

		return $defaults;
	}

	public function get_avatar( $size = 100 ) {
		return '//www.gravatar.com/avatar/' . md5( strtolower( $this->user_email ) ) . '?s=' . $size;
	}

	public function get_recent_translation_sets( $amount = 5 ) {
		global $wpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT translation_set_id, date_added
			FROM $wpdb->gp_translations as t
			WHERE
				date_added >= DATE_SUB(NOW(), INTERVAL 2 MONTH) AND
				user_id = %s AND
				status != 'rejected'
			ORDER BY date_added DESC
		", $this->id );

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
				$translation_set->count        = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->gp_translations WHERE user_id = %s AND status != 'rejected' AND translation_set_id = %s", $this->id, $translation->translation_set_id ) );

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

	public function locales_known() {
		global $wpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT ts.locale, count(*) AS count
			FROM $wpdb->gp_translations as t
			INNER JOIN $wpdb->gp_translation_sets AS ts ON ts.id = t.translation_set_id
			WHERE user_id = %s
			GROUP BY ts.locale
			ORDER BY count DESC
		", $this->id );

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
	public function get_permissions() {
		$permissions = GP::$permission->find_many_no_map( array( 'user_id' => $this->id, 'action' => 'approve' ) );

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
GP::$user = new GP_User();
