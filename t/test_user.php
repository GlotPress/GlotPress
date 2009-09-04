<?php
require_once('init.php');

class GP_Test_User extends GP_UnitTestCase {
	
	function test_can() {
		$user = new GP_User( array( 'id' => 111 ) );
		$args = array( 'user_id' => $user->id, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 1 );
		GP::$permission->create( $args );
		$this->assertEquals( true, $user->can( 'write', 'translation-set', 1 ) );
		$this->assertEquals( false, $user->can( 'write', 'translation-set', 2 ) );
		$this->assertEquals( false, $user->can( 'write', 'translation-set' ) );
		$this->assertEquals( false, $user->can( 'write' ) );
	}
	
	function test_admin() {
		$admin_user = new GP_User( array( 'id' => 2 ) );
		GP::$permission->create( array( 'user_id' => $admin_user->id, 'action' => 'admin' ) );
		$nonadmin_user = new GP_User( array( 'id' => 3 ) );
		$this->assertEquals( true, $admin_user->admin() );
		$this->assertEquals( false, $nonadmin_user->admin() );
		$this->assertEquals( true, $admin_user->can( 'milk', 'a cow' ) );
		$this->assertEquals( true, $admin_user->can( 'milk', 'a cow', 5 ) );
		$this->assertEquals( false, $nonadmin_user->can( 'milk', 'a cow', 5 ) );
	}
	
	function test_create() {
		$user = GP::$user->create( array( 'user_login' => 'pijo', 'user_url' => 'http://dir.bg/', 'user_pass' => 'baba', 'user_email' => 'pijo@example.org' ) );
		$from_db = GP::$user->by_login( 'pijo' );
		$this->assertEquals( $user->id, $from_db->id );
	}
	
	function test_get() {
		$user = GP::$user->create( array( 'user_login' => 'pijo', 'user_url' => 'http://dir.bg/', 'user_pass' => 'baba', 'user_email' => 'pijo@example.org' ) );
		$from_db = GP::$user->get( $user );
		$this->assertEquals( $user->id, $from_db->id );
	}
	
}
