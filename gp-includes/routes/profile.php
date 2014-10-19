<?php
class GP_Route_Profile extends GP_Route_Main {
	private $projects = array();

	function profile_get() {
		if ( !GP::$user->logged_in() ) {
			$this->redirect( gp_url( '/login?redirect_to=' ).urlencode( gp_url( '/profile') ) );
			return;
		}

		$this->tmpl( 'profile' );
	}

	function profile_post() {
		if ( isset( $_POST['submit'] ) ) {
			$per_page = (int) $_POST['per_page'];
			GP::$user->current()->set_meta( 'per_page', $per_page );

			$default_sort = $_POST['default_sort'];
			GP::$user->current()->set_meta( 'default_sort', $default_sort );
		}

		$this->redirect( gp_url( '/profile' ) );
	}

	public function profile_view( $user ) {
		$user = GP::$user->find_one( array( 'user_nicename' => $user ) );

		if ( ! $user ) {
			return $this->die_with_404();
		}

		$projects    = $user->get_recent_projects();
		$locales = $user->locales_known();

		$_recent_projects = array_slice( $projects, 0, 5 );

		//recent projects
		$recent_actions = array();
		foreach ( $_recent_projects as $recent_project ) {
			$set = GP::$translation_set->find_one( array( 'id' => (int) $recent_project->id ) );

			if ( $set ) {
				$action = $this->get_project( $set );
				$action->set_id = $set->id;
				$action->human_time = $this->time_since( strtotime( $recent_project->last_updated ) );
				$action->last_updated = $recent_project->last_updated;
				$action->count = $recent_project->count;
				$recent_projects[] = $action;
			}
		}

		//validate to
		$permissions = GP::$permission->find_many_no_map( array( 'user_id' => $user->id, 'action' => 'approve' ) );
		foreach ( $permissions as $key => &$permission ) {
			$object_id = GP::$validator_permission->project_id_locale_slug_set_slug( $permission->object_id );
			$set = GP::$translation_set->find_one(
				array(
					'project_id' => $object_id[0],
					'locale' => $object_id[1],
					'slug' => $object_id[2]
				)
			);

			unset( $permission->id, $permission->action, $permission->object_type, $permission->object_id );

			if ( $set ) {
				$permission = (object) array_merge( (array) $permission, (array) $this->get_project( $set ) );
				$permission->set_id = $set->id;
			} else {
				unset( $permissions[$key] );
			}
		}

		$this->tmpl( 'profile-public', get_defined_vars() );
	}

	private function get_project( $set ) {
		if ( ! isset( $this->projects[ $set->project_id ] ) ) {
			 $this->projects[ $set->project_id ] = GP::$project->get( $set->project_id );
		}

		$project = $this->projects[$set->project_id];
		$project_url = gp_url_project( $project, gp_url_join( $set->locale, $set->slug ) );
		$set_name = gp_project_names_from_root( $project ) . ' | ' . $set->name_with_locale();

		return (object) array(
			'project_id' => $project->id,
			'project_url' => $project_url,
			'set_name' => $set_name
		);
	}

	private function time_since( $time ) {
		$time = time() - $time; // to get the time since that moment

		$tokens = array (
			31536000 => 'year',
			2592000 => 'month',
			604800 => 'week',
			86400 => 'day',
			3600 => 'hour',
			60 => 'minute',
			1 => 'second'
		);

		foreach ( $tokens as $unit => $text ) {
			if ( $time < $unit ) {
				continue;
			}

			$numberOfUnits = floor( $time / $unit );

			return $numberOfUnits . ' ' . $text . ( ( $numberOfUnits > 1 ) ? 's' : '' );
		}
	}

}
