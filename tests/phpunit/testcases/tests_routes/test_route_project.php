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
}
