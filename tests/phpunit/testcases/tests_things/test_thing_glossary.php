<?php

class GP_Test_Glossary extends GP_UnitTestCase {

	function test_empty_translation_set_id() {
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => '' ) );
		$verdict = $glossary->validate();

		$this->assertFalse( $verdict );
	}

	function test_by_set_id() {
		$glossary_1 = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary_2 = GP::$glossary->create_and_select( array( 'translation_set_id' => '2' ) );
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertEquals( $glossary_1, $new );
		$this->assertNotEquals( $glossary_2, $new );
	}

	function test_by_set_or_parent_project() {
		$locale = $this->factory->locale->create( array( 'slug' => 'bg' ) );

		$root = $this->factory->project->create( array( 'name' => 'root' ) );
		$root_set = $this->factory->translation_set->create( array( 'project_id' => $root->id, 'locale' => $locale->slug ) );

		$sub = 	$this->factory->project->create( array( 'name' => 'sub', 'parent_project_id' => $root->id ) );
		$sub_set = $this->factory->translation_set->create( array( 'project_id' => $sub->id, 'locale' => $locale->slug ) );

		$subsub = 	$this->factory->project->create( array( 'name' => 'subsub', 'parent_project_id' => $sub->id ) );
		$subsub_set = $this->factory->translation_set->create( array( 'project_id' => $subsub->id, 'locale' => $locale->slug ) );

		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $root_set->id ) );

		$sub_glossary = GP::$glossary->by_set_or_parent_project( $sub_set, $sub );
		$this->assertEquals( $glossary, $sub_glossary );
		$this->assertEquals( $glossary->path(), $sub_glossary->path() );

		$subsub_glossary = GP::$glossary->by_set_or_parent_project( $subsub_set, $subsub );
		$this->assertEquals( $glossary, $subsub_glossary );
		$this->assertEquals( $glossary->path(), $subsub_glossary->path() );
	}

	function test_delete() {
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary->delete();
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertNotEquals( $glossary, $new );

	}

	/**
	 * @ticket gh-435
	 */
	function test_get_entries() {

		$this->assertFalse( GP::$glossary->get_entries() );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$test_entries = array(
			array(
				'term' => 'Term',
				'translation' => 'Translation',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'Term 2',
				'translation' => 'Translation 2',
				'glossary_id' => $glossary->id,
			),
		);

		GP::$glossary_entry->create_and_select( $test_entries[0] );
		$entries = $glossary->get_entries();

		$this->assertCount( 1, $entries );
		$this->assertInstanceOf( 'GP_Glossary_Entry', $entries[0] );

		$this->assertEquals( $entries[0]->term, $test_entries[0]['term'] );
		$this->assertEquals( $entries[0]->translation, $test_entries[0]['translation'] );

		GP::$glossary_entry->create_and_select( $test_entries[1] );

		// Test that caching is working.
		$this->assertCount( 1, $glossary->get_entries() );

		$new_glossary = GP::$glossary->get( $glossary->id );
		$this->assertCount( 2, $new_glossary->get_entries() );
	}

	/**
	 * @ticket gh-435
	 */
	function test_locale_glossary() {
		$locale = $this->factory->locale->create();
		$locale_set = $this->factory->translation_set->create( array( 'project_id' => 0, 'locale' => $locale->slug ) );
		$locale_glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $locale_set->id ) );

		$args = array(
			'locale' => $locale_set->locale,
			'slug' => $locale_set->slug,
		);
		$set = $this->factory->translation_set->create_with_project( $args );
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$test_entries = array(
			array(
				'term' => 'Term',
				'part_of_speech' => 'noun',
				'translation' => 'Translation',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'Term 2',
				'part_of_speech' => 'noun',
				'translation' => 'Translation 2',
				'glossary_id' => $locale_glossary->id,
			),
		);

		GP::$glossary_entry->create_and_select( $test_entries[0] );
		$this->assertCount( 1, $glossary->get_entries() );

		$route = new Testable_GP_Route_Translation;
		$extended_glossary = $route->testable_get_extended_glossary( $set, $set->project );
		$this->assertCount( 1, $extended_glossary->get_entries() );

		$locale_glossary_entry = GP::$glossary_entry->create_and_select( $test_entries[1] );
		$this->assertCount( 1, $locale_glossary->get_entries() );

		$route = new Testable_GP_Route_Translation;
		$extended_glossary = $route->testable_get_extended_glossary( $set, $set->project );
		$this->assertCount( 2, $extended_glossary->get_entries() );

		$locale_glossary_entry->term = 'Term';
		$locale_glossary_entry->save();

		$route = new Testable_GP_Route_Translation;
		$extended_glossary = $route->testable_get_extended_glossary( $set, $set->project );
		$entries = $extended_glossary->get_entries();

		// Count is now 1 as the term is overwritten by the project glossary.
		$this->assertCount( 1, $entries );
		$this->assertEquals( $test_entries[0]['translation'], $entries[0]->translation );
	}

	/**
	 * @ticket gh-435
	 */
	function test_locale_glossary_without_project_glossary() {
		$locale = $this->factory->locale->create();
		$locale_set = $this->factory->translation_set->create( array( 'project_id' => 0, 'locale' => $locale->slug ) );
		$locale_glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $locale_set->id ) );

		$set = $this->factory->translation_set->create_with_project( array(
			'locale' => $locale_set->locale,
			'slug' => $locale_set->slug,
		) );

		GP::$glossary_entry->create( array(
			'term' => 'Term 2',
			'part_of_speech' => 'noun',
			'translation' => 'Translation 2',
			'glossary_id' => $locale_glossary->id,
		) );

		$route = new Testable_GP_Route_Translation;
		$extended_glossary = $route->testable_get_extended_glossary( $set, $set->project );
		$this->assertInstanceOf( 'GP_Glossary', $extended_glossary );
		$this->assertCount( 1, $extended_glossary->get_entries() );
	}
}

/**
 * Class that makes it possible to test protected functions.
 */
class Testable_GP_Route_Translation extends GP_Route_Translation {
	/**
	 * Wraps the protected get_extended_glossary function
	 *
	 * @param  GP_Translation_Set $translation_set translation_set for which to retrieve the glossary.
	 * @param  GP_Project         $project         project for finding potential parent projects.
	 * @return GP_Glossary                         extended glossary
	 */
	public function testable_get_extended_glossary( $translation_set, $project ) {
		return $this->get_extended_glossary( $translation_set, $project );
	}
}
