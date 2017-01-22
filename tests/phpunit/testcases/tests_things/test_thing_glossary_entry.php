<?php

class GP_Test_Glossary_Entry extends GP_UnitTestCase {

	function test_empty_glossary_id() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_term() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => '', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_part_of_speech() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => '', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_negative_last_edited_by() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'tern', 'part_of_speech' => 'verb', 'last_edited_by' =>'-1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_last_edited_by() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'tern', 'part_of_speech' => 'verb', 'last_edited_by' =>'0' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_by_glossary_id() {
		$glossary_entry_1 = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$glossary_entry_2 = GP::$glossary_entry->create( array( 'glossary_id' => '2', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$new = GP::$glossary_entry->by_glossary_id( '1' );
		$this->assertEquals( array( $glossary_entry_1 ), $new );
		$this->assertNotEquals( array( $glossary_entry_2 ), $new );
	}

	function test_part_of_speech_array_set() {
		$this->assertCount( 9, GP::$glossary_entry->parts_of_speech );
		$this->assertArrayHasKey( 'noun', GP::$glossary_entry->parts_of_speech );
	}

	function test_delete() {
		$entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );

		$pre_delete = GP::$glossary_entry->find_one( array( 'id' => $entry->id ) );

		$entry->delete();

		$post_delete = GP::$glossary_entry->find_one( array( 'id' => $entry->id ) );

		$this->assertFalse( empty( $pre_delete ) );
		$this->assertNotEquals( $pre_delete, $post_delete );
	}

	function test_mapping_entries_to_originals() {
		require_once GP_TMPL_PATH . 'helper-functions.php';

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$nouns = array( 'term', 'box', 'city', 'toy', 'wife', 'shelf', 'man', 'woman', 'post', 'install/installation' );
		foreach ( $nouns as $noun ) {
			GP::$glossary_entry->create( array( 'glossary_id' => $glossary->id, 'term' => $noun, 'part_of_speech' => 'noun', 'translation' => $noun, 'comment' => 'my comment', 'last_edited_by' =>'1' ) );
		}
		$verbs = array( 'write', 'post' );
		foreach ( $verbs as $verb ) {
			GP::$glossary_entry->create( array( 'glossary_id' => $glossary->id, 'term' => $verb, 'part_of_speech' => 'verb', 'translation' => $verb, 'comment' => 'my comment', 'last_edited_by' =>'1' ) );
		}

		$originals = array(
			'term' => array( 'term' ),
			'terms' => array( 'term' ),
			'A sentence with a term to be found.' => array( 'term' ),
			'A sentence with some terms to be found.' => array( 'term' ),
			'A sentence with just a box.' => array( 'box' ),
			'A sentence that contains a few boxes.' => array( 'box' ),
			'A sentence about a city with some boxes.' => array( 'city', 'box' ),
			'A blog about a city.' => array( 'city' ),
			'Two blogs about two cities.' => array( 'city' ),
			'A blog about a toy.' => array( 'toy' ),
			'Two blogs about two toys.' => array( 'toy' ),
			'A blog about a shelf.' => array( 'shelf' ),
			'Two blogs about two shelves.' => array( 'shelf' ),
			'A blog about a wife.' => array( 'wife' ),
			'Two blogs about two wives.' => array( 'wife' ),
			'A blog about a man and a woman.' => array( 'man', 'woman' ),
			'Two blogs about two men and two women.' => array( 'man', 'woman' ),
			'I write about something.' => array( 'write' ),
			'Someone writes about something.' => array( 'write' ),
			'I post about something.' => array( 'post' ),
			'Someone posts something.' => array( 'post' ),
		);

		foreach ( $originals as $original => $terms ) {
			$this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => $original, 'plural' => $original, ) );
		}

		$translations = GP::$translation->for_translation( $set->project, $set, 'no-limit' );
		$translations = map_glossary_entries_to_translations_originals( $translations, $glossary );

		foreach ( $translations as $translation ) {
			foreach ( $originals[ $translation->singular ] as $term ) {
				foreach ( array( 'noun', 'verb' ) as $pos ) {
					if ( ! in_array( $term, ${ $pos . 's' } ) ) {
						continue;
					}

					// echo 'Checking for ', $pos, ' ', $term, ' in ', $translation->singular, PHP_EOL;
					$translation_json = array(
						'translation' => $term,
						'pos' => $pos,
					);

					$regex = '#<span class="glossary-word" data-translations="\[.*?' . preg_quote( htmlspecialchars( substr( json_encode( $translation_json ), 0, -2 ) ), '#' ) . '[^"]+">[^<]+</span>#';

					$this->assertRegExp( $regex, $translation->singular_glossary_markup, 'Glossary term "' . $term . '" should have been found in "' . $translation->singular . '".' );
					$this->assertRegExp( $regex, $translation->plural_glossary_markup, 'Glossary term "' . $term . '" should have been found in "' . $translation->plural . '".' );
				}
			}
		}
	}
}
