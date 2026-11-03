<?php

class GP_Test_Permissions extends GP_UnitTestCase {

	function test_create_find() {
		$args = array( 'user_id' => 2, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => '5' );
		GP::$permission->create( $args );
		$from_db = GP::$permission->find_one( $args );
		$this->assertEqualPermissions( $args, $from_db );
	}

	function test_create_find_with_nulls() {
		$args = array( 'user_id' => 2, 'action' => 'write', 'object_type' => 'translation-set', );
		GP::$permission->create( array_merge( $args, array( 'object_id' => 11 ) ) );
		GP::$permission->create( $args );
		$args['object_id'] = null;
		$from_db = GP::$permission->find_one( $args );
		$this->assertEqualPermissions( $args, $from_db );
	}

	function test_user_can() {
		$user = $this->factory->user->create();
		$set_1_permission = array( 'user_id' => $user, 'action' => 'write', 'object_type' => 'translation-set', 'object_id' => 1 );
		GP::$permission->create( $set_1_permission );
		$this->assertTrue( GP::$permission->user_can( $user, 'write', 'translation-set', 1 ) );
		$this->assertFalse( GP::$permission->user_can( $user, 'write', 'translation-set', 2 ) );
		$this->assertFalse( GP::$permission->user_can( $user, 'write', 'translation-set' ) );
		$this->assertFalse( GP::$permission->user_can( $user, 'write' ) );
	}

	function test_admin_should_be_able_to_do_random_actions() {
		$admin_user = $this->factory->user->create_admin();
		$this->assertTrue( GP::$permission->user_can( $admin_user, 'milk', 'a cow' ) );
		$this->assertTrue( GP::$permission->user_can( $admin_user, 'milk', 'a cow', 5 ) );
	}

	function test_non_admin_should_not_be_able_to_do_random_actions() {
		$nonadmin_user = $this->factory->user->create();
		$this->assertFalse( GP::$permission->user_can( $nonadmin_user, 'milk', 'a cow' ) );
		$this->assertFalse( GP::$permission->user_can( $nonadmin_user, 'milk', 'a cow', 5 ) );
	}

	function test_logged_out_permissions() {
		$project = $this->factory->project->create();
		$this->assertFalse( (bool)GP::$permission->current_user_can( 'admin' ) );
		$this->assertFalse( (bool)GP::$permission->current_user_can( 'write', 'project', $project->id ) );
	}

	function test_recursive_project_permissions() {
		$user = $this->factory->user->create();
		$other = GP::$project->create( array( 'name' => 'Other', 'slug' => 'other', 'path' => 'other') );
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root') );
		$sub = GP::$project->create( array( 'name' => 'Sub', 'slug' => 'sub', 'parent_project_id' => $root->id, 'path' => 'root/sub' ) );

		GP::$permission->create( array( 'user_id' => $user, 'action' => 'write', 'object_type' => 'project', 'object_id' => $root->id ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'write', 'project', $root->id ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'write', 'project', $sub->id ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'write', 'project', $other->id ) );
	}

	function test_recursive_validator_permissions() {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();

		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root') );
		$sub = GP::$project->create( array( 'name' => 'Sub', 'slug' => 'sub', 'parent_project_id' => $root->id, 'path' => 'root/sub' ) );

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'whatever',
			'project_id' => $root->id, 'locale_slug' => 'bg', 'set_slug' => 'default' ) );

		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'whatever', $object_type, GP::$validator_permission->object_id( $root->id, 'bg', 'default' ) ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'whatever', $object_type, GP::$validator_permission->object_id( $sub->id, 'bg', 'default' ) ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'whatever', $object_type, GP::$validator_permission->object_id( $sub->id, 'bg', 'default' ) ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'other', $object_type, $sub->id.'|bg|default' ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'whatever', $object_type, $sub->id.'|en|default' ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'whatever', $object_type, $sub->id.'|bg|slug' ) );
	}

	function test_approve_translation_set_permissions() {
		$user = $this->factory->user->create();

		$other = GP::$project->create( array( 'name' => 'Other', 'slug' => 'other', 'path' => 'other') );
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root') );
		$sub = GP::$project->create( array( 'name' => 'Sub', 'slug' => 'sub', 'parent_project_id' => $root->id, 'path' => 'root/sub' ) );

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'approve',
			'project_id' => $root->id, 'locale_slug' => 'bg', 'set_slug' => 'default' ) );

		$set_root_bg = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $root->id, 'locale' => 'bg') );
		$set_sub_bg = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $sub->id, 'locale' => 'bg') );
		$set_root_en = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $root->id, 'locale' => 'en') );
		$set_root_bg_slug = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'baba', 'project_id' => $root->id, 'locale' => 'bg') );
		$set_other_bg = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $other->id, 'locale' => 'bg') );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_root_bg->id ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_sub_bg->id ) );
		$this->assertTrue( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_root_bg->id, array('set' => $set_root_bg) ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_root_en->id ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_root_bg_slug->id ) );
		$this->assertFalse( (bool)GP::$permission->user_can( $user, 'approve', 'translation-set', $set_other_bg->id ) );
	}

	function assertEqualPermissions( $expected, $actual ) {
		foreach(  $expected as $field => $value ) {
			$this->assertSame( $value, $actual->$field, "Field: $field" );
		}
	}

	/**
	 * @ticket gh194
	 */
	function test_permissions_delete_on_user_delete() {
		$user = $this->factory->user->create();
		$project = $this->factory->project->create();

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'approve',
			'project_id' => $project->id, 'locale_slug' => 'bg', 'set_slug' => 'default' ) );

		$permissions = GP::$permission->find_many( array( 'user_id' => $user ) );
		$this->assertSame( 1, count( $permissions ) );

		wp_delete_user( $user );

		$permissions = GP::$permission->find_many( array( 'user_id' => $user ) );
		$this->assertSame( 0, count( $permissions ) );
	}

	/**
	 * @ticket gh-233
	 */
	function test_administrator_permissions_create() {
		$user = $this->factory->user->create();

		GP::$administrator_permission->create( array( 'user_id' => $user ) );

		$permissions = GP::$administrator_permission->find( array( 'user_id' => $user ) );
		$this->assertSame( 1, count( $permissions ) );
	}

	/**
	 * @ticket gh-377
	 */
	function test_import_permissions() {
		$user = $this->factory->user->create();

		$project = $this->factory->project->create();
		$set = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $project->id, 'locale' => 'bg' ) );
		$set2 = GP::$translation_set->create( array( 'name' => 'Set', 'slug' => 'default', 'project_id' => $project->id, 'locale' => 'de' ) );

		$this->assertFalse( (bool) GP::$permission->user_can( $user, 'import-waiting', 'translation-set', $set->id ) );
		$this->assertFalse( (bool) GP::$permission->user_can( $user, 'import-waiting', 'translation-set', $set2->id ) );

		GP::$permission->create( array( 'user_id' => $user, 'action' => 'import-waiting', 'object_type' => 'translation-set', 'object_id' => $set2->id ) );

		$this->assertTrue( (bool) GP::$permission->user_can( $user, 'import-waiting', 'translation-set', $set2->id ) );
		$this->assertFalse( (bool) GP::$permission->user_can( $user, 'import-waiting', 'translation-set', $set->id ) );
	}

	/**
	 * An anonymous request must never be treated as an administrator, even when a
	 * permission row with an empty (NULL) user_id happens to exist in the database.
	 *
	 * A permission is always tied to a specific user, so a NULL user_id must not
	 * match any user via `user_id IS NULL`.
	 */
	function test_anonymous_user_is_not_admin_when_null_user_id_admin_row_exists() {
		// user_id 0 is normalized to NULL by GP_Permission, mimicking a row a
		// userless activation could leave behind.
		GP::$permission->create( array( 'user_id' => 0, 'action' => 'admin' ) );
		$this->assertNotFalse( GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => null ) ), 'Expected an admin row with a NULL user_id to be stored for the test.' );

		wp_set_current_user( 0 );
		$this->assertFalse( (bool) GP::$permission->current_user_can( 'admin' ) );
		$this->assertFalse( (bool) GP::$permission->user_can( 0, 'admin' ) );
		$this->assertFalse( (bool) GP::$permission->user_can( 0, 'write', 'project', 1 ) );

		// Control: a real user with a proper admin row is still an administrator.
		$admin = $this->factory->user->create_admin();
		$this->assertTrue( (bool) GP::$permission->user_can( $admin, 'admin' ) );
	}

	/**
	 * Activation must not seed an administrator when no user is logged in
	 * (e.g. WP-CLI or programmatic activation).
	 */
	function test_activation_does_not_seed_admin_without_current_user() {
		$this->assertFalse( GP::$permission->find_one( array( 'action' => 'admin' ) ), 'Test expects no administrators to exist beforehand.' );

		wp_set_current_user( 0 );
		gp_activate_plugin();

		$this->assertFalse( GP::$permission->find_one( array( 'action' => 'admin' ) ), 'No administrator should be created without a logged-in user.' );
	}

	/**
	 * Activation seeds the user performing it as the administrator.
	 */
	function test_activation_seeds_current_user_as_admin() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		gp_activate_plugin();

		$admin = GP::$permission->find_one( array( 'action' => 'admin' ) );
		$this->assertNotFalse( $admin );
		$this->assertEquals( $user, $admin->user_id );
	}
}
