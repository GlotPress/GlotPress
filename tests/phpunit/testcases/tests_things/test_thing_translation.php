<?php

class GP_Test_Thing_Translation extends GP_UnitTestCase {

	function test_translation_approve_change_status() {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$permission = array( 'user_id' => $user, 'action' => 'approve',
                             'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug );
		GP::$validator_permission->create( $permission );

		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// Put the current count already in the cache
		$set->current_count();

		$translation->set_status('current');
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'current' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( 1, $set->current_count() );
	}

	function test_translation_changesrequested_change_status() {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$permission = array( 'user_id' => $user, 'action' => 'approve',
                             'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug );
		GP::$validator_permission->create( $permission );

		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// Put the current count already in the cache
		$set->current_count();

		$translation->set_status('changesrequested');
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( 1, $set->changesrequested_count() );

		$translation->set_status('current'); // Change the status to current.
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'current' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( 1, $set->current_count() );
		$this->assertEquals( 0, $set->changesrequested_count() );

	}

	function test_translation_denied_approve_change_status() {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$permission = array( 'user_id' => $user, 'action' => 'approve',
                             'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug );
		GP::$validator_permission->create( $permission );

		$cannot_approve_translation = function( $verdict, $args ) {
			return 'approve' !== $args['action'] || 'translation' !== $args['object_type'];
		};

		add_filter( 'gp_pre_can_user', $cannot_approve_translation, 2, 2 );
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// Put the current count already in the cache
		$set->current_count();

		$this->assertFalse( $translation->set_status( 'current' ) );
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache
		remove_filter( 'gp_pre_can_user', $cannot_approve_translation );

		$current_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'current' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );

		$this->assertEquals( 0, count( $current_translations ) );
		$this->assertEquals( 0, $set->current_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, $set->waiting_count() );
	}

	function test_translation_denied_changesrequested_change_status() {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$permission = array( 'user_id' => $user, 'action' => 'approve',
                             'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug );
		GP::$validator_permission->create( $permission );

		$cannot_approve_translation = function( $verdict, $args ) {
			return 'approve' !== $args['action'] || 'translation' !== $args['object_type'];
		};

		add_filter( 'gp_pre_can_user', $cannot_approve_translation, 2, 2 );
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// Put the current count already in the cache
		$set->current_count();

		$this->assertFalse( $translation->set_status( 'changesrequested' ) );
		$set->update_status_breakdown(); // Refresh the counts of the object but not the cache
		remove_filter( 'gp_pre_can_user', $cannot_approve_translation );

		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );

		$this->assertEquals( 0, count( $changesrequested_translations ) );
		$this->assertEquals( 0, $set->changesrequested_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, $set->waiting_count() );
	}

	function test_set_status_should_not_approve_translation_with_errors() {
		$set = $this->create_set_with_validator();

		$original    = $this->factory->original->create( array( 'project_id' => $set->project_id, 'singular' => '%s items' ) );
		$translation = $this->factory->translation->create(
			array(
				'original_id'        => $original->id,
				'translation_set_id' => $set->id,
				'translation_0'      => '%s Elemente in %s',
				'status'             => 'waiting',
			)
		);

		$this->assertFalse( $translation->set_status( 'current' ) );
		$this->assertSame( 'waiting', GP::$translation->get( $translation->id )->status );

		$this->assertTrue( $translation->set_status( 'rejected' ) );
	}

	function test_set_status_should_supersede_current_translation_when_new_one_is_created_as_current() {
		$set = $this->create_set_with_validator();

		$original = $this->factory->original->create( array( 'project_id' => $set->project_id, 'singular' => '%s items' ) );
		$previous = $this->factory->translation->create(
			array(
				'original_id'        => $original->id,
				'translation_set_id' => $set->id,
				'translation_0'      => '%s Elemente',
				'status'             => 'current',
			)
		);
		$created  = GP::$translation->create(
			array(
				'original_id'        => $original->id,
				'translation_set_id' => $set->id,
				'translation_0'      => '%s Elemente in %s',
				'status'             => 'current',
			)
		);

		$this->assertTrue( $created->set_status( 'current' ) );
		$this->assertSame( 'old', GP::$translation->get( $previous->id )->status );
	}

	/**
	 * Creates a translation set and lets the current user approve translations in it.
	 *
	 * @return GP_Translation_Set The translation set.
	 */
	private function create_set_with_validator() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create(
			array(
				'user_id'     => $user,
				'action'      => 'approve',
				'project_id'  => $set->project_id,
				'locale_slug' => $set->locale,
				'set_slug'    => $set->slug,
			)
		);

		return $set;
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
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$permission = array( 'user_id' => $user, 'action' => 'approve',
                             'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug );
		GP::$validator_permission->create( $permission );
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->reject();
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'untranslated' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
	}

	function test_for_translation_accepts_a_scalar_priority_filter() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create( array( 'project_id' => $set->project_id, 'priority' => 1 ) );
		$this->factory->original->create( array( 'project_id' => $set->project_id, 'priority' => 0 ) );

		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => '1' ) );

		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( $original->id, $for_translation[0]->original_id );
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

	function test_for_changesrequested_should_not_include_untranslated_for_single_status() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) ); //This isn't going to be translated

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'changesrequested' ) );
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array('status' => 'changesrequested'), array('by' => 'translation', 'how' => 'asc') );

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

		// We can't be sure which order the for_export call returned the strings, so make sure to compare the right one.
		if ( (int) $for_export[0]->original_id === (int) $translation1->original_id ) {
			$this->assertEquals( $translation1->id, $for_export[0]->id );
		} else {
			$this->assertEquals( $translation1->id, $for_export[1]->id );
		}
	}

	function test_for_export_should_include_changesrequested() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation2 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original2->id, 'status' => 'changesrequested' ) );
		$for_export = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current_or_changesrequested' ) );

		$this->assertEquals( 2, count( $for_export ) );

		// We can't be sure which order the for_export call returned the strings, so make sure to compare the right one.
		if ( (int) $for_export[0]->original_id === (int) $translation1->original_id ) {
			$this->assertEquals( $translation1->id, $for_export[0]->id );
		} else {
			$this->assertEquals( $translation1->id, $for_export[1]->id );
		}
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

		GP::$validator_permission->create( array(
			'user_id' => $user, 'action' => 'approve',
			'project_id' => $set->project_id, 'locale_slug' => $set->locale,
			'set_slug' => $set->slug,
		) );

		$translation->set_as_current();
		$this->assertEquals( $user, $translation->user_id_last_modified );
	}

	function test_validator_id_saved_on_status_change_to_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status( 'waiting' );

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		GP::$validator_permission->create( array(
			'user_id' => $user, 'action' => 'approve',
			'project_id' => $set->project_id, 'locale_slug' => $set->locale,
			'set_slug' => $set->slug,
		) );

		$translation->set_status( 'rejected' );
		$this->assertEquals( $user, $translation->user_id_last_modified );
	}

	function test_validator_id_saved_on_status_change_to_changesrequested() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status( 'waiting' );

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		GP::$validator_permission->create( array(
			'user_id' => $user, 'action' => 'approve',
			'project_id' => $set->project_id, 'locale_slug' => $set->locale,
			'set_slug' => $set->slug,
		) );

		$translation->set_status( 'changesrequested' );
		$this->assertEquals( $user, $translation->user_id_last_modified );
	}

	function test_cannot_reject_translation_without_approve_permission() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$this->assertTrue( $translation->set_status( 'waiting' ) );

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$this->assertFalse( $translation->set_status( 'rejected' ) );
		$this->assertNotEquals( $user, $translation->user_id_last_modified );
	}

	function test_cannot_changesrequested_translation_without_approve_permission() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$this->assertTrue( $translation->set_status( 'waiting' ) );

		$user = $this->factory->user->create();
		wp_set_current_user( $user );
		$translation->set_status( 'changesrequested' );

		$this->assertFalse( $translation->set_status( 'changesrequested' ) );
		$this->assertNotEquals( $user, $translation->user_id_last_modified );
	}

	function test_cannot_approve_translation_without_approve_permission() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$this->assertTrue( $translation->set_status( 'waiting' ) );

		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$this->assertFalse( $translation->set_status( 'current' ) );
		$this->assertNotEquals( $user, $translation->user_id_last_modified );
	}

	public function test_previous_state_is_passed_to_saved_action() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set, array( 'translation_0' => 'Before' ) );
		$initial_translation = clone $translation;

		$previous_translation = null;
		$closure = function( $translation_after, $translation_before ) use ( &$previous_translation ) {
			$previous_translation = $translation_before;
		};

		add_action( 'gp_translation_saved', $closure, 10, 2 );

		$translation->save( array( 'translation_0' => 'After' ) );

		remove_action( 'gp_translation_saved', $closure );

		$this->assertEquals( $initial_translation, $previous_translation );
		$this->assertEquals( $previous_translation->translation_0, 'Before' );
		$this->assertEquals( $translation->translation_0, 'After' );
	}

	/**
	 * @ticket gh-664
	 */
	function test_filter_by_permission() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );

		// A new original has the priority 0.
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0 ) ) );
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '0' )  ) ) ) );

		// Invalid priority is the same as specifying no priority.
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '10' )  ) ) ) );

		// String and numeric values should work.
		$this->assertEquals( 0, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '1' ) ) ) ) );
		$this->assertEquals( 0, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( 1 ) ) ) ) );

		// Now let's modify the priority.
		$translation->original->priority = '1';
		$translation->original->status = '+active';
		$translation->original->save();

		// The modified original should now be found.
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0 ) ) );
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '1' ) ) ) ) );
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '10' ) ) ) ) );
		$this->assertEquals( 0, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '0' ) ) ) ) );

		// Should also work with the hidden priority.
		$translation->original->priority = '-1';
		$translation->original->save();

		$this->assertEquals( 0, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '1' ) ) ) ) );
		$this->assertEquals( 1, count( GP::$translation->for_translation( $set->project, $set, 0, array( 'priority' => array( '-1' ) ) ) ) );
	}

	function test_when_update_a_changedrequested_translation_it_is_set_to_old_status() {
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		wp_set_current_user( $user1 );
		$set = $this->factory->translation_set->create_with_project_and_locale( );
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set, array( 'translation_0' => 'Translation 1', 'user_id' => $user1 ) );
		$translation->set_status( 'waiting' );

		GP::$validator_permission->create( array(
			'user_id' => $user2, 'action' => 'approve',
			'project_id' => $set->project_id, 'locale_slug' => $set->locale,
			'set_slug' => $set->slug,
		) );

		wp_set_current_user( $user2 );
		$translation->set_as_changesrequested();
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( $user2, $translation->user_id_last_modified );
		$this->assertEquals( 1, count( $changesrequested_translations ) );
		$this->assertEquals( 1, $set->changesrequested_count() );
		$this->assertEquals( 0, count( $waiting_translations ) );
		$this->assertEquals( 0, $set->waiting_count() );

		wp_set_current_user( $user1 );
		$translation->save( array( 'translation_0' => 'Translation 2' ) );
		$translation->set_as_waiting();
		gp_clean_translation_set_cache( $set->id ); // Clean the cache.
		$set->update_status_breakdown();            // Refresh the counts of the object.
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( 0, count( $changesrequested_translations ) );
		$this->assertEquals( 0, $set->changesrequested_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, $set->waiting_count() );
	}

	function test_when_update_the_same_changedrequested_translation_two_times_it_is_set_to_old_status() {
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		wp_set_current_user( $user1 );
		$set = $this->factory->translation_set->create_with_project_and_locale( );
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set, array( 'translation_0' => 'Translation 1', 'user_id' => $user1 ) );
		$translation->set_status( 'waiting' );

		GP::$validator_permission->create( array(
			'user_id' => $user2, 'action' => 'approve',
			'project_id' => $set->project_id, 'locale_slug' => $set->locale,
			'set_slug' => $set->slug,
		) );

		wp_set_current_user( $user2 );
		$translation->set_as_changesrequested();
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( $user2, $translation->user_id_last_modified );
		$this->assertEquals( 1, count( $changesrequested_translations ) );
		$this->assertEquals( 1, $set->changesrequested_count() );
		$this->assertEquals( 0, count( $waiting_translations ) );
		$this->assertEquals( 0, $set->waiting_count() );

		wp_set_current_user( $user1 );
		$translation->save( array( 'translation_0' => 'Translation 2' ) );
		$translation->set_as_waiting();
		gp_clean_translation_set_cache( $set->id ); // Clean the cache.
		$set->update_status_breakdown();            // Refresh the counts of the object.
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( 0, count( $changesrequested_translations ) );
		$this->assertEquals( 0, $set->changesrequested_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, $set->waiting_count() );

		wp_set_current_user( $user2 );
		$translation->set_as_changesrequested();
		gp_clean_translation_set_cache( $set->id ); // Clean the cache.
		$set->update_status_breakdown();            // Refresh the counts of the object.
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( $user2, $translation->user_id_last_modified );
		$this->assertEquals( 1, count( $changesrequested_translations ) );
		$this->assertEquals( 1, $set->changesrequested_count() );
		$this->assertEquals( 0, count( $waiting_translations ) );
		$this->assertEquals( 0, $set->waiting_count() );

		wp_set_current_user( $user1 );
		$translation->save( array( 'translation_0' => 'Translation 3' ) );
		$translation->set_as_waiting();
		gp_clean_translation_set_cache( $set->id ); // Clean the cache.
		$set->update_status_breakdown();            // Refresh the counts of the object.
		$changesrequested_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'changesrequested' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );
		$this->assertEquals( 0, count( $changesrequested_translations ) );
		$this->assertEquals( 0, $set->changesrequested_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, $set->waiting_count() );
	}

	function test_when_update_a_waiting_translation_it_is_set_to_old_status() {
		$user = $this->factory->user->create();

		wp_set_current_user( $user );
		$set = $this->factory->translation_set->create_with_project_and_locale( );
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation_old = $this->factory->translation->create( array( 'user_id' => $user, 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertTrue( $translation_old->set_as_waiting() );
		$translation_waiting = $this->factory->translation->create( array( 'user_id' => $user, 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertTrue( $translation_waiting->set_as_waiting() ); //$translation_old is now old

		$old_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'old' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );

		$this->assertEquals( 1, $set->waiting_count() );
		$this->assertEquals( 1, count( $waiting_translations ) );
		$this->assertEquals( 1, count( $old_translations ) );
	}

	function test_when_update_a_waiting_translation_it_is_set_to_old_status_and_dont_set_as_old_the_suggestions_from_other_users() {
		$user1 = $this->factory->user->create();
		$user2 = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale( );
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		wp_set_current_user( $user1 );
		$translation1_old = $this->factory->translation->create( array( 'user_id' => $user1, 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertTrue( $translation1_old->set_as_waiting() );

		wp_set_current_user( $user2 );
		$translation2 = $this->factory->translation->create( array( 'user_id' => $user2, 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertTrue( $translation2->set_as_waiting() );

		wp_set_current_user( $user1 );
		$translation1_waiting = $this->factory->translation->create( array( 'user_id' => $user1, 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertTrue( $translation1_waiting->set_as_waiting() ); //$translation1_old is now old

		$old_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'old' ) );
		$waiting_translations = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'waiting' ) );

		$this->assertEquals( 2, $set->waiting_count() );
		$this->assertEquals( 2, count( $waiting_translations ) );
		$this->assertEquals( 1, count( $old_translations ) );
	}

	function test_for_export_strips_control_characters() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );

		// Created directly, the way an import does, so no blocking error runs.
		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
			'translation_0'      => "Guardar\x04alteracoes",
		) );
		$translation->set_as_current();

		// for_export() is the shared path of the web export route and the
		// WP-CLI export command, so stripping here covers both.
		$entries = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current' ) );

		$this->assertCount( 1, $entries );
		$this->assertSame( 'Guardaralteracoes', $entries[0]->translations[0] );
	}

	/**
	 * Creates a translation of an original in a set, with a given status and date_modified.
	 *
	 * @param GP_Translation_Set $set         The translation set.
	 * @param int                $original_id The original ID.
	 * @param string             $status      The status.
	 * @return GP_Translation The translation.
	 */
	private function create_translation_with_status( $set, $original_id, $status ) {
		global $wpdb;

		$translation = $this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original_id,
				'status'             => $status,
			)
		);
		$wpdb->update( $wpdb->gp_translations, array( 'date_modified' => '2020-01-01 00:00:00' ), array( 'id' => $translation->id ) );

		return GP::$translation->get( $translation->id );
	}

	public function test_set_as_current_supersedes_the_other_translations_of_the_original() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other_set = $this->factory->translation_set->create( array( 'project_id' => $set->project_id, 'locale' => 'de', 'slug' => 'default' ) );
		$original  = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$other     = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$translations = array();
		foreach ( array( 'current', 'waiting', 'fuzzy', 'changesrequested', 'rejected', 'old' ) as $status ) {
			$translations[ $status ] = $this->create_translation_with_status( $set, $original->id, $status );
		}
		$other_original = $this->create_translation_with_status( $set, $other->id, 'current' );
		$other_set_row  = $this->create_translation_with_status( $other_set, $original->id, 'current' );
		$approved       = $this->create_translation_with_status( $set, $original->id, 'waiting' );

		$this->assertTrue( $approved->set_as_current() );

		$this->assertSame( 'current', $approved->status );
		$this->assertEquals( $user, $approved->user_id_last_modified );

		$stored = GP::$translation->get( $approved->id );
		$this->assertSame( 'current', $stored->status );
		$this->assertEquals( $user, $stored->user_id_last_modified );
		$this->assertNotSame( '2020-01-01 00:00:00', $stored->date_modified );

		$expected = array(
			'current'          => 'old',
			'waiting'          => 'old',
			'fuzzy'            => 'old',
			'changesrequested' => 'old',
			'rejected'         => 'rejected',
			'old'              => 'old',
		);
		foreach ( $expected as $status => $expected_status ) {
			$stored = GP::$translation->get( $translations[ $status ]->id );
			$this->assertSame( $expected_status, $stored->status, "The $status translation." );

			// Only the superseded translations are modified.
			$superseded = $status !== $expected_status;
			$this->assertSame( ! $superseded, '2020-01-01 00:00:00' === $stored->date_modified, "The date_modified of the $status translation." );
			$this->assertNotEquals( $user, $stored->user_id_last_modified, "The user_id_last_modified of the $status translation." );
		}

		// Other originals and other translation sets are left alone.
		$this->assertSame( 'current', GP::$translation->get( $other_original->id )->status );
		$this->assertSame( 'current', GP::$translation->get( $other_set_row->id )->status );
	}

	public function test_set_as_current_leaves_a_single_current_translation() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		// Two current translations of the same original, as left by two approvals at the same time.
		$first  = $this->create_translation_with_status( $set, $original->id, 'current' );
		$second = $this->create_translation_with_status( $set, $original->id, 'current' );

		$this->assertTrue( $first->set_as_current() );

		$this->assertSame( 'current', GP::$translation->get( $first->id )->status );
		$this->assertSame( 'old', GP::$translation->get( $second->id )->status );
	}

	public function test_set_as_current_fires_translation_saved_with_the_previous_status() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status( 'waiting' );

		$saved = array();
		add_action(
			'gp_translation_saved',
			function ( $translation, $translation_before ) use ( &$saved ) {
				$saved[] = array( $translation->status, $translation_before->status );
			},
			10,
			2
		);

		$translation->set_as_current();

		$this->assertSame( array( array( 'current', 'waiting' ) ), $saved );
	}

	public function test_set_as_current_clears_the_status_breakdown_cache() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->set_status( 'waiting' );

		// Put the counts in the cache.
		$this->assertSame( 0, $set->current_count() );

		$translation->set_as_current();

		$set = GP::$translation_set->get( $set->id );
		$this->assertSame( 1, $set->current_count() );
	}

	public function test_set_as_current_applies_the_prepare_for_save_filter() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$original    = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$superseded  = $this->create_translation_with_status( $set, $original->id, 'current' );
		$translation = $this->create_translation_with_status( $set, $original->id, 'waiting' );

		add_filter(
			'gp_translation_prepare_for_save',
			function ( $args ) {
				$args['warnings'] = 'current' === $args['status'] ? array( 0 => array( 'test' => 'Promoted.' ) ) : array( 0 => array( 'test' => 'Superseded.' ) );
				return $args;
			}
		);

		$translation->set_as_current();

		$this->assertSame( array( 0 => array( 'test' => 'Promoted.' ) ), GP::$translation->get( $translation->id )->warnings );
		$this->assertSame( array( 0 => array( 'test' => 'Superseded.' ) ), GP::$translation->get( $superseded->id )->warnings );
	}
}
