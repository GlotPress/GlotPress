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

	public function test_import_skips_waiting_identical_to_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Apple',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Apfel',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Apple',
					'translations' => array( 'Apfel' ),
				)
			)
		);

		$added = $set->import( $translations, 'waiting' );

		$this->assertSame( 0, $added );
		$this->assertSame( 0, $set->waiting_count() );
	}

	public function test_import_skips_waiting_identical_to_changesrequested() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Banana',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'changesrequested',
				'translation_0'      => 'Banane',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Banana',
					'translations' => array( 'Banane' ),
				)
			)
		);

		$added = $set->import( $translations, 'waiting' );

		$this->assertSame( 0, $added );
		$this->assertSame( 0, $set->waiting_count() );
	}

	public function test_import_skips_fuzzy_identical_to_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Grape',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Traube',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Grape',
					'translations' => array( 'Traube' ),
				)
			)
		);

		$added = $set->import( $translations, 'fuzzy' );

		$this->assertSame( 0, $added );
		$this->assertSame( 0, $set->fuzzy_count() );
	}

	public function test_import_creates_waiting_different_from_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Orange',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Apfelsine',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Orange',
					'translations' => array( 'Orange' ),
				)
			)
		);

		$added = $set->import( $translations, 'waiting' );

		$this->assertSame( 1, $added );
		$this->assertSame( 1, $set->waiting_count() );
	}

	public function test_import_creates_current_identical_to_rejected() {
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

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Peach',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Pfirsich',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Peach',
					'translations' => array( 'Pfirsich' ),
				)
			)
		);

		$added = $set->import( $translations, 'current' );

		$this->assertSame( 1, $added );
		$this->assertSame( 1, $set->current_count() );
	}

	public function test_import_creates_when_skip_rejected_filter_returns_false() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Cherry',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Kirsche',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Cherry',
					'translations' => array( 'Kirsche' ),
				)
			)
		);

		add_filter( 'gp_translation_set_import_skip_rejected_or_changesrequested', '__return_false' );
		$added = $set->import( $translations, 'waiting' );
		remove_filter( 'gp_translation_set_import_skip_rejected_or_changesrequested', '__return_false' );

		$this->assertSame( 1, $added );
		$this->assertSame( 1, $set->waiting_count() );
	}

	public function test_import_skips_plural_matching_rejected() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => '%d file',
				'plural'     => '%d files',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => '%d Datei',
				'translation_1'      => '%d Dateien',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => '%d file',
					'plural'       => '%d files',
					'translations' => array( '%d Datei', '%d Dateien' ),
				)
			)
		);

		$added = $set->import( $translations, 'waiting' );

		$this->assertSame( 0, $added );
		$this->assertSame( 0, $set->waiting_count() );
	}

	public function test_import_skips_current_with_warnings_downgraded_to_waiting_when_rejected_exists() {
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

		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project_id,
				'singular'   => 'Hello %s',
			)
		);

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'rejected',
				'translation_0'      => 'Hallo',
			)
		);

		$translations = new Translations();
		$translations->add_entry(
			new Translation_Entry(
				array(
					'singular'     => 'Hello %s',
					'translations' => array( 'Hallo' ),
				)
			)
		);

		$added = $set->import( $translations, 'current' );

		$this->assertSame( 0, $added );
		$this->assertSame( 0, $set->current_count() );
		$this->assertSame( 0, $set->waiting_count() );
	}
}
