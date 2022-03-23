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

	/**
	 * @ticket gh-301
	 */
	function test_original_should_validate_if_singular_is_zero() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => '0', 'priority' => -1 ) );
		$this->assertTrue( $original->validate() );
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

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = $original->import_for_project( $set->project, $translations_for_import );

		$this->assertEquals( 0, $originals_added );
		$this->assertEquals( 0, $originals_existing );
		$this->assertEquals( 1, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );
		$this->assertEquals( 0, $originals_error );

		$current_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'current'" );
		$fuzzy_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'fuzzy'" );

		$this->assertEquals( 0, count( $current_translations ) );
		$this->assertEquals( 1, count( $fuzzy_translations ) );
	}

	function test_import_should_not_mark_translation_of_changed_strings_as_fuzzy_with_filter() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba baba' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'current' ) );
		$translations_for_import = $this->create_translations_with( array( array( 'singular' => 'baba baba.' ) ) );

		add_filter( 'gp_set_translations_for_original_to_fuzzy', '__return_false' );

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = $original->import_for_project( $set->project, $translations_for_import );

		remove_filter( 'gp_set_translations_for_original_to_fuzzy', '__return_false' );

		$this->assertEquals( 0, $originals_added );
		$this->assertEquals( 1, $originals_existing );
		$this->assertEquals( 0, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );
		$this->assertEquals( 0, $originals_error );

		$current_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'current'" );
		$fuzzy_translations = GP::$translation->find_many( "original_id = '{$original->id}' AND status = 'fuzzy'" );

		$this->assertEquals( 1, count( $current_translations ) );
		$this->assertEquals( 0, count( $fuzzy_translations ) );
	}

	/**
	 * @ticket 508
	 */
	function test_import_should_clean_project_count_cache() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+obsolete', 'singular' => 'baba baba' ) );

		$count = $original->count_by_project_id( $set->project->id );
		$this->assertEquals( 0, $count );

		$translations_for_import = $this->create_translations_with( array( array( 'singular' => 'baba baba' ) ) );

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = $original->import_for_project( $set->project, $translations_for_import );

		$this->assertEquals( 0, $originals_added );
		$this->assertEquals( 1, $originals_existing );
		$this->assertEquals( 0, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );
		$this->assertEquals( 0, $originals_error );

		$count = $original->count_by_project_id( $set->project->id );
		$this->assertEquals( 1, $count );
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
	 * @ticket gh-302
	 */
	function test_import_for_project_with_context_which_exceeds_the_maximum_length_of_255() {
		$project = $this->factory->project->create();

		// Insert an original with a context with 255 chars. It shouldn't be removed.
		$this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'foo', 'context' => str_repeat( 'a', 255 ) ) );

		/*
		 * Create three originals.
		 * 1) Same as existing original, but with 256 chars.
		 * 2) New original wit 256 chars.
		 * 3) New original with 255 chars.
		 */
		$test_originals = array(
			array( 'singular' => 'foo', 'context' => str_repeat( 'a', 256 ) ),
			array( 'singular' => 'bar', 'context' => str_repeat( 'b', 256 ) ),
			array( 'singular' => 'bab', 'context' => str_repeat( 'c', 255 ) ),
		);

		// First import:

		$translations_for_import = $this->create_translations_with( $test_originals );
		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = GP::$original->import_for_project( $project, $translations_for_import );

		// Only two new originals.
		$this->assertEquals( 2, $originals_added );
		$this->assertEquals( 0, $originals_existing );
		$this->assertEquals( 0, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );
		$this->assertEquals( 0, $originals_error );

		$originals = GP::$original->by_project_id( $project->id );
		$this->assertCount( 3, $originals );

		// Second import:

		$translations_for_import = $this->create_translations_with( $test_originals );
		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = GP::$original->import_for_project( $project, $translations_for_import );

		// Originals are already imported, no change.
		$this->assertEquals( 0, $originals_added );
		$this->assertEquals( 0, $originals_existing );
		$this->assertEquals( 0, $originals_fuzzied );
		$this->assertEquals( 0, $originals_obsoleted );
		$this->assertEquals( 0, $originals_error );

		$originals = GP::$original->by_project_id( $project->id );
		$this->assertCount( 3, $originals );

		// Get the first item.
		$original = reset( $originals );
		$this->assertSame( str_repeat( 'a', 255 ), $original->context );
	}

	/**
	 * @ticket gh-397
	 */
	function test_return_types_of_count_by_project_id() {
		$project = $this->factory->project->create();

		$this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'Foo' ) );
		$this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'Bar', 'priority' => -2 ) );

		$total = GP::$original->count_by_project_id( $project->id );
		$this->assertSame( 2, $total );

		$all = GP::$original->count_by_project_id( $project->id, 'all' );
		$this->assertInternalType( 'object', $all );
		$this->assertSame( 2, $all->total );
		$this->assertSame( 1, $all->hidden );
		$this->assertSame( 1, $all->public );

		$hidden = GP::$original->count_by_project_id( $project->id, 'hidden' );
		$this->assertSame( 1, $hidden );

		$public = GP::$original->count_by_project_id( $project->id, 'public' );
		$this->assertSame( 1, $public );

		$non_existent = GP::$original->count_by_project_id( $project->id, 'non_existent' );
		$this->assertSame( 0, $non_existent );
	}

	public function test_previous_state_is_passed_to_saved_action() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'Before' ) );
		$initial_original = clone $original;

		$previous_original = null;
		$closure = function( $original_after, $original_before ) use ( &$previous_original ) {
			$previous_original = $original_before;
		};

		add_action( 'gp_original_saved', $closure, 10, 2 );

		$original->save( array( 'singular' => 'After' ) );

		remove_action( 'gp_original_saved', $closure );

		$this->assertEquals( $initial_original, $previous_original );
		$this->assertEquals( $previous_original->singular, 'Before' );
		$this->assertEquals( $original->singular, 'After' );
	}

	public function test_delete() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		$original = GP::$original->get( $translation->original_id );
		$this->assertSame( 1, $original->delete() );

		// Check if translations are deleted too.
		$translation = GP::$translation->find_one( array( 'id' => $translation->id ) );
		$this->assertFalse( $translation );
	}

	/**
	 * @ticket gh-1340
	 */
	function test_import_should_respect_priority_in_flags() {
		$project = $this->factory->project->create();
		$original = $this->factory->original->create( array( 'project_id' => $project->id, 'status' => '+active', 'singular' => 'baba' ) );

		$translations = $this->create_translations_with(
			array(
				array( 'singular' => 'baba', 'flags' => array( 'gp-priority: low' ) ),
				array( 'singular' => 'baba baba' ),
				array( 'singular' => 'baba baba baba', 'flags' => array( 'gp-priority: high' ) ),
				array( 'singular' => 'priority flag should be ignored.', 'flags' => array( 'gp-priority: unexpected' ) ),
			)
		);

		$original->import_for_project( $project, $translations );

		$originals_for_project = $original->by_project_id( $project->id );
		$this->assertEquals( 4, count( $originals_for_project ) );

		$this->assertEquals( -1, $originals_for_project[0]->priority, 'Existing string should have been updated to be low-priority.' );
		$this->assertEquals( 0, $originals_for_project[1]->priority, 'New string should have imported as normal priority.' );
		$this->assertEquals( 1, $originals_for_project[2]->priority, 'New string should have imported as high priority.' );
		$this->assertEquals( 0, $originals_for_project[3]->priority, 'New string with invalid priority flag should have imported as normal priority.' );
	}

}
