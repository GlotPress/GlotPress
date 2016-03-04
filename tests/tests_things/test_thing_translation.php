<?php

class GP_Test_Thing_Translation extends GP_UnitTestCase {

	function test_translation_approve_change_status() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// Put the current count already in the cache
		$set->current_count();

		$translation->set_status('current');
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'current' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( 1, $set->current_count() );
	}

	function test_translation_should_support_6_plurals() {
		$plurals = array( 'translation_0' => 'Zero', 'translation_1' => 'One', 'translation_2' => 'Two', 'translation_3' => 'Three', 'translation_4' => 'Four', 'translation_5' => 'Five' );
		$translation = $this->factory->translation->create( $plurals );

		$this->assertEqualFields( $translation, $plurals );
	}

	function test_translation_should_write_all_6_plurals_to_database() {
		$plurals = array( 'translation_0' => 'Zero', 'translation_1' => 'One', 'translation_2' => 'Two', 'translation_3' => 'Three', 'translation_4' => 'Four', 'translation_5' => 'Five' );
		$translation = $this->factory->translation->create( $plurals );
		$translation->reload();

		$this->assertEqualFields( $translation, $plurals );
	}

	/**
	 * @ticket 149
	 * @ticket gh-236
	 */
	function test_translation_should_not_validate_with_empty_plurals() {
		$plurals = array( 'translation_0' => 'Zero', 'translation_1' => '', 'translation_2' => '' );
		$translation = $this->factory->translation->create( $plurals );

		$this->assertFalse( $translation->validate() );
	}

	function test_for_translation_shouldnt_exclude_originals_with_rejected_translation_if_status_has_untranslated() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->reject();
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'untranslated' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
	}

	function test_for_translation_should_include_untranslated_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array(), array('by' => 'translation', 'how' => 'asc') );

		$this->assertEquals( 2, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
		$this->assertEquals( $translation1->id, $for_translation[1]->id );
	}

	function test_for_translation_should_not_include_old_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translation1_old = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1_current = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1_current->set_as_current(); //$translation1_old is now old

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array(), array('by' => 'translation', 'how' => 'asc') );

		$this->assertEquals( 2, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
		$this->assertEquals( $translation1_current->id, $for_translation[1]->id );
	}


	function test_for_translation_should_not_include_untranslated_for_single_status() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) ); //This isn't going to be translated

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array('status' => 'current'), array('by' => 'translation', 'how' => 'asc') );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( $translation1->id, $for_translation[0]->id );
	}

	function test_for_export_should_include_untranslated() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_export = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current_or_untranslated' ) );

		$this->assertEquals( 2, count( $for_export ) );
		$this->assertEquals( $translation1->id, $for_export[0]->id );
	}

	function test_propagate_across_projects_propagates() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array('name'=>'project_two') );
		$set2 = $this->factory->translation_set->create( array('locale'=>$set1->locale, 'project_id'=>$project2->id) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1->set_as_current(); //calls propagate_across_projects

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array('status' => 'waiting') );

		$this->assertEquals( 1, count( $set2_current_translations ) );
	}

	function __string_status_current() {
		return 'current';
	}

	function test_propagate_across_projects_propagates_case_sensitiv() {
		add_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original3 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'Baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1->set_as_current(); //calls propagate_across_projects

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );
		remove_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
	}

	function test_propagate_across_projects_propagates_ignores_translations_with_warnings() {
		add_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$warnings = array( 0 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current', 'warnings' => $warnings ) );
		$translation1->set_as_current(); //calls propagate_across_projects

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );
		remove_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
	}

	function test_propagate_across_projects_does_not_create_more_than_one_current() {
		add_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array('name'=>'project_two') );
		$set2 = $this->factory->translation_set->create( array('locale' => $set1->locale, 'project_id' => $project2->id) );

		$project3 = $this->factory->project->create( array('name'=>'project_three') );
		$set3 = $this->factory->translation_set->create( array('locale' => $set1->locale, 'project_id' => $project3->id) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original3 = $this->factory->original->create( array( 'project_id' => $set3->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation2 = $this->factory->translation->create( array( 'translation_set_id' => $set2->id, 'original_id' => $original2->id, 'status' => 'current' ) );

		$translation1->set_as_current(); //calls propagate_across_projects
		$translation2->set_as_current(); //calls propagate_across_projects

		$set3_current_translations = GP::$translation->for_export( $project3, $set3, array('status' => 'current') );

		$this->assertEquals( 1, count( $set3_current_translations ) );
		remove_filter( 'gp_translations_to_other_projects_status', array( $this, '__string_status_current' ) );
	}

	/**
	 * @ticket 327
	 */
	function test_propagate_across_projects_with_missing_permissions() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		// User has only validator permissions for project 1
		GP::$validator_permission->create( array(
			'user_id'     => $user,
			'action'      => 'approve',
			'project_id'  => $set1->project_id,
			'locale_slug' => $set1->locale,
			'set_slug'    => $set1->slug,
		) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1->set_as_current(); //calls propagate_across_projects

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'waiting' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );
	}

	/**
	 * @ticket gh-250
	 */
	function test_propagate_across_projects_with_missing_permissions_does_not_create_duplicates() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		// User has only validator permissions for project 1
		GP::$validator_permission->create( array(
			'user_id'     => $user,
			'action'      => 'approve',
			'project_id'  => $set1->project_id,
			'locale_slug' => $set1->locale,
			'set_slug'    => $set1->slug,
		) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'user_id' => $user, 'status' => 'current' ) );

		// Add the same translation as waiting to another set.
		$translation_waiting = $translation1->fields();
		$translation_waiting[ 'translation_set_id' ] = $set2->id;
		$translation_waiting[ 'original_id' ] = $original2->id;
		$translation_waiting[ 'status' ] = 'waiting';
		$translation2 = $this->factory->translation->create( $translation_waiting );

		$translation1->set_as_current(); //calls propagate_across_projects

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'waiting' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );

		$this->assertEquals( $set2_current_translations[0]->user_login, wp_get_current_user()->user_login );
	}

	/**
	 * @ticket gh-252
	 */
	function test_copy_into_set_uses_equal_waiting_translations() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );
		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id ) );

		// Add the same translation as waiting to another set.
		$translation_waiting = $translation1->fields();
		$translation_waiting[ 'translation_set_id' ] = $set2->id;
		$translation_waiting[ 'original_id' ] = $original2->id;
		$translation_waiting[ 'status' ] = 'waiting';
		$translation2 = $this->factory->translation->create( $translation_waiting );

		$translation1->copy_into_set( $set2->id, $original2->id, 'current' );

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );

		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'waiting' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );
	}
}
