<?php

class GP_Test_Format_ResX extends GP_UnitTestCase {
    function setUp(): void {
		parent::setUp();
		$this->resx = new GP_Format_ResX;
		$this->entries = array(
			array('normal_string', 'Normal String', 'Just A Normal String', ''),
			array('with_a_quote', 'I\'m with a quote', 'I\'m with a quote', ''),
			array('with_newlines', "new\nlines", "I\nhave\nnew\nlines", ''),
			array('with_doublequotes', 'double "quotes"', 'I have double "quotes"', ''),
			array('with_utf8', 'питка', 'баба ми омеси питка', ''),
			array('with_lt', 'you < me', 'ти < аз', ''),
			array('with_gt', 'me > you', "аз > ти", ''),
			array('with_amps', 'me & you are not &amp;', 'аз & ти не сме &amp;', ''),
			array('with_comment', 'baba', 'баба', 'Me, myself & Irene'),
		);
	}

	function test_export() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$project = $set->project;
		$locale = $this->factory->locale->create();
		$entries_for_export = array();

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$entries_for_export[] = (object)array(
				'context' => $context,
				'singular' => $original,
				'translations' => array($translation),
				'extracted_comments' => $comment,
			);
		}

		$file_contents = file_get_contents( GP_DIR_TESTDATA . '/translation.resx.xml' );
		$file_contents = str_replace( '[GP VERSION]', GP_VERSION, $file_contents );

		$this->assertEquals( $file_contents, $this->resx->print_exported_file( $project, $locale, $set, $entries_for_export ) );
	}


	function test_read_originals() {
		$translations = $this->resx->read_originals_from_file( GP_DIR_TESTDATA . '/originals.resx.xml' );
		$this->assertEquals( count($this->entries ), count( $translations->entries ), 'number of read originals is different from the expected' );
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$translatable_entry = new Translation_Entry( array('singular' => $original, 'context' => $context, 'extracted_comments' => $comment ) );
			$entry = $translations->translate_entry( $translatable_entry );
			$this->assertEquals( $original, $entry->singular );
			$this->assertEquals( $context, $entry->context );
			$this->assertEquals( $comment, $entry->extracted_comments );
		}
	}

	function test_read_translations() {
		$stubbed_originals = array();
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$stubbed_originals[] = new GP_Original( array( 'singular' => $original, 'context' => $context ) );
		}
		GP::$original = $this->getMockBuilder( 'GP_Original' )->setMethods( array('by_project_id') )->getMock();
		GP::$original->expects( $this->once() )
					->method( 'by_project_id' )
					->with( $this->equalTo(2) )
					->will( $this->returnValue($stubbed_originals) );
		$translations = $this->resx->read_translations_from_file( GP_DIR_TESTDATA . '/translation.resx.xml', (object)array( 'id' => 2 ) );
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}
}
