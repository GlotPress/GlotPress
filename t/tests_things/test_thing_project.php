<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Project extends GP_UnitTestCase {
	function test_update_path() {
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root' ) );
		// the slug is changed
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'cool', 'path' => 'root/p1', 'parent_project_id' => $root->id ) );
		$p2 = GP::$project->create( array( 'name' => 'P2', 'slug' => 'p2', 'path' => 'root/p1/p2', 'parent_project_id' => $p1->id ) );
		$p3 = GP::$project->create( array( 'name' => 'P3', 'slug' => 'p3', 'path' => 'root/p1/p2/p3', 'parent_project_id' => $p2->id ) );
		$p1->update_path();
		$p1->reload();
		$p2->reload();
		$p3->reload();
		$this->assertEquals( 'root/cool', $p1->path);
		$this->assertEquals( 'root/cool/p2', $p2->path);
		$this->assertEquals( 'root/cool/p2/p3', $p3->path);
	}
	
	function test_valid_path_on_create() {
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root' ) );
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'p1', 'parent_project_id' => $root->id ) );
		$q = GP::$project->create( array( 'name' => 'Invader', 'slug' => 'invader', 'path' => '' ) );
		$root->reload();
		$p1->reload();
		$q->reload();
		$this->assertEquals( 'root', $root->path );
		$this->assertEquals( 'root/p1', $p1->path );
		$this->assertEquals( 'invader', $q->path );
	}
	
	function test_save_no_args() {
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'p1', 'path' => 'p1', ) );
		$id = $p1->id;
		$p1->name = 'P2';
		$p1->save();
		$this->assertEquals( 'P2', $p1->name );
		$p1->reload();
		$this->assertEquals( 'P2', $p1->name );
		$this->assertEquals( 'P2', GP::$project->get( $id )->name );
	}
	
	function test_reload() {
		global $gpdb;
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root'  ) );
		$gpdb->update( $gpdb->projects, array( 'name' => 'Buuu' ), array( 'id' => $root->id ) );
		$root->reload();
		$this->assertEquals( 'Buuu', $root->name );
	}
	
	function test_path_to_root() {
		$root = $this->factory->project->create( array( 'name' => 'Root' ) );
		$sub = $this->factory->project->create( array( 'name' => 'Sub', 'parent_project_id' => $root->id ) );
		$subsub = $this->factory->project->create( array( 'name' => 'SubSub', 'parent_project_id' => $sub->id ) );
		$this->assertEquals( array( $subsub, $sub, $root ), $subsub->path_to_root() );
		$this->assertEquals( array( $sub, $root ), $sub->path_to_root() );
		$this->assertEquals( array( $root ), $root->path_to_root() );
	}
}