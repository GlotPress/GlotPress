<?php

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
}
