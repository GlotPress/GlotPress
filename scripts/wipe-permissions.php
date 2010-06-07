<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Wipe_Permissions extends GP_CLI {
	
	function run() {
		echo "This will erase all current permissions!\nAre you sure you want to delete them? [y/N]\n";
		$response = fgets( STDIN );
		if ( in_array( strtolower( trim( $response ) ), array( 'y', 'yes' ) ) ) {
			if ( GP::$permission->delete_all() ) {
				echo "Permissions were deleted. Now you can use scripts/add-admin.php to add a new administrator.\n";
			} else {
				$this->to_stderr( "Error in deleting permissions." );
				exit( 1 );
			}
		} else {
			echo "Nothing was deleted.\n";
		}
	}
}

$gp_script_wipe_permissions = new GP_Script_Wipe_Permissions;
$gp_script_wipe_permissions->run();
