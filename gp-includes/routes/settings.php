<?php
class GP_Route_Settings extends GP_Route_Main {

	public function settings_get() {
		if ( ! is_user_logged_in() ) {
			$this->redirect( wp_login_url( gp_url_profile() ) );
			return;
		}

		$this->tmpl( 'settings' );
	}

	public function settings_post( $user_id = null ) {
		if ( isset( $_POST['submit'] ) ) {
			// Sometimes we get null, sometimes we get 0, depending on where it comes from.
			// Let's make sure we have a consistent value to test against and that it's an integer.
			$user_id = (int) $user_id;

			if ( 0 === $user_id ) { 
				$user_id = get_current_user_id(); 
			}
			
			$per_page = (int) $_POST['per_page'];
			update_user_option( $user_id, 'gp_per_page', $per_page );

			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc'
			);
			
			$user_sort = wp_parse_args( $_POST['default_sort'], $default_sort );
			update_user_option( $user_id, 'gp_default_sort', $user_sort );
		}

		$this->redirect( gp_url( '/settings' ) );
	}
}
