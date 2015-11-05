<?php

class GP_CLI_Wipe_Permissions extends WP_CLI_Command {

	public function __invoke() {
		_e( "This will erase all current permissions!\nAre you sure you want to delete them? [y/N]\n", 'glotpress' );
		$response = fgets( STDIN );
		if ( ! in_array( strtolower( trim( $response ) ), array( 'y', 'yes' ) ) ) {
			WP_CLI::line( __( 'Nothing was deleted.', 'glotpress' ) );
			return;
		}

		if ( ! GP::$permission->delete_all() ) {
			WP_CLI::error( __( 'Error in deleting permissions.', 'glotpress' ) );
		}

		WP_CLI::line( __( 'Permissions were deleted. Now you can use `wp glotpress add-admin` to add a new administrator.', 'glotpress' ) );
	}
}
