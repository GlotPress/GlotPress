<?php

class GP_Route_Login extends GP_Route_Main {

	function login_get() {
		if ( is_user_logged_in() ) {
			$this->redirect( gp_url( '/' ) );

			return;
		}

		$redirect_to = gp_url( '/' );
		if ( isset( $_GET['redirect_to'] ) && $_GET['redirect_to'] ) {
			$redirect_to = $_GET['redirect_to'];
		}

		$this->redirect( wp_login_url( $redirect_to ) );
	}

	function logout() {
		$this->redirect( wp_logout_url( gp_url( '/' ) ) );
	}
}
