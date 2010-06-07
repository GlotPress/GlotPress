<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Add_Admin extends GP_CLI {

	var $usage = "<username-to-make-an-admin>";
	
	function run() {
		if ( empty( $this->args ) ) {
			$this->usage();
		}
		foreach( $this->args as $user_login ) {
			$user_to_make_admin = GP::$user->by_login( $user_login );
			if ( !$user_to_make_admin ) {
				$this->to_stderr( "User '$user_login' doesn't exist." );
				exit( 1 );
			}
			if ( !GP::$permission->create( array( 'user_id' => $user_to_make_admin->id, 'action' => 'admin' ) ) ) {
				$this->to_stderr( "Error in making '$user_login' and admin." );
				exit( 2 );
			}
		}
		
	}
}

$gp_script_add_admin = new GP_Script_Add_Admin;
$gp_script_add_admin->run();
