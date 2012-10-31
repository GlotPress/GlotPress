<?php
class GP_Route_Profile extends GP_Route_Main {
	function profile_get() {
		if ( !GP::$user->logged_in() ) {
			$this->redirect( gp_url( '/login?redirect_to=' ).urlencode( gp_url( '/profile') ) );
			return;
		}

		gp_tmpl_load( 'profile', array() );
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
}
