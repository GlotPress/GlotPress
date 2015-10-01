<?php
class GP_Route_Profile extends GP_Route_Main {

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

			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc'
			);
			$user_sort = wp_parse_args( $_POST['default_sort'], $default_sort );

			GP::$user->current()->set_meta( 'default_sort', $user_sort );
		}

		$this->redirect( gp_url( '/profile' ) );
	}

	public function profile_view( $user ) {
		$user = GP::$user->find_one( array( 'user_nicename' => $user ) );

		if ( ! $user ) {
			return $this->die_with_404();
		}

		$recent_projects = $user->get_recent_translation_sets( 5 );
		$locales         = $user->locales_known();

		//validate to
		$permissions = $user->get_permissions();

		$this->tmpl( 'profile-public', get_defined_vars() );
	}

}
