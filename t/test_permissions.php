<?php
require_once('init.php');

class GP_Test_Permissions extends GP_UnitTestCase {
	function test_create_find() {
		$args = array( 'user' => 1, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 5 );
		GP_Permission::create( $args );
		$from_db = GP_Permission::find( $args );
		$this->assertEqualPermissions( (object)$args,  $from_db );
	}
	
	function test_create_find_with_nulls() {
		$args = array( 'user' => 1, 'action' => 'write', 'object_type' => 'translation-set', );
		GP_Permission::create( array_merge( $args, array( 'object_id' => 11 ) ) );
		GP_Permission::create( $args );
		$args['object_id'] = null;
		$from_db = GP_Permission::find( $args );
		$this->assertEqualPermissions( (object)$args,  $from_db );
	}
	
	function assertEqualPermissions( $expected, $actual ) {
		unset( $actual->id );
		$expected->user_id = $expected->user;
		unset( $expected->user );
		$this->assertEquals( $expected, $actual );
	}
}
