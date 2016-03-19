<?php

class GP_Test_Project extends GP_UnitTestCase {

	function test_empty_name() {
		$project = GP::$project->create( array( 'name' => '' ) );
		$verdict = $project->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_slug() {
		$project = GP::$project->create( array( 'name' => 'Name', 'slug' => false ) );
		$verdict = $project->validate();

		$this->assertTrue( $verdict );
		$this->assertEquals( 'name', $project->path );
	}

	function test_update_path() {
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root' ) );
		// the slug is changed
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'cool', 'path' => 'root/p1', 'parent_project_id' => $root->id ) );
		$p2 = GP::$project->create( array( 'name' => 'P2', 'slug' => 'p2', 'path' => 'root/p1/p2', 'parent_project_id' => $p1->id ) );
		$p3 = GP::$project->create( array( 'name' => 'P3', 'slug' => 'p3', 'path' => 'root/p1/p2/p3', 'parent_project_id' => $p2->id ) );
		$p4 = GP::$project->create( array( 'name' => 'P4', 'slug' => 'p4', 'path' => 'root/p4/', 'parent_project_id' => $root->id ) );
		$p5 = GP::$project->create( array( 'name' => 'P5', 'slug' => 'p5', 'path' => 'root/p4/p5/', 'parent_project_id' => $p4->id ) );
		$p1->update_path();
		$p1->reload();
		$p2->reload();
		$p3->reload();
		$p4->reload();
		$p5->reload();
		$this->assertEquals( 'root/cool', $p1->path);
		$this->assertEquals( 'root/cool/p2', $p2->path);
		$this->assertEquals( 'root/cool/p2/p3', $p3->path);
		$this->assertEquals( 'root/p4', $p4->path);
		$this->assertEquals( 'root/p4/p5', $p5->path);
	}

	function test_valid_path_on_create() {
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root', 'path' => 'root' ) );
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'p1', 'parent_project_id' => $root->id ) );
		$q = GP::$project->create( array( 'name' => 'Invader', 'slug' => 'invader', 'path' => '' ) );
		$p4 = GP::$project->create( array( 'name' => 'P4', 'slug' => 'p4', 'path' => 'root/p4/', 'parent_project_id' => $root->id ) );
		$p5 = GP::$project->create( array( 'name' => 'P5', 'slug' => 'p5', 'path' => 'root/p4/p5/', 'parent_project_id' => $p4->id ) );
		$root->reload();
		$p1->reload();
		$q->reload();
		$p4->reload();
		$p5->reload();
		$this->assertEquals( 'root', $root->path );
		$this->assertEquals( 'root/p1', $p1->path );
		$this->assertEquals( 'invader', $q->path );
		$this->assertEquals( 'root/p4', $p4->path);
		$this->assertEquals( 'root/p4/p5', $p5->path);
	}

	function test_create_and_select() {
		$project = new GP_Project( array( 'name' => '@@@@', 'slug' => '' ) );
		$verdict = $project->validate();

		$this->assertFalse( $verdict );
	}

	function test_save_no_args() {
		$p1 = GP::$project->create( array( 'name' => 'P1', 'slug' => 'p1', 'path' => 'p1' ) );
		$id = $p1->id;
		$p1->name = 'P2';
		$p1->save();
		$this->assertEquals( 'P2', $p1->name );
		$p1->reload();
		$this->assertEquals( 'P2', $p1->name );
		$this->assertEquals( 'P2', GP::$project->get( $id )->name );
	}

	function test_reload() {
		global $wpdb;
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root' ) );
		$wpdb->update( $wpdb->gp_projects, array( 'name' => 'Buuu' ), array( 'id' => $root->id ) );
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

	function test_regenerate_paths() {
		global $wpdb;
		$root = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root' ) );
		$sub  = $this->factory->project->create( array( 'name' => 'Sub', 'parent_project_id' => $root->id ) );
		$wpdb->update( $wpdb->gp_projects, array( 'path' => 'wrong-path' ), array( 'id' => $sub->id ) );
		$sub->reload();
		$sub->regenerate_paths();
		$sub->reload();
		$this->assertEquals( 'root/sub', $sub->path );
		
		// Run the same test a second time with a permalink structure that includes a trailing slash.
		$this->set_permalink_structure( GP_TESTS_PERMALINK_STRUCTURE_WITH_TRAILING_SLASH );
		$wpdb->update( $wpdb->gp_projects, array( 'path' => 'wrong-path' ), array( 'id' => $sub->id ) );
		$sub->reload();
		$sub->regenerate_paths();
		$sub->reload();
		$this->assertEquals( 'root/sub', $sub->path );

	}

	function test_set_difference_from_same() {
		$p1 = $this->factory->project->create( array( 'name' => 'P1' ) );
		$p2 = $this->factory->project->create( array( 'name' => 'P2' ) );

		$difference = $p1->set_difference_from( $p2 );

		$this->assertEmpty( $difference['added'] );
		$this->assertEmpty( $difference['removed'] );
	}

	function test_set_difference_from_difference() {
		$s1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'P1' ) );
		$s2 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'nl' ), array( 'name' => 'P2' ) );

		$difference = $s1->project->set_difference_from( $s2->project );

		$this->assertEquals( $s2->id, $difference['added'][0]->id );
		$this->assertEquals( $s1->id, $difference['removed'][0]->id );
	}

	function test_copy_originals_from() {
		$s1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'P1' ) );
		$s2 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'nl' ), array( 'name' => 'P2' ) );

		$this->factory->translation->create_with_original_for_translation_set( $s1 );

		$s2->project->copy_originals_from( $s1->project->id );

		$s1_original = GP::$original->by_project_id( $s1->project->id );
		$s2_original = GP::$original->by_project_id( $s2->project->id );
		$s1_original = array_shift( $s1_original );
		$s2_original = array_shift( $s2_original );

		$this->assertNotEquals( $s1_original->id, $s2_original->id );
		$this->assertNotEquals( $s1_original->project_id, $s2_original->project_id );
		$this->assertEqualFields( $s2_original,
			array( 'singular' => $s1_original->singular, 'plural' => $s1_original->plural, 'references' => $s1_original->references, 'comment' =>$s1_original->comment, 'status' =>$s1_original->status, 'date_added' => $s1_original->date_added )
		);
	}

	function test_sets_in_copy_sets_and_translations_from() {
		$s1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'P1' ) );
		$this->factory->translation->create_with_original_for_translation_set( $s1 );

		$branch = $this->factory->project->create( array( 'name' => 'branch' ) );
		$branch->copy_sets_and_translations_from( $s1->project->id );

		$difference = $branch->set_difference_from( $s1->project );

		$this->assertEmpty( $difference['added'] );
		$this->assertEmpty( $difference['removed'] );

	}

	function test_translations_in_copy_sets_and_translations_from() {
		$original = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'P1' ) );
		$this->factory->translation->create_with_original_for_translation_set( $original );

		$copy = $this->factory->project->create( array( 'name' => 'branch' ) );
		$copy->copy_originals_from( $original->project->id );
		$copy->copy_sets_and_translations_from( $original->project->id );

		$copy_set = GP::$translation_set->by_project_id( $copy->id );
		$copy_set = array_shift( $copy_set );

		$original_translation = GP::$translation->find( array( 'translation_set_id' => $original->id ) );
		$original_translation = array_shift( $original_translation );
		$copy_translation = GP::$translation->find( array( 'translation_set_id' => $copy_set->id ) );
		$copy_translation = array_shift( $copy_translation );

		$this->assertNotEquals( $original_translation->original_id, $copy_translation->original_id );

		$this->assertEqualFields( $copy_translation,
			array( 'translation_0' => $original_translation->translation_0 )
		);

	}

	function test_branching_translation_sets(){
		$root_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'root' ) );
		$root = $root_set->project;

		$sub_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'Sub', 'parent_project_id' => $root->id ) );
		$sub = $sub_set->project;

		$subsub_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'SubSub', 'parent_project_id' => $sub->id ) );
		$subsub = $subsub_set->project;

		$this->factory->translation->create_with_original_for_translation_set( $root_set );
		$this->factory->translation->create_with_original_for_translation_set( $sub_set );
		$this->factory->translation->create_with_original_for_translation_set( $subsub_set );

		$branch = $this->factory->project->create( array( 'name' => 'branch' ) );
		$branch->duplicate_project_contents_from( $root );

		$branch_sub = $branch->sub_projects();
		$branch_sub = array_shift( $branch_sub );
		$branch_subsub = $branch_sub->sub_projects();
		$branch_subsub = array_shift( $branch_subsub );

		$difference_root = $root->set_difference_from( $branch );
		$difference_sub = $sub->set_difference_from( $branch_sub );
		$difference_subsub = $subsub->set_difference_from( $branch_subsub );

		$this->assertEmpty( $difference_root['added'] );
		$this->assertEmpty( $difference_root['removed'] );

		$this->assertEmpty( $difference_sub['added'] );
		$this->assertEmpty( $difference_sub['removed'] );

		$this->assertEmpty( $difference_subsub['added'] );
		$this->assertEmpty( $difference_subsub['removed'] );
	}

	function test_branching_originals(){
		$root_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'root' ) );
		$root = $root_set->project;

		$sub_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'SubSub', 'parent_project_id' => $root->id ) );
		$sub = $sub_set->project;

		$this->factory->translation->create_with_original_for_translation_set( $root_set );
		$this->factory->translation->create_with_original_for_translation_set( $sub_set );

		$branch = $this->factory->project->create( array( 'name' => 'branch' ) );
		$branch->duplicate_project_contents_from( $root );

		$branch_sub = $branch->sub_projects();
		$branch_sub = array_shift( $branch_sub );

		$originals_root = GP::$original->by_project_id( $root->id );
		$originals_sub = GP::$original->by_project_id( $sub->id );

		$originals_branch = GP::$original->by_project_id( $branch->id );
		$originals_branch_sub = GP::$original->by_project_id( $branch_sub->id );

		$this->assertEquals( count( $originals_root ), count( $originals_branch ) );
		$this->assertEquals( count( $originals_sub ), count( $originals_branch_sub ) );
	}

	function test_branching_paths(){
		$root_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'root' ) );
		$root = $root_set->project;

		$sub_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'sub', 'parent_project_id' => $root->id ) );
		$sub = $sub_set->project;

		$other_sub_set = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'other_sub', 'parent_project_id' => $root->id ) );

		$branch = $this->factory->project->create( array( 'name' => 'branch' ) );
		$branch->duplicate_project_contents_from( $root );

		$branch_sub = $branch->sub_projects();
		$branch_sub = array_shift( $branch_sub );

		$this->assertEquals( $root->path, 'root' );
		$this->assertEquals( $branch->path, 'branch' );
		$this->assertEquals( $sub->path, 'root/sub' );
		$this->assertEquals( $branch_sub->path, 'branch/sub' );

		$branch_other_sub = GP::$project->by_path('branch/other_sub');
		$this->assertNotEquals( false, $branch_other_sub );
	}

	function test_delete() {
		$project = GP::$project->create( array( 'name' => 'Root', 'slug' => 'root' ) );
		
		$pre_delete = GP::$project->find_one( array( 'id' => $project->id ) );

		$project->delete();
		
		$post_delete = GP::$project->find_one( array( 'id' => $project->id ) );
		
		$this->assertFalse( empty( $pre_delete ) );
		$this->assertNotEquals( $pre_delete, $post_delete );
	}

}