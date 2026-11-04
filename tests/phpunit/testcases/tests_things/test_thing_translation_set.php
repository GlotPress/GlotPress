<?php

class GP_Test_Thing_Translation_set extends GP_UnitTestCase {

	function test_copy_translations_from_should_copy_into_empty_set() {
		$source_set = $this->factory->translation_set->create();
		$destination_set = $this->factory->translation_set->create();
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $source_set->id ) );
		$destination_set->copy_translations_from( $source_set->id );
		$destination_set_translations = GP::$translation->find( array( 'translation_set_id' => $destination_set->id ) );

		$this->assertEquals( 1, count( $destination_set_translations ) );
		$this->assertEqualFields( $destination_set_translations[0],
			array( 'translation_0' => $translation->translation_0, 'translation_set_id' => $destination_set->id, 'original_id' => $translation->original_id )
		);
	}

	function test_import_should_save_user_info() {
		$user = $this->factory->user->create( array( 'user_login' => 'pijo' ) );
		wp_set_current_user( $user );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string','translations' => array( 'baba' ) ) );
		$set->import( $translations_for_import );

		$translations = GP::$translation->all();
		$this->assertEquals( $translations[0]->user_id, $user );
	}

	function test_import_should_save_fuzzy() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Second string' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'Baba' ), 'flags' => array('fuzzy' ) ) );
		$translations_for_import->add_entry( array( 'singular' => 'Second string', 'translations' => array( 'Second' ) ) );
		$set->import( $translations_for_import );

		$translations = GP::$translation->all();

		$this->assertEquals( $translations[0]->status, 'fuzzy' );
		$this->assertEquals( $translations[1]->status, 'current' );
	}

	function test_import_should_save_forced_fuzzy() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Second string' ) );

		$fuzzy_import = new Translations;
		$fuzzy_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'Baba' ) ) );

		$current_import = new Translations;
		$current_import->add_entry( array( 'singular' => 'Second string', 'translations' => array( 'Second' ) ) );

		$set->import( $fuzzy_import, 'fuzzy' );
		$set->import( $current_import );

		$translations = GP::$translation->all();

		$this->assertEquals( $translations[0]->status, 'fuzzy' );
		$this->assertEquals( $translations[1]->status, 'current' );
	}

	function test_import_should_skip_existing_when_importing_fuzzy() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );

		$fuzzy_import = new Translations;
		$fuzzy_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'baba' ) ) );

		$translations_added = $set->import( $fuzzy_import, 'fuzzy' );
		$this->assertEquals( $translations_added, 1 );

		// Do the import again, it should not go through.
		$translations_added = $set->import( $fuzzy_import, 'fuzzy' );
		$this->assertEquals( $translations_added, 0 );
	}

	function test_import_should_generate_warnings() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string with %s' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string with %s', 'translations' => array( 'No Placeholder' ) ) );
		$set->import( $translations_for_import );

		$translations = GP::$translation->all();
		$this->assertArrayHasKey( 'placeholders', $translations[0]->warnings[0] );
	}

	function test_import_should_not_import_translations_with_warnings_twice() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string with %s' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string with %s', 'translations' => array( 'No Placeholder' ) ) );
		$translations_added = $set->import( $translations_for_import );
		$this->assertEquals( $translations_added, 1 );

		$translations = GP::$translation->all();
		$this->assertArrayHasKey( 'placeholders', $translations[0]->warnings[0] );

		// Do the import again, it should not go through.
		$translations_added = $set->import( $translations_for_import );
		$this->assertEquals( $translations_added, 0 );
	}

	/**
	 * @ticket gh-646
	 */
	function test_import_should_not_change_fuzzy_status_if_warnings() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string with %s' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string with %s', 'translations' => array( 'No Placeholder' ) ) );
		$set->import( $translations_for_import, 'fuzzy' );

		$translations = GP::$translation->all();
		$this->assertArrayHasKey( 'placeholders', $translations[0]->warnings[0] );
		$this->assertSame( 'fuzzy', $translations[0]->status );
	}

	function test_import_should_not_import_existing_same_translation() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba' ), 'status' => 'current' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'baba' ) ) );
		$translations_added = $set->import( $translations_for_import );

		$this->assertEquals( $translations_added, 0 );

	}

	function test_import_should_import_over_existing_different_translation_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba' ), 'status' => 'current' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'abab' ) ) );
		$translations_added = $set->import( $translations_for_import );

		$this->assertEquals( $translations_added, 1 );

	}

	function test_filter_translation_set_import_warnings() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string with %s' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string with %s', 'translations' => array( 'No Placeholder' ) ) );

		add_filter( 'gp_translation_set_import_warnings', '__return_empty_array' );
		$set->import( $translations_for_import );
		remove_filter( 'gp_translation_set_import_warnings', '__return_empty_array' );

		$translations = GP::$translation->all();
		$this->assertEquals( 'current', $translations[0]->status );
		$this->assertNull( $translations[0]->warnings );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string with %s' ) );

		$custom_warnings = array(
			array(
				'warning'   => 'custom_warning',
				'position'  => 0,
				'reference' => 'singular',
			),
		);

		$custom_callback = function () use ( $custom_warnings ) {
			return $custom_warnings;
		};

		add_filter( 'gp_translation_set_import_warnings', $custom_callback );
		$set->import( $translations_for_import );
		remove_filter( 'gp_translation_set_import_warnings', $custom_callback );

		$translations = GP::$translation->all();
		$this->assertSame( $custom_warnings, $translations[1]->warnings );
		$this->assertEquals( 'waiting', $translations[1]->status );
	}

	function test_filter_translation_set_import_over_existing() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba' ), 'status' => 'current' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'abab' ) ) );

		add_filter( 'gp_translation_set_import_over_existing', '__return_false' );
		$translations_added = $set->import( $translations_for_import );
		remove_filter( 'gp_translation_set_import_over_existing', '__return_false' );

		$this->assertEquals( $translations_added, 0 );
	}

	function test_user_can_reject_his_own_translation() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba' ), 'status' => 'current' ) );

		$this->assertFalse( $translation->set_status( 'current' ) );
		$this->assertTrue( $translation->set_status( 'rejected' ) );
		$this->assertTrue( $translation->set_status( 'waiting' ) );
		$this->assertFalse( $translation->set_status( 'current' ) );

		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba2' ), 'status' => 'rejected' ) );

		$this->assertFalse( $translation->set_status( 'current' ) );
		$this->assertTrue( $translation->set_status( 'rejected' ) );
		$this->assertFalse( $translation->set_status( 'current' ) );

		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translations' => array( 'baba3' ), 'status' => 'waiting' ) );

		$this->assertFalse( $translation->set_status( 'current' ) );
		$this->assertTrue( $translation->set_status( 'rejected' ) );
		$this->assertFalse( $translation->set_status( 'current' ) );
	}

	/**
	 * @ticket 512
	 */
	function test_filter_translation_set_import_fuzzy_translations() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$translations_for_import = new Translations;

		// Create 3 originals and 3 fuzzy translations
		for ( $i = 0; $i < 3; $i++ ) {
			$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => "A string #$i" ) );
			$translations_for_import->add_entry( array(
				'singular' => "A string #$i",
				'translations' => array( "A translated string #$i" ),
				'flags' => array( 'fuzzy' )
			) );
		}

		// Create 3 originals and 3 non-fuzzy translations
		for ( $i = 0; $i < 3; $i++ ) {
			$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => "A second string #$i" ) );
			$translations_for_import->add_entry( array(
				'singular' => "A second string #$i",
				'translations' => array( "A second translated string #$i" ),
			) );
		}

		// Import 6 translations
		add_filter( 'gp_translation_set_import_fuzzy_translations', '__return_false' );
		$translations_added = $set->import( $translations_for_import );
		remove_filter( 'gp_translation_set_import_fuzzy_translations', '__return_false' );

		// Expect only 3 imported translations, fuzzy translations are ignored.
		$this->assertEquals( $translations_added, 3 );
	}

	function test_delete() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$pre_delete = GP::$translation_set->find_one( array( 'id' => $set->id ) );

		$set->delete();

		$post_delete = GP::$translation_set->find_one( array( 'id' => $set->id ) );

		$this->assertFalse( empty( $pre_delete ) );
		$this->assertNotEquals( $pre_delete, $post_delete );
	}

	/**
	 * @ticket gh-1845
	 */
	function test_delete_removes_its_glossary_and_keeps_other_glossaries() {
		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other_set = $this->factory->translation_set->create_with_project_and_locale();

		$glossary       = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );
		$other_glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $other_set->id ) );

		GP::$glossary_entry->create( array( 'glossary_id' => $glossary->id, 'term' => 'term', 'part_of_speech' => 'noun', 'last_edited_by' => 1 ) );
		$this->factory->translation->create_with_original_for_translation_set( $set );

		$set->delete();

		$this->assertFalse( GP::$glossary->by_set_id( $set->id ) );
		$this->assertEmpty( GP::$glossary_entry->find_many( array( 'glossary_id' => $glossary->id ) ) );
		$this->assertEmpty( GP::$translation->find_many( array( 'translation_set_id' => $set->id ) ) );
		$this->assertEquals( $other_glossary->id, GP::$glossary->by_set_id( $other_set->id )->id );
	}

	/**
	 * Validator permissions are inherited by sub-projects and can exist without a set.
	 *
	 * @ticket gh-2153
	 */
	function test_delete_keeps_validator_permissions() {
		$user = $this->factory->user->create();
		$set  = $this->factory->translation_set->create_with_project_and_locale();

		GP::$validator_permission->create( array( 'user_id' => $user, 'action' => 'approve', 'project_id' => $set->project_id, 'locale_slug' => $set->locale, 'set_slug' => $set->slug ) );

		$set->delete();

		$this->assertCount( 1, GP::$validator_permission->by_project_id( $set->project_id ) );
	}

	/**
	 * @ticket gh-2153
	 */
	function test_delete_removes_translation_set_permissions() {
		$user      = $this->factory->user->create();
		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other_set = $this->factory->translation_set->create_with_project_and_locale();

		GP::$permission->create( array( 'user_id' => $user, 'action' => 'approve', 'object_type' => 'translation-set', 'object_id' => $set->id ) );
		GP::$permission->create( array( 'user_id' => $user, 'action' => 'approve', 'object_type' => 'translation-set', 'object_id' => $other_set->id ) );

		$set->delete();

		$this->assertEmpty( GP::$permission->find_many( array( 'object_type' => 'translation-set', 'object_id' => $set->id ) ) );
		$this->assertCount( 1, GP::$permission->find_many( array( 'object_type' => 'translation-set', 'object_id' => $other_set->id ) ) );
	}

	/**
	 * @ticket gh-397
	 */
	function test_update_status_breakdown_updates_pre_2_1_cached_values() {
		global $wpdb;
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$counts = array();
		$statuses = GP::$translation->get_static( 'statuses' );
		foreach ( $statuses as $status ) {
			$counts[] = (object) array( 'translation_status' => $status, 'n' => 10 );
		}
		$counts[] = (object) array( 'translation_status' => 'warnings', 'n' => 1 );
		wp_cache_set( $set->id, $counts, 'translation_set_status_breakdown' );

		$num_queries = $wpdb->num_queries;
		$set->update_status_breakdown();
		$this->assertEquals( $num_queries + 6, $wpdb->num_queries );
	}

	public function test_created_action_is_called() {
		$action = new MockAction();

		add_action( 'gp_translation_set_created', array( $action, 'action' ) );

		$this->factory->translation_set->create_with_project_and_locale();

		remove_action( 'gp_translation_set_created', array( $action, 'action' ) );

		$this->assertSame( 1, $action->get_call_count() );
	}

	public function test_saved_action_is_called() {
		$action = new MockAction();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$locale = $this->factory->locale->create();

		add_action( 'gp_translation_set_saved', array( $action, 'action' ) );

		$set->save( array(
			'locale' => $locale->slug,
		) );

		remove_action( 'gp_translation_set_saved', array( $action, 'action' ) );

		$this->assertSame( 1, $action->get_call_count() );
	}

	public function test_deleted_action_is_called() {
		$action = new MockAction();

		$set = $this->factory->translation_set->create_with_project_and_locale();

		add_action( 'gp_translation_set_deleted', array( $action, 'action' ) );

		$set->delete();

		remove_action( 'gp_translation_set_deleted', array( $action, 'action' ) );

		$this->assertSame( 1, $action->get_call_count() );
	}

	public function test_previous_state_is_passed_to_saved_action() {
		$translation_set = $this->factory->translation_set->create_with_project_and_locale( array( 'name' => 'Before' ) );
		$initial_set = clone $translation_set;

		$previous_set = null;
		$closure = function( $set_after, $set_before ) use ( &$previous_set ) {
			$previous_set = $set_before;
		};

		add_action( 'gp_translation_set_saved', $closure, 10, 2 );

		$translation_set->save( array( 'name' => 'After' ) );

		remove_action( 'gp_translation_set_saved', $closure );

		$this->assertEquals( $initial_set, $previous_set );
		$this->assertEquals( $previous_set->name, 'Before' );
		$this->assertEquals( $translation_set->name, 'After' );
	}

	function test_name_with_locale_escapes_the_set_name() {
		$name = '<b>abc123</b>';
		$set  = $this->factory->translation_set->create_with_project_and_locale( array( 'name' => $name, 'slug' => 'markup' ) );

		$html = $set->name_with_locale();

		$this->assertStringNotContainsString( $name, $html );
		$this->assertStringContainsString( esc_html( $name ), $html );
	}

	function test_locale_is_reduced_to_slug_characters() {
		$project = $this->factory->project->create();

		$set = new GP_Translation_Set(
			array(
				'name'       => 'Set',
				'slug'       => 'default',
				'project_id' => $project->id,
				'locale'     => '<b>de</b>',
			)
		);

		$this->assertSame( 'de', $set->locale, 'A locale is reduced to slug characters.' );
		$this->assertTrue( $set->validate() );
	}

	function test_locale_that_reduces_to_nothing_is_invalid() {
		$project = $this->factory->project->create();

		$set = new GP_Translation_Set(
			array(
				'name'       => 'Set',
				'slug'       => 'default',
				'project_id' => $project->id,
				'locale'     => '<script>`',
			)
		);

		$this->assertSame( '', $set->locale );
		$this->assertFalse( $set->validate(), 'A locale left empty by the reduction does not validate.' );
	}

	function test_locale_keeps_the_characters_real_locale_slugs_use() {
		$project = $this->factory->project->create();

		foreach ( array( 'de', 'pt-br', 'zh-hk', 'art-xemoji' ) as $slug ) {
			$set = new GP_Translation_Set(
				array(
					'name'       => 'Set',
					'slug'       => 'default',
					'project_id' => $project->id,
					'locale'     => $slug,
				)
			);

			$this->assertSame( $slug, $set->locale, 'A real locale slug is kept unchanged.' );
		}
	}

	function test_non_db_field_names_are_declared_properties() {
		$set = new GP_Translation_Set();

		foreach ( $set->non_db_field_names as $field ) {
			$this->assertTrue(
				property_exists( $set, $field ),
				"Non-DB field '$field' must be a declared property so assigning it does not create a dynamic property."
			);
		}
	}

	/**
	 * Creates a translation with a given status and date_modified.
	 *
	 * @param GP_Translation_Set $set           The translation set.
	 * @param int                $original_id   The original ID.
	 * @param string             $status        The status.
	 * @param string             $date_modified The date_modified.
	 * @return GP_Translation The translation.
	 */
	private function create_translation_modified_at( $set, $original_id, $status, $date_modified ) {
		global $wpdb;

		$translation = $this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original_id,
				'status'             => $status,
			)
		);
		$wpdb->update( $wpdb->gp_translations, array( 'date_modified' => $date_modified ), array( 'id' => $translation->id ) );

		return GP::$translation->get( $translation->id );
	}

	public function test_supersede_multiple_currents_keeps_the_most_recently_modified() {
		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other_set = $this->factory->translation_set->create( array( 'project_id' => $set->project_id, 'locale' => 'de', 'slug' => 'default' ) );
		$original  = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$tied      = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$single    = $this->factory->original->create( array( 'project_id' => $set->project_id ) );

		$older  = $this->create_translation_modified_at( $set, $original->id, 'current', '2020-01-01 00:00:00' );
		$newest = $this->create_translation_modified_at( $set, $original->id, 'current', '2022-01-01 00:00:00' );
		$newer  = $this->create_translation_modified_at( $set, $original->id, 'current', '2021-01-01 00:00:00' );
		$tied_1 = $this->create_translation_modified_at( $set, $tied->id, 'current', '2020-01-01 00:00:00' );
		$tied_2 = $this->create_translation_modified_at( $set, $tied->id, 'current', '2020-01-01 00:00:00' );
		$alone  = $this->create_translation_modified_at( $set, $single->id, 'current', '2020-01-01 00:00:00' );
		$other  = array(
			$this->create_translation_modified_at( $other_set, $original->id, 'current', '2020-01-01 00:00:00' ),
			$this->create_translation_modified_at( $other_set, $original->id, 'current', '2021-01-01 00:00:00' ),
		);

		$this->assertSame(
			array(
				(int) $original->id => array(
					'kept'       => (int) $newest->id,
					'superseded' => array( (int) $newer->id, (int) $older->id ),
				),
				(int) $tied->id     => array(
					'kept'       => (int) $tied_2->id,
					'superseded' => array( (int) $tied_1->id ),
				),
			),
			$set->supersede_multiple_currents()
		);

		$this->assertSame( 'current', GP::$translation->get( $newest->id )->status );
		$this->assertSame( 'old', GP::$translation->get( $newer->id )->status );
		$this->assertSame( 'old', GP::$translation->get( $older->id )->status );
		$this->assertSame( 'current', GP::$translation->get( $tied_2->id )->status );
		$this->assertSame( 'old', GP::$translation->get( $tied_1->id )->status );
		$this->assertSame( 'current', GP::$translation->get( $alone->id )->status );

		// Other translation sets are left alone.
		$this->assertSame( 'current', GP::$translation->get( $other[0]->id )->status );
		$this->assertSame( 'current', GP::$translation->get( $other[1]->id )->status );

		$this->assertSame( array(), $set->supersede_multiple_currents() );
	}

	public function test_supersede_multiple_currents_dry_run_changes_nothing() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$older    = $this->create_translation_modified_at( $set, $original->id, 'current', '2020-01-01 00:00:00' );
		$newer    = $this->create_translation_modified_at( $set, $original->id, 'current', '2021-01-01 00:00:00' );

		$expected = array(
			(int) $original->id => array(
				'kept'       => (int) $newer->id,
				'superseded' => array( (int) $older->id ),
			),
		);
		$this->assertSame( $expected, $set->supersede_multiple_currents( true ) );
		$this->assertSame( 'current', GP::$translation->get( $older->id )->status );
		$this->assertSame( $expected, $set->supersede_multiple_currents( true ) );
	}

	public function test_supersede_multiple_currents_fixes_the_counts() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$this->create_translation_modified_at( $set, $original->id, 'current', '2020-01-01 00:00:00' );
		$this->create_translation_modified_at( $set, $original->id, 'current', '2021-01-01 00:00:00' );

		$this->assertSame( 2, $set->current_count() );

		$set->supersede_multiple_currents();

		$set = GP::$translation_set->get( $set->id );
		$this->assertSame( 1, $set->current_count() );
	}
}
