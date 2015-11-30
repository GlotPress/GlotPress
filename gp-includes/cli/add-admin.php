<?php

class GP_CLI_Add_Admin extends WP_CLI_Command {
	/**
	 * Give the user admin rights in GlotPress
	 *
	 * ## OPTIONS
	 *
	 * <username>...
	 * : Username(s) to make an admin
	 */
	public function __invoke( $args, $assoc_args) {
		foreach( $args as $user_login ) {
			$user_to_make_admin = get_user_by( 'login', $user_login );
			if ( ! $user_to_make_admin ) {
				/* translators: %s: Username */
				WP_CLI::error( sprintf( __( "User '%s' doesn't exist.", 'glotpress' ), $user_login ) );
			}
			if ( ! GP::$permission->create( array( 'user_id' => $user_to_make_admin->ID, 'action' => 'admin' ) ) ) {
				/* translators: %s: Username */
				WP_CLI::error( sprintf( __( "Error in making '%s' an admin.", 'glotpress' ), $user_login ) );
			}

			/* translators: %s: Username */
			WP_CLI::line( sprintf( __( "'%s' is now an admin.", 'glotpress' ), $user_login ) );
		}
	}
}
