<?php

class GP_Import extends GP_UnitTestCase {

	/**
	 * @ticket gh-377
	 */
	private function _verify_multiple_imports( $originals, $runs ) {
		$object_type = GP::$validator_permission->object_type;
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'approve',
		                                          'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug ) );

		if ( isset( $originals['singular'] ) ) {
			$originals = array( $originals );
		}

		foreach ( $originals as $original ) {
			$o = $this->factory->original->create( array_merge( array(
				'project_id' => $set->project_id,
				'status'     => '+active',
			), $original ) );
		}

		$this->assertEquals( count( $originals ), $set->all_count() );
		$this->assertEquals( 0, $set->current_count() );
		$this->assertEquals( count( $originals ), $set->untranslated_count() );

		$status_sequence = '';
		foreach ( $runs as $run ) {
			$status_sequence .= $run['status'] . '|';
			$set->import( $run['translations'], $run['status'] );

			wp_cache_flush();
			$set->update_status_breakdown();

			foreach ( $run['counts'] as $function => $count ) {
				$this->assertEquals( $count, $set->$function(), $status_sequence . $function . '()' );
			}
		}
	}

	function test_multiple_imports_singular() {
		$original = array(
			'singular'   => 'Good morning',
		);

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => $original['singular'],
			'translations' => array( 'Guten Morgen' ),
		)));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
		));
	}

	function test_multiple_imports_plural() {
		$original = array(
			'singular'   => '%d apple',
			'plural'   => '%d apples',
		);

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => $original['singular'],
			'plural' => $original['plural'],
			'translations' => array( '%d Apfel', '%d Äpfel' ),
		)));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $original, array(
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations,
				'counts' => array(
					'all_count' => 1,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
		));
	}


	/**
	 * @ticket gh-710
	 */
	function test_import_identical_to_waiting_approves_and_keeps_translator_credit() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( $validator );
		$set->import( $translations, 'current' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertEquals( $waiting->id, $current->id, 'The waiting translation should have been approved, not replaced.' );
		$this->assertEquals( $translator, (int) $current->user_id, 'Credit should remain with the original translator.' );
		$this->assertEquals( $validator, (int) $current->user_id_last_modified, 'The importer should be recorded as approver.' );
	}

	/**
	 * An original that is already translated must still approve an identical waiting suggestion
	 * instead of creating a third row credited to the importer.
	 *
	 * @ticket gh-710
	 */
	function test_import_identical_to_waiting_approves_when_original_already_current() {
		$translator     = $this->factory->user->create();
		$old_translator = $this->factory->user->create();
		$validator      = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$existing_current = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Tag',
			'user_id'            => $old_translator,
			'status'             => 'current',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( $validator );
		$set->import( $translations, 'current' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertEquals( $waiting->id, $current->id, 'The waiting translation should have been approved, not replaced.' );
		$this->assertEquals( $translator, (int) $current->user_id, 'Credit should remain with the original translator.' );
		$this->assertEquals( $validator, (int) $current->user_id_last_modified, 'The importer should be recorded as approver.' );

		$superseded = GP::$translation->get( $existing_current->id );
		$this->assertEquals( 'old', $superseded->status, 'The previously current translation should have been superseded.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 2, $all, 'No third translation should have been created.' );
	}

	/**
	 * The waiting translations of an original are compared one by one, so a matching suggestion
	 * is found even when the original carries more than one waiting translation.
	 *
	 * @ticket gh-710
	 */
	function test_import_matches_waiting_translation_among_several_of_same_original() {
		$translator_a = $this->factory->user->create();
		$translator_b = $this->factory->user->create();
		$validator    = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		/*
		 * The matching suggestion is added first on purpose: a Translations collection is keyed
		 * by context and singular, so it keeps only the last waiting row of the original and the
		 * earlier matching one would never be seen.
		 */
		$waiting_a = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator_a,
			'status'             => 'waiting',
		) );

		$waiting_b = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Moin',
			'user_id'            => $translator_b,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( $validator );
		$set->import( $translations, 'current' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertEquals( $waiting_a->id, $current->id, 'The matching waiting translation should have been approved.' );
		$this->assertEquals( $translator_a, (int) $current->user_id, 'Credit should remain with the matching translator.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 2, $all, 'No new translation should have been created.' );

		// Approving demotes every other waiting translation of the original, exactly like the UI does.
		$other = GP::$translation->get( $waiting_b->id );
		$this->assertEquals( 'old', $other->status );
	}

	/**
	 * The plural forms of a waiting translation are compared up to the number of plurals of the locale.
	 *
	 * @ticket gh-710
	 */
	function test_import_identical_to_waiting_approves_plural_string() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'One comment',
			'plural'     => '%d comments',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Ein Kommentar',
			'translation_1'      => '%d Kommentare',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'One comment',
			'plural'       => '%d comments',
			'translations' => array( 'Ein Kommentar', '%d Kommentare' ),
		) ) );

		wp_set_current_user( $validator );
		$set->import( $translations, 'current' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertEquals( $waiting->id, $current->id, 'The waiting plural translation should have been approved.' );
		$this->assertEquals( $translator, (int) $current->user_id );
	}

	/**
	 * The `gp_translation_set_import_approve_waiting` filter opts out of the approval.
	 *
	 * @ticket gh-710
	 */
	function test_import_approve_waiting_filter_false_creates_new_translation() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( $validator );
		add_filter( 'gp_translation_set_import_approve_waiting', '__return_false' );
		$set->import( $translations, 'current' );
		remove_filter( 'gp_translation_set_import_approve_waiting', '__return_false' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertNotEquals( $waiting->id, $current->id, 'A new translation should have been created.' );
		$this->assertEquals( $validator, (int) $current->user_id );

		$superseded = GP::$translation->get( $waiting->id );
		$this->assertEquals( 'old', $superseded->status );
	}

	/**
	 * Approving a waiting translation supersedes the current one, so it must not happen when
	 * `gp_translation_set_import_over_existing` has refused to import over that translation.
	 *
	 * @ticket gh-710
	 */
	function test_import_over_existing_filter_false_does_not_approve_waiting_translation() {
		$translator     = $this->factory->user->create();
		$old_translator = $this->factory->user->create();
		$validator      = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$existing_current = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Tag',
			'user_id'            => $old_translator,
			'status'             => 'current',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( $validator );
		add_filter( 'gp_translation_set_import_over_existing', '__return_false' );
		$translations_added = $set->import( $translations, 'current' );
		remove_filter( 'gp_translation_set_import_over_existing', '__return_false' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertEquals( $existing_current->id, $current->id, 'The current translation should have been left alone.' );
		$this->assertEquals( 'Guten Tag', $current->translation_0 );

		$still_waiting = GP::$translation->get( $waiting->id );
		$this->assertEquals( 'waiting', $still_waiting->status, 'The waiting translation should not have been approved.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 2, $all, 'Nothing should have been created.' );

		$this->assertSame( 0, $translations_added, 'Nothing was imported.' );
	}

	/**
	 * Approving a waiting translation replaces the second `gp_translation_set_import_status` call
	 * of the create path, so a filter that downgrades the imported string must be able to stop it.
	 *
	 * @ticket gh-710
	 */
	function test_import_status_filter_can_prevent_approving_waiting_translation() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		$downgrade = function ( $status, $entry, $old_translation ) {
			return $old_translation ? 'waiting' : $status;
		};

		wp_set_current_user( $validator );
		add_filter( 'gp_translation_set_import_status', $downgrade, 10, 3 );
		$set->import( $translations, 'current' );
		remove_filter( 'gp_translation_set_import_status', $downgrade, 10 );

		$still_waiting = GP::$translation->get( $waiting->id );
		$this->assertEquals( 'waiting', $still_waiting->status, 'The filter refused the current status, so nothing should have been approved.' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );
		$this->assertFalse( $current, 'No translation should have become current.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 2, $all, 'The import falls through to the create path, with the status the filter asked for.' );
	}

	/**
	 * A filter that leaves the status alone still gets to see the waiting translation that is
	 * about to be approved, and the approval happens.
	 *
	 * @ticket gh-710
	 */
	function test_import_status_filter_sees_the_waiting_translation_and_approval_happens() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		$old_translation_ids = array();
		$record              = function ( $status, $entry, $old_translation ) use ( &$old_translation_ids ) {
			$old_translation_ids[] = $old_translation ? (int) $old_translation->id : 0;
			return $status;
		};

		wp_set_current_user( $validator );
		add_filter( 'gp_translation_set_import_status', $record, 10, 3 );
		$set->import( $translations, 'current' );
		remove_filter( 'gp_translation_set_import_status', $record, 10 );

		$this->assertContains( (int) $waiting->id, $old_translation_ids, 'The filter should have been called with the waiting translation.' );

		$approved = GP::$translation->get( $waiting->id );
		$this->assertEquals( 'current', $approved->status, 'The filter left the status alone, so the approval should have happened.' );
		$this->assertEquals( $translator, (int) $approved->user_id, 'Credit should remain with the original translator.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 1, $all, 'Nothing should have been created.' );
	}

	/**
	 * Approving goes through `set_status( 'current' )`, so an import without a current user,
	 * a WP-CLI import for example, keeps the previous behaviour of creating a new translation.
	 *
	 * @ticket gh-710
	 */
	function test_import_without_current_user_creates_new_translation() {
		$translator = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		wp_set_current_user( 0 );
		$set->import( $translations, 'current' );

		$still_waiting = GP::$translation->get( $waiting->id );
		$this->assertEquals( 'waiting', $still_waiting->status, 'Without a current user nothing can be approved.' );

		$all = GP::$translation->find_many( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
		) );
		$this->assertCount( 2, $all, 'A new translation should have been created.' );
	}

	/**
	 * An approved waiting translation was not created, so `gp_translations_imported` reports it
	 * through `$approved_translation_ids` and not through `$created_translation_ids`.
	 *
	 * @ticket gh-710
	 */
	function test_gp_translations_imported_reports_approved_waiting_ids_separately() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		$created_translation_ids  = null;
		$approved_translation_ids = null;
		$closure = function( $set_id, $created = null, $approved = null ) use ( &$created_translation_ids, &$approved_translation_ids ) {
			$created_translation_ids  = $created;
			$approved_translation_ids = $approved;
		};
		add_action( 'gp_translations_imported', $closure, 10, 3 );

		wp_set_current_user( $validator );
		$translations_added = $set->import( $translations, 'current' );

		remove_action( 'gp_translations_imported', $closure );

		$this->assertSame( array(), $created_translation_ids, 'An approval creates nothing, so no ID is reported as created.' );
		$this->assertSame( array( $waiting->id ), $approved_translation_ids, 'The approved translation is reported separately.' );
		$this->assertContainsOnly( 'int', $approved_translation_ids );
		$this->assertSame( 1, $translations_added, 'The approval still counts towards the imported total.' );
	}

	/**
	 * A single import can both create and approve, and `gp_translations_imported` keeps the two
	 * sets apart so that a credit consumer can tell them apart again.
	 *
	 * @ticket gh-710
	 */
	function test_gp_translations_imported_reports_created_and_approved_separately() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$waiting_original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$untranslated_original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good evening',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $waiting_original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good evening',
			'translations' => array( 'Guten Abend' ),
		) ) );

		$created_translation_ids  = null;
		$approved_translation_ids = null;
		$closure = function( $set_id, $created = null, $approved = null ) use ( &$created_translation_ids, &$approved_translation_ids ) {
			$created_translation_ids  = $created;
			$approved_translation_ids = $approved;
		};
		add_action( 'gp_translations_imported', $closure, 10, 3 );

		wp_set_current_user( $validator );
		$translations_added = $set->import( $translations, 'current' );

		remove_action( 'gp_translations_imported', $closure );

		$created = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $untranslated_original->id,
		) );

		$this->assertSame( array( $created->id ), $created_translation_ids, 'Only the new row is reported as created.' );
		$this->assertSame( array( $waiting->id ), $approved_translation_ids, 'Only the approved row is reported as approved.' );
		$this->assertSame( array(), array_intersect( $created_translation_ids, $approved_translation_ids ), 'The two sets are disjoint.' );
		$this->assertSame( 2, $translations_added, 'Both entries count towards the imported total.' );
	}

	/**
	 * @ticket gh-710
	 */
	function test_import_different_from_waiting_still_creates_new_translation() {
		$translator = $this->factory->user->create();
		$validator  = $this->factory->user->create();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'     => $validator,
			'action'      => 'approve',
			'project_id'  => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'    => $set->slug,
		) );

		$original = $this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$waiting = $this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'translation_0'      => 'Guten Morgen',
			'user_id'            => $translator,
			'status'             => 'waiting',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Tag' ),
		) ) );

		wp_set_current_user( $validator );
		$set->import( $translations, 'current' );

		$current = GP::$translation->find_one( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );

		$this->assertNotEquals( $waiting->id, $current->id );
		$this->assertEquals( 'Guten Tag', $current->translation_0 );
		$this->assertEquals( $validator, (int) $current->user_id );
	}

	function test_multiple_imports_multiple_singulars() {
		$originals = array(
			array(
				'singular'   => 'Good morning',
			),
			array(
				'singular'   => 'Good evening',
			),
		);

		$translations1 = new Translations();
		$translations1->add_entry( new Translation_Entry( array(
			'singular' => $originals[0]['singular'],
			'translations' => array( 'Guten Morgen' ),
		)));

		$translations2 = new Translations();
		$translations2->add_entry( new Translation_Entry( array(
			'singular' => $originals[1]['singular'],
			'translations' => array( 'Guten Abend' ),
		)));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 2,
					'untranslated_count' => 0,
					'waiting_count' => 0,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 0,
					'waiting_count' => 1,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'waiting',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'waiting',
				'translations' => $translations2,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 0,
					'waiting_count' => 2,
				),
			),
		));

		$this->_verify_multiple_imports( $originals, array(
			array(
				'status' => 'waiting',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 0,
					'untranslated_count' => 1,
					'waiting_count' => 1,
				),
			),
			array(
				'status' => 'current',
				'translations' => $translations1,
				'counts' => array(
					'all_count' => 2,
					'current_count' => 1,
					'untranslated_count' => 1,
					'waiting_count' => 0,
				),
			),
		));
	}

	/**
	 * The `gp_translations_imported` action should pass the IDs of the created translations.
	 *
	 * @ticket gh-1467
	 */
	function test_gp_translations_imported_passes_created_translation_ids() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		GP::$validator_permission->create( array(
			'user_id'    => $user,
			'action'     => 'approve',
			'project_id' => $set->project_id,
			'locale_slug' => $set->locale,
			'set_slug'   => $set->slug,
		) );

		$this->factory->original->create( array(
			'project_id' => $set->project_id,
			'status'     => '+active',
			'singular'   => 'Good morning',
		) );

		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		$action_args = array();
		$closure = function( $set_id, $created_translation_ids = null ) use ( &$action_args ) {
			$action_args = array( $set_id, $created_translation_ids );
		};
		add_action( 'gp_translations_imported', $closure, 10, 2 );

		$set->import( $translations );

		remove_action( 'gp_translations_imported', $closure );

		$this->assertSame( $set->id, $action_args[0] );
		$this->assertIsArray( $action_args[1] );
		$this->assertCount( 1, $action_args[1] );
		$this->assertContainsOnly( 'int', $action_args[1] );
		$this->assertGreaterThan( 0, $action_args[1][0] );
	}

	/**
	 * The `gp_translations_imported` action should pass an empty array when no translations are created.
	 *
	 * @ticket gh-1467
	 */
	function test_gp_translations_imported_passes_empty_array_when_nothing_created() {
		$user = $this->factory->user->create();
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();

		// No originals exist, so nothing can be created from the import.
		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Good morning',
			'translations' => array( 'Guten Morgen' ),
		) ) );

		$created_translation_ids = null;
		$closure = function( $set_id, $ids = null ) use ( &$created_translation_ids ) {
			$created_translation_ids = $ids;
		};
		add_action( 'gp_translations_imported', $closure, 10, 2 );

		$set->import( $translations );

		remove_action( 'gp_translations_imported', $closure );

		$this->assertSame( array(), $created_translation_ids );
	}

}
