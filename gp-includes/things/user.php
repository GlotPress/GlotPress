<?php
class GP_User extends GP_Thing {
	// For caching purposes
	private $projects = array();

	var $table_basename = 'users';
	var $field_names = array( 'id', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status', 'display_name' );
	var $non_updatable_attributes = array( 'ID' );

	function create( $args ) {
		global $wp_users_object;
		if ( isset( $args['id'] ) ) {
			$args['ID'] = $args['id'];
			unset( $args['id'] );
		}
		$user = $wp_users_object->new_user( $args );
		return $this->coerce( $user );
	}

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['ID'] ) ) {
			$args['id'] = $args['ID'];
			unset( $args['ID'] );
		}
		return $args;
	}

	function get( $user_or_id ) {
		global $wp_users_object;
		if ( is_object( $user_or_id ) ) $user_or_id = $user_or_id->id;
		return $this->coerce( $wp_users_object->get_user( $user_or_id ) );
	}

	function by_login( $login ) {
		global $wp_users_object;
		$user = $wp_users_object->get_user( $login, array( 'by' => 'login' ) );
		return $this->coerce( $user );
	}

	function by_email( $email ) {
		global $wp_users_object;
		$user = $wp_users_object->get_user( $email, array( 'by' => 'email' ) );
		return $this->coerce( $user );
	}

	function logged_in() {
		global $wp_auth_object;
		$coerced = $this->coerce( $wp_auth_object->get_current_user() );
		return ( $coerced && $coerced->id );
	}

	function current() {
		global $wp_auth_object;
		if ( $this->logged_in() )
			return $this->coerce( $wp_auth_object->get_current_user() );
		else
			return new GP_User( array( 'id' => 0, ) );
	}

	function logout() {
		global $wp_auth_object;
		$wp_auth_object->clear_auth_cookie();
	}

	/**
	 * Determines whether the user is an admin
	 */
	function admin() {
		return $this->can( 'admin' );
	}

	/**
	 * Set $this as the current user if $password patches this user's password
	 */
	function login( $password ) {
 		if ( !WP_Pass::check_password( $password, $this->user_pass, $this->id ) ) {
			return false;
		}
		$this->set_as_current();
		return true;
	}

	/**
	 * Makes the user the current user of this session. Sets the cookies and such.
	 */
	function set_as_current() {
		global $wp_auth_object;
		$wp_auth_object->set_current_user( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id );
		$wp_auth_object->set_auth_cookie( $this->id, 0, 0, 'logged_in');
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
		elseif ( GP::$user->logged_in() )
			$user = GP::$user->current();
		$user_id = $user? $user->id : null;
		$args = $filter_args = compact( 'user_id', 'action', 'object_type', 'object_id' );
		$filter_args['user'] = $user;
		$filter_args['extra'] = $extra;
		$preliminary = apply_filters( 'pre_can_user', 'no-verdict', $filter_args );
		if ( is_bool( $preliminary ) ) {
			return $preliminary;
		}
		$verdict =
			GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
			GP::$permission->find_one( $args ) ||
			GP::$permission->find_one( array_merge( $args, array( 'object_id' => null ) ) );
		return apply_filters( 'can_user', $verdict, $filter_args );
	}

	function get_meta( $key ) {
		global $wp_users_object;
		if ( !$user = $wp_users_object->get_user( $this->id ) ) {
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


	function reintialize_wp_users_object() {
		global $gpdb, $wp_auth_object, $wp_users_object;
		$wp_users_object = new WP_Users( $gpdb );
		$wp_auth_object->users = $wp_users_object;
	}


	public function get_avatar( $size = 100 ) {
		return '//www.gravatar.com/avatar/' . md5( strtolower( $this->user_email ) ) . '?s=' . $size;
	}

	public function get_recent_translation_sets( $amount = 5 ) {
		global $gpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT translation_set_id, date_added
			FROM $gpdb->translations as t
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
				$translation_set->count        = $gpdb->get_var( $gpdb->prepare( "SELECT COUNT(*) FROM $gpdb->translations WHERE user_id = %s AND status != 'rejected' AND translation_set_id = %s", $this->id, $translation->translation_set_id ) );

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
		global $gpdb;

		$translations = GP::$translation_set->many_no_map("
			SELECT ts.locale, count(*) AS count
			FROM $gpdb->translations as t
			INNER JOIN $gpdb->translation_sets AS ts ON ts.id = t.translation_set_id
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
