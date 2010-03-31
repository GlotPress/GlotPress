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
	
	function test_logged_out_permissions() {
		$this->assertFalse( (bool)GP::$user->current()->can( 'admin' ) );
		$this->assertFalse( (bool)GP::$user->current()->can( 'write', 'project', 1 ) );
	}

	function test_recursive_project_permissions() {
		$user = GP::$user->create( array( 'user_login' => 'gugu', 'user_email' => 'gugu@gugu.net' ) );
		$other = GP::$project->create( array( 'name' => 'Other', 'slug' => 'other', 'path' => 'other') );
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root') );
		$sub = GP::$project->create( array( 'name' => 'Sub', 'slug' => 'sub', 'parent_project_id' => $root->id, 'path' => 'root/sub' ) );
		
		GP::$permission->create( array( 'user_id' => $user->id, 'action' => 'write', 'object_type' => 'project', 'object_id' => $root->id ) );
		$this->assertTrue( (bool)$user->can( 'write', 'project', $root->id ) );
		$this->assertTrue( (bool)$user->can( 'write', 'project', $sub->id ) );
		$this->assertFalse( (bool)$user->can( 'write', 'project', $other->id ) );
	}
	
	function assertEqualPermissions( $expected, $actual ) {
		$fields = $actual->fields();
		unset($fields['id']);
	}
	
	
}
