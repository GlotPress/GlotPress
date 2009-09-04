<?php
require_once('init.php');

class GP_Test_Permissions extends GP_UnitTestCase {
	function test_create_find() {
		$args = array( 'user_id' => 2, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 5 );
		GP::$permission->create( $args );
		$from_db = GP::$permission->find_one( $args );
		$this->assertEqualPermissions( $args,  $from_db );
	}
	
	function test_create_find_with_nulls() {
		$args = array( 'user_id' => 2, 'action' => 'write', 'object_type' => 'translation-set', );
		GP::$permission->create( array_merge( $args, array( 'object_id' => 11 ) ) );
		GP::$permission->create( $args );
		$args['object_id'] = null;
		$from_db = GP::$permission->find_one( $args );
		$this->assertEqualPermissions( $args, $from_db );
	}
	
	function assertEqualPermissions( $expected, $actual ) {
		$fields = $actual->fields();
		unset($fields['id']);
	}
}
