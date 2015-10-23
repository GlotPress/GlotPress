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
		wp_set_current_user( $user->id );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string','translations' => array( 'baba' ) ) );
		$set->import( $translations_for_import );

		$translations = GP::$translation->all();
		$this->assertEquals( $translations[0]->user_id, $user->id );
	}

	function test_import_should_save_fuzzy() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A string' ) );
		$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Second string' ) );

		$translations_for_import = new Translations;
		$translations_for_import->add_entry( array( 'singular' => 'A string', 'translations' => array( 'baba' ), 'flags' => array('fuzzy' ) ) );
		$translations_for_import->add_entry( array( 'singular' => 'Second string', 'translations' => array( 'second' ) ) );
		$set->import( $translations_for_import );

		$translations = GP::$translation->all();

		$this->assertEquals( $translations[0]->status, 'fuzzy' );
		$this->assertEquals( $translations[1]->status, 'current' );
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

}
