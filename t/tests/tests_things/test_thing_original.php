<?php

global $update_invocation_count;
$update_invocation_count = 0;

class MockOriginal extends GP_Original {
	function update( $data, $where = null ) {
		$GLOBALS['update_invocation_count']++;
		return parent::update( $data, $where );
	}
}

class GP_Test_Thing_Original extends GP_UnitTestCase {

	function create_original_with_update_counter( $original_args = array() ) {
		$project = $this->factory->project->create();
		/* We are doing it this hackish way, because I could not make the PHPUnit mocker to count the update() invocations */
		$mock_original = new MockOriginal;
		$this->factory->original = new GP_UnitTest_Factory_For_Original( $this->factory, $mock_original );
		$original = $this->factory->original->create( array_merge( array( 'project_id' => $project->id ), $original_args ) );
		// the object doesn't retrieve default values, we need to select it back from the database to get them
		$original->reload();
		return array( $project, $original );
	}

	function create_translations_with( $entries ) {
		$translations = new Translations;
		foreach( $entries as $entry ) {
			$translations->add_entry( $entry );
		}
		return $translations;
	}

	function test_import_for_project_should_not_update_unchanged_originals() {
		list( $project, $original ) = $this->create_original_with_update_counter();
		$translations = $this->create_translations_with( array( array( 'singular' => $original->singular ) ) );
		$original->import_for_project( $project, $translations );
		$this->assertEquals( 0, $GLOBALS['update_invocation_count'], 'update should be invoked only 2 times' );
	}

	function test_import_for_project_should_update_changed_originals() {
		list( $project, $original ) = $this->create_original_with_update_counter( array(
			'comment' => 'Some comment'
		) );
		$translations = $this->create_translations_with( array( array( 'singular' => $original->singular ) ) );
		$original->import_for_project( $project, $translations );
		$this->assertEquals( 1, $GLOBALS['update_invocation_count'], 'update should be invoked 3 times' );
	}

	function test_import_for_project_should_update_cache() {
		$project  = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'baba' ) );
		$count    = $original->count_by_project_id( $project->id );

		$translations_array = array( array( 'singular' => $original->singular ), array( 'singular' => 'dyado' ) );
		$translations       = $this->create_translations_with( $translations_array );
		$original->import_for_project( $project, $translations );

		$this->assertEquals( count( $translations_array ), $original->count_by_project_id( $project->id ) );
	}

	function test_is_different_from_should_return_true_if_only_singular_is_for_update_and_it_is_the_same() {
		$original = $this->factory->original->create();
		$this->assertFalse( GP::$original->is_different_from( array( 'singular' => $original->singular ), $original ) );
	}

	function test_is_different_from_should_return_true_if_one_value_is_empty_string_and_the_other_is_null() {
		$original = $this->factory->original->create( array( 'comment' => NULL ) );
		$this->assertFalse( GP::$original->is_different_from( array( 'singular' => $original->singular, 'comment' => '' ), $original ) );
	}

	function test_is_different_from_should_use_this_if_second_argument_is_not_supplied() {
		$original = $this->factory->original->create();
		$data = array( 'singular' => 'baba' );
		$this->assertEquals( GP::$original->is_different_from( $data, $original ), $original->is_different_from( $data )  );
	}

	function test_import_should_leave_unchanged_strings_as_active() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'baba' ) );
		$translations = $this->create_translations_with( array( array( 'singular' => 'baba' ) ) );
		$original->import_for_project( $project, $translations );
		$originals_for_project = $original->by_project_id( $project->id );
		$this->assertEquals( 1, count( $originals_for_project ) );
		$this->assertEquals( 'baba', $originals_for_project[0]->singular );
	}

	function test_import_should_mark_translation_of_changed_strings_as_fuzzy() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba baba' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'current' ) );
		$translations_for_import = $this->create_translations_with( array( array( 'singular' => 'baba baba.' ) ) );

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = $original->import_for_project( $set->project, $translations_for_import );

		$this->assertEquals( 0, $originals_added );
		$this->assertEquals( 0, $originals_existing );
		$this->assertEquals( 1, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );

		$current_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'current'" );
		$fuzzy_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'fuzzy'" );

		$this->assertEquals( 0, count( $current_translations ) );
		$this->assertEquals( 1, count( $fuzzy_translations ) );
	}

	function test_import_should_remove_from_active_missing_strings() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active' ) );
		$original->import_for_project( $project, new Translations );
		$originals_for_project = $original->by_project_id( $project->id );
		$this->assertEquals( 0, count( $originals_for_project ) );
	}

	function test_normalize_fields_should_convert_named_priorities_to_numeric_by_name() {
		$original = new GP_Original;
		$normalized_args = 	$original->normalize_fields( array( 'priority' => 'hidden' ) );
		$this->assertEquals( -2, $normalized_args['priority'] );
	}

	function test_normalize_fields_should_not_convert_numeric_priorities_to_numeric_by_name() {
		$original = new GP_Original;
		$normalized_args = 	$original->normalize_fields( array( 'priority' => '1' ) );
		$this->assertEquals( 1, $normalized_args['priority'] );
	}

	function test_normalize_fields_should_unset_priority_if_named_priority_is_missing() {
		$original = new GP_Original;
		$normalized_args = 	$original->normalize_fields( array( 'priority' => 'baba' ) );
		$this->assertFalse( isset( $normalized_args['priority'] ) );
	}

	function test_by_project_id_and_entry_should_match_case() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'Baba' ) );

		$entry = new stdClass();
		$entry->singular = 'BABA';

		$by_project_id_and_entry = GP::$original->by_project_id_and_entry( $project->id, $entry );
		$this->assertEquals( false, $by_project_id_and_entry );

		$entry->singular = 'Baba';
		$by_project_id_and_entry = GP::$original->by_project_id_and_entry( $project->id, $entry );
		$this->assertSame( $original->singular, $by_project_id_and_entry->singular );
	}

	/**
	 * @ticket 327
	 */
	function test_add_translations_from_other_projects() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'project_one' ) );
		$set2 = $this->factory->translation_set->create_with_project( array( 'locale' => $set1->locale ), array( 'name' => 'project_two' ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'bubu' ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation2 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original2->id, 'status' => 'waiting' ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'bubu' ) );

		$original1->add_translations_from_other_projects();
		$original2->add_translations_from_other_projects();

		$set2_current_translations = GP::$translation->for_export( $set2->project, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );
	}

	/**
	 * @ticket 327
	 */
	function test_add_translations_from_other_projects_with_placeholders_in_original() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'project_one' ) );
		$set2 = $this->factory->translation_set->create_with_project( array( 'locale' => $set1->locale ), array( 'name' => 'project_two' ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => '%s baba', 'plural' => '%s babas' ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current' ) );

		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => '%s baba', 'plural' => '%s babas' ) );
		$original2->add_translations_from_other_projects();

		$set2_current_translations = GP::$translation->for_export( $set2->project, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );
		$this->assertEquals( $translation1->translation_0, $set2_current_translations[0]->translations[0] );
	}

	/**
	 * @ticket 327
	 */
	function test_add_translations_from_other_projects_not_creating_duplicate_translations() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'project_one' ) );
		$set2 = $this->factory->translation_set->create_with_project( array( 'locale' => $set1->locale ), array( 'name' => 'project_two' ) );
		$set3 = $this->factory->translation_set->create_with_project( array( 'locale' => $set1->locale ), array( 'name' => 'project_three' ) );
		$set4 = $this->factory->translation_set->create_with_project( array( 'locale' => $set1->locale ), array( 'name' => 'project_four' ) );

		// Insert first original with a waiting translation in project 1.
		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'waiting' ) );

		// Insert the same original with a current translation in project 2.
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$translation2 = $this->factory->translation->create( array( 'translation_set_id' => $set2->id, 'original_id' => $original2->id, 'status' => 'current' ) );

		// Insert the same original with a current translation in project 3.
		$original3 = $this->factory->original->create( array( 'project_id' => $set3->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$translation3 = $this->factory->translation->create( array( 'translation_set_id' => $set3->id, 'original_id' => $original3->id, 'status' => 'current' ) );

		// Insert the same original with no translation in project 4.
		$original4 = $this->factory->original->create( array( 'project_id' => $set4->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original4->add_translations_from_other_projects();

		// The translation of the fourth original should be equal with the translation in project 3, because it's the newest.
		$set4_current_translations = GP::$translation->for_export( $set4->project, $set4, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set4_current_translations ) );
		$this->assertEquals( $translation3->translation_0, $set4_current_translations[0]->translations[0] );
	}
}
