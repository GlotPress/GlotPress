<?php
/**
 * Routes: GP_Route_Settings class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 2.0.0
 */

/**
 * Core class used to implement the settings route.
 *
 * @since 2.0.0
 */
class GP_Route_Settings extends GP_Route_Main {

	/**
	 * Displays the settings page, requires a user to be logged in.
	 */
	public function settings_get() {
		if ( ! is_user_logged_in() ) {
			$this->redirect( wp_login_url( gp_url_profile() ) );
			exit;
		}

		$this->tmpl( 'settings' );
	}

	/**
	 * Saves settings for a user and redirects back to the settings page.
	 *
	 * @param int $user_id Optional. A user id, if not provided the id of the currently logged in user will be used.
	 */
	public function settings_post( $user_id = null ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
		if ( isset( $_POST['submit'] ) ) {
			// Sometimes we get null, sometimes we get 0, depending on where it comes from.
			// Let's make sure we have a consistent value to test against and that it's an integer.
			$user_id = (int) $user_id;

			if ( 0 === $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( $this->invalid_nonce_and_redirect( 'update-settings_' . $user_id ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
			$per_page = (int) $_POST['per_page'];
			update_user_option( $user_id, 'gp_per_page', $per_page );

			$default_sort = array(
				'by'  => 'priority',
				'how' => 'desc',
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
			$user_sort = wp_parse_args( $_POST['default_sort'], $default_sort );
			update_user_option( $user_id, 'gp_default_sort', $user_sort );

			// Choices that match the site aren't stored, so the user keeps following the site when it changes.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
			$timezone = gp_sanitize_timezone( wp_unslash( $_POST['timezone'] ?? '' ) );
			if ( '' === $timezone || gp_get_site_timezone_choice() === $timezone ) {
				delete_user_option( $user_id, 'gp_timezone' );
			} else {
				update_user_option( $user_id, 'gp_timezone', $timezone );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
			$date_format = wp_unslash( $_POST['date_format'] ?? '' );
			if ( in_array( $date_format, gp_get_date_format_choices(), true ) && get_option( 'date_format' ) !== $date_format ) {
				update_user_option( $user_id, 'gp_date_format', $date_format );
			} else {
				delete_user_option( $user_id, 'gp_date_format' );
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified by invalid_nonce_and_redirect()
			$use_24_hour_time = ! empty( $_POST['time_format_24h'] );
			if ( gp_site_uses_24_hour_time() === $use_24_hour_time ) {
				delete_user_option( $user_id, 'gp_time_format_24h' );
			} else {
				update_user_option( $user_id, 'gp_time_format_24h', $use_24_hour_time ? '1' : '0' );
			}

			$this->successes[] = __( 'Settings saved!', 'glotpress' );
		}

		$this->redirect( gp_url( '/settings' ) );
		$this->exit_();
	}
}
