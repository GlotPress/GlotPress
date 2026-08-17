<?php

class GP_Test_Route_Project extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Project';

	private function edit_project( $project, $fields ) {
		$_POST['project']            = $fields;
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-project_' . $project->id );
		$this->route->edit_post( $project->path );
	}

	public function test_a_submitted_path_cannot_rewrite_another_project_subtree() {
		$this->set_admin_user_as_current();
		$other_parent = $this->factory->project->create( array( 'name' => 'Foo', 'slug' => 'foo' ) );
		$other_child  = $this->factory->project->create( array( 'name' => 'Bar', 'slug' => 'bar', 'parent_project_id' => $other_parent->id ) );
		$project      = $this->factory->project->create( array( 'name' => 'Owned', 'slug' => 'owned' ) );

		// A path submitted in the request that, if trusted, would match the "foo" subtree.
		$this->edit_project(
			$project,
			array(
				'name'              => 'Owned',
				'slug'              => 'owned',
				'path'              => 'foo',
				'parent_project_id' => 0,
			)
		);

		$other_child->reload();
		$this->assertSame( 'foo/bar', $other_child->path, 'A path in the request must not rewrite an unrelated project subtree.' );
	}

	public function test_renaming_a_project_leaves_a_prefix_sibling_untouched() {
		$this->set_admin_user_as_current();
		$sibling = $this->factory->project->create( array( 'name' => 'Foobar', 'slug' => 'foobar' ) );
		$project = $this->factory->project->create( array( 'name' => 'Foo', 'slug' => 'foo' ) );

		$this->edit_project(
			$project,
			array(
				'name'              => 'Foo',
				'slug'              => 'foo2',
				'parent_project_id' => 0,
			)
		);

		$sibling->reload();
		$this->assertSame( 'foobar', $sibling->path, 'A sibling sharing the path prefix must not be rewritten.' );

		$project->reload();
		$this->assertSame( 'foo2', $project->path, 'The renamed project should get its new derived path.' );
	}

	public function test_writer_cannot_reparent_under_a_project_without_write_permission() {
		$user_id      = $this->set_normal_user_as_current();
		$project      = $this->factory->project->create( array( 'name' => 'Owned', 'slug' => 'owned' ) );
		$other_parent = $this->factory->project->create( array( 'name' => 'Other', 'slug' => 'other' ) );

		GP::$permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'write',
				'object_type' => 'project',
				'object_id'   => $project->id,
			)
		);

		$this->edit_project(
			$project,
			array(
				'name'              => 'Owned',
				'slug'              => 'owned',
				'parent_project_id' => $other_parent->id,
			)
		);

		$project->reload();
		$this->assertSame( 0, (int) $project->parent_project_id, 'A user without write on the destination parent must not reparent under it.' );
		$this->assertSame( 'owned', $project->path );
	}

	public function test_admin_can_reparent_a_project() {
		$this->set_admin_user_as_current();
		$parent  = $this->factory->project->create( array( 'name' => 'Parent', 'slug' => 'parent' ) );
		$project = $this->factory->project->create( array( 'name' => 'Child', 'slug' => 'child' ) );

		$this->edit_project(
			$project,
			array(
				'name'              => 'Child',
				'slug'              => 'child',
				'parent_project_id' => $parent->id,
			)
		);

		$project->reload();
		$this->assertSame( (int) $parent->id, (int) $project->parent_project_id );
		$this->assertSame( 'parent/child', $project->path );
	}

	private function mass_create_sets( $target, $source ) {
		$_POST['project_id']         = $source->id;
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'mass-create-transation-sets_' . $target->id );
		$this->route->mass_create_sets_post( $target->path );
	}

	public function test_mass_create_does_not_remove_a_set_without_delete_permission() {
		$user_id = $this->set_normal_user_as_current();
		$set     = $this->factory->translation_set->create_with_project_and_locale();
		$source  = $this->factory->project->create();

		GP::$permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'write',
				'object_type' => 'project',
				'object_id'   => $set->project->id,
			)
		);

		$this->mass_create_sets( $set->project, $source );

		$this->assertNotFalse( GP::$translation_set->get( $set->id ), 'A writer without delete permission must not remove an existing set.' );
	}

	public function test_mass_create_removes_a_set_with_delete_permission() {
		$user_id = $this->set_normal_user_as_current();
		$set     = $this->factory->translation_set->create_with_project_and_locale();
		$source  = $this->factory->project->create();

		foreach ( array( 'write', 'delete' ) as $action ) {
			GP::$permission->create(
				array(
					'user_id'     => $user_id,
					'action'      => $action,
					'object_type' => 'project',
					'object_id'   => $set->project->id,
				)
			);
		}

		$this->mass_create_sets( $set->project, $source );

		$this->assertFalse( GP::$translation_set->get( $set->id ), 'A user with delete permission can remove sets via mass-create.' );
	}

	public function test_mass_create_still_adds_a_set_for_a_writer() {
		$user_id    = $this->set_normal_user_as_current();
		$target     = $this->factory->project->create();
		$source_set = $this->factory->translation_set->create_with_project_and_locale();

		GP::$permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'write',
				'object_type' => 'project',
				'object_id'   => $target->id,
			)
		);

		$this->mass_create_sets( $target, $source_set->project );

		$this->assertCount( 1, GP::$translation_set->by_project_id( $target->id ), 'A writer can still add sets when nothing is removed.' );
	}

	public function test_mass_create_applies_additions_but_skips_removals_without_delete_permission() {
		$user_id    = $this->set_normal_user_as_current();
		$target_set = $this->factory->translation_set->create_with_project_and_locale();
		$source_set = $this->factory->translation_set->create_with_project_and_locale();

		GP::$permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'write',
				'object_type' => 'project',
				'object_id'   => $target_set->project->id,
			)
		);

		// The source has a locale the target lacks (an addition) and lacks the
		// target's locale (a removal), so the diff contains both.
		$this->mass_create_sets( $target_set->project, $source_set->project );

		$this->assertNotFalse( GP::$translation_set->get( $target_set->id ), 'The existing set must survive without delete permission.' );
		$this->assertCount( 2, GP::$translation_set->by_project_id( $target_set->project->id ), 'The addition is applied while the removal is skipped.' );
	}
}
