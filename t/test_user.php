<?php
require_once('init.php');

class GP_Test_User extends GP_UnitTestCase {
    function GP_Test_User() {
        $this->UnitTestCase('User');
	}
	
	function test_can() {
		$user = new GP_User( array( 'id' => 1 ) );
		$args = array( 'user' => $user, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 1 );
		GP_Permission::create( $args );
		$this->assertEqual( true, $user->can( 'write', 'translation-set', 1 ) );
		$this->assertEqual( false, $user->can( 'write', 'translation-set', 2 ) );
		$this->assertEqual( false, $user->can( 'write', 'translation-set' ) );
		$this->assertEqual( false, $user->can( 'write' ) );
	}
	
	function test_admin() {
		$admin_user = new GP_User( array( 'id' => 1 ) );
		GP_Permission::create( array( 'user' => $admin_user, 'action' => 'admin' ) );
		$nonadmin_user = new GP_User( array( 'id' => 2 ) );
		$this->assertEqual( true, $admin_user->admin() );
		$this->assertEqual( false, $nonadmin_user->admin() );
		$this->assertEqual( true, $admin_user->can( 'milk', 'a cow' ) );
		$this->assertEqual( true, $admin_user->can( 'milk', 'a cow', 5 ) );
		$this->assertEqual( false, $nonadmin_user->can( 'milk', 'a cow', 5 ) );
	}
	
	function test_create() {
		global $gpdb;
		$user = GP_User::create( array( 'user_login' => 'pijo', 'user_url' => 'http://dir.bg/', 'user_pass' => 'baba', 'user_email' => 'baba@baba.net' ) );
		$from_db = GP_User::by_login( 'pijo' );
		$this->assertEqual( $user->id, $from_db->id );
	}
}
