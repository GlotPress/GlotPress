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
			$user_to_make_admin = GP::$user->by_login( $user_login );
			if ( !$user_to_make_admin ) {
				WP_CLI::error( "User '$user_login' doesn't exist." );
			}
			if ( !GP::$permission->create( array( 'user_id' => $user_to_make_admin->id, 'action' => 'admin' ) ) ) {
				WP_CLI::line( "Error in making '$user_login' an admin." );
			}
		}
	}
}
