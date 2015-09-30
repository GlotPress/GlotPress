<?php

class GP_CLI_Wipe_Permissions extends WP_CLI_Command {

	public function __invoke() {
		echo "This will erase all current permissions!\nAre you sure you want to delete them? [y/N]\n";
		$response = fgets( STDIN );
		if ( ! in_array( strtolower( trim( $response ) ), array( 'y', 'yes' ) ) ) {
			WP_CLI::line( 'Nothing was deleted.' );
			return;
		}

		if ( ! GP::$permission->delete_all() ) {
			WP_CLI::error( 'Error in deleting permissions.' );
		}

		WP_CLI::line( 'Permissions were deleted. Now you can use scripts/add-admin.php to add a new administrator.' );
	}
}
