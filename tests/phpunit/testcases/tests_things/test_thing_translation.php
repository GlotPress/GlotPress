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
	 *
	 * @covers GP_Translation::restrict_fields
	 */
	function test_translation_should_not_validate_with_empty_plurals() {
		$data = array(
			'user_id'            => 1,
			'original_id'        => 1,
			'translation_set_id' => 1,
			'status'             => 'current',
		);
		$plurals = array(
			'translation_0' => 'Zero',
			'translation_1' => '',
			'translation_2' => '',
			'translation_3' => '',
			'translation_4' => '',
			'translation_5' => '',
		);

		$data = array_merge( $data, $plurals );

		$translation = $this->factory->translation->create( $data );
		$this->assertFalse( $translation->validate() );
		$this->assertCount( 5, $translation->errors );
	}

	/**
	 * @ticket gh-341
	 */
	function test_translation_should_not_report_empty_translation_set_id_as_translation_value_error() {
		$data = array(
			'user_id'            => 1,
			'original_id'        => 1,
			'status'             => 'current',
		);
		$plurals = array(
			'translation_0' => 'Zero',
			'translation_1' => '',
			'translation_2' => '',
			'translation_3' => '',
			'translation_4' => '',
			'translation_5' => '',
		);

		$data = array_merge( $data, $plurals );

		$translation = $this->factory->translation->create( $data );
		$this->assertFalse( $translation->validate() );
		$this->assertNotEquals( 'The textarea <strong>Translation 1</strong> is invalid and should be positive int!', $translation->errors[0] );
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

	function test_for_translation_should_respect_priorities() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id, 'priority' => 1 ) );

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'untranslated' ) );
		$this->assertEquals( 2, count( $for_translation ) );

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'untranslated', 'priority' => array( 1 ) ) );
		$this->assertEquals( 1, count( $for_translation ) );
	}

	function test_for_export_should_include_untranslated() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_export = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current_or_untranslated' ) );

		$this->assertEquals( 2, count( $for_export ) );
		$this->assertEquals( $translation1->id, $for_export[1]->id );
	}

	function test_delete() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		$pre_delete = GP::$translation->find_one( array( 'id' => $translation->id ) );

		$translation->delete();

		$post_delete = GP::$translation->find_one( array( 'id' => $translation->id ) );

		$this->assertFalse( empty( $pre_delete ) );
		$this->assertNotEquals( $pre_delete, $post_delete );
	}

	function test_validator_id_saved_on_status_change_to_current() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status('waiting');

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'whatever',
		                                          'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug ) );

		$translation->set_as_current();
		$this->assertEquals( $user, $translation->user_id_last_modified );
	}

	function test_validator_id_saved_on_status_change_to_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status('waiting');

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'whatever',
		                                          'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug ) );

		$translation->set_status('rejected');
		$this->assertEquals( $user, $translation->user_id_last_modified );
	}

}
