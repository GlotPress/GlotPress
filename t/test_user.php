<?php
require_once('init.php');

class GP_Test_User extends GP_UnitTestCase {
	
	function test_can() {
		$user = new GP_User( array( 'id' => 1 ) );
		$args = array( 'user' => $user, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 1 );
		GP_Permission::create( $args );
		$this->assertEquals( true, $user->can( 'write', 'translation-set', 1 ) );
		$this->assertEquals( false, $user->can( 'write', 'translation-set', 2 ) );
		$this->assertEquals( false, $user->can( 'write', 'translation-set' ) );
		$this->assertEquals( false, $user->can( 'write' ) );
	}
	
	function test_admin() {
		$admin_user = new GP_User( array( 'id' => 1 ) );
		GP_Permission::create( array( 'user' => $admin_user, 'action' => 'admin' ) );
		$nonadmin_user = new GP_User( array( 'id' => 2 ) );
		$this->assertEquals( true, $admin_user->admin() );
		$this->assertEquals( false, $nonadmin_user->admin() );
		$this->assertEquals( true, $admin_user->can( 'milk', 'a cow' ) );
		$this->assertEquals( true, $admin_user->can( 'milk', 'a cow', 5 ) );
		$this->assertEquals( false, $nonadmin_user->can( 'milk', 'a cow', 5 ) );
	}
	
	function test_create() {
		global $gpdb;
		$user = GP_User::create( array( 'user_login' => 'pijo', 'user_url' => 'http://dir.bg/', 'user_pass' => 'baba', 'user_email' => 'pijo@example.org' ) );
		$from_db = GP_User::by_login( 'pijo' );
		$this->assertEquals( $user->id, $from_db->id );
	}
}
