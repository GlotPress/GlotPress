<?php

class GP_CLI_Wipe_Permissions extends WP_CLI_Command {

	public function __invoke() {
		WP_CLI::confirm( "This will erase all current permissions!\nAre you sure you want to delete them?" );

		if ( ! GP::$permission->delete_all() ) {
			WP_CLI::error( __( 'Error in deleting permissions.', 'glotpress' ) );
		}

		WP_CLI::success( __( 'Permissions were deleted. Now you can use `wp glotpress add-admin` to add a new administrator.', 'glotpress' ) );
	}
}
