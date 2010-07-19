<?php
require_once('init.php');

class GP_Test_User extends GP_UnitTestCase {
	
	function test_can() {
		$user = $this->factory->user->create();
		$set_1_permission = array( 'user_id' => $user->id, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 1 );
		GP::$permission->create( $set_1_permission );
		$this->assertTrue( $user->can( 'write', 'translation-set', 1 ) );
		$this->assertFalse( $user->can( 'write', 'translation-set', 2 ) );
		$this->assertFalse( $user->can( 'write', 'translation-set' ) );
		$this->assertFalse( $user->can( 'write' ) );
	}
	
	function test_admin_should_be_admin() {
		$admin_user = $this->factory->user->create_admin();
		$this->assertTrue( $admin_user->admin() );
	}

	function test_non_admin_user_should_not_be_admin() {
		$nonadmin_user = $this->factory->user->create();
		$this->assertFalse( $nonadmin_user->admin() );
	}

	function test_admin_should_be_able_to_do_random_actions() {
		$admin_user = $this->factory->user->create_admin();
		$this->assertTrue( $admin_user->can( 'milk', 'a cow' ) );
		$this->assertTrue( $admin_user->can( 'milk', 'a cow', 5 ) );
	}
	
	function test_non_admin_should_not_be_able_to_do_random_actions() {
		$nonadmin_user = $this->factory->user->create();
		$this->assertFalse( $nonadmin_user->can( 'milk', 'a cow' ) );
		$this->assertFalse( $nonadmin_user->can( 'milk', 'a cow', 5 ) );
	}

	function test_select_by_login() {
		$user = $this->factory->user->create( array( 'user_login' => 'pijo' ) );
		$from_db = GP::$user->by_login( 'pijo' );
		$this->assertEquals( $user->id, $from_db->id );
	}
	
	function test_get() {
		$user = $this->factory->user->create();
		$from_db = GP::$user->get( $user );
		$this->assertEquals( $user->id, $from_db->id );
	}

	function test_set_meta_should_set_meta() {
		$user = $this->factory->user->create();
		$user->set_meta( 'int', 5 );
		$this->assertEquals( 5, $user->get_meta( 'int') );
	}
	
	function test_delete_meta_should_delete_the_meta() {
		$user = $this->factory->user->create();
		$user->set_meta( 'int', 5 );		
		$user->delete_meta( 'int' );
		$this->assertEquals( null, $user->get_meta( 'int') );
	}
	
	function test_setting_array_value_as_meta_should_come_out_as_an_array() {
		$user = $this->factory->user->create();
		$user->set_meta( 'mixed', array(1, 2, 3) );
		$this->assertEquals( array(1, 2, 3), $user->get_meta( 'mixed' ) );
	}
}
