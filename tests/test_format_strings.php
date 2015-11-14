<?php

class GP_Test_Format_Strings extends GP_UnitTestCase {

    function setUp() {
		parent::setUp();
		$this->strings = new GP_Format_Strings;
		$this->entries = array(
			array('Normal String', 'Normal String', 'Just A Normal String', ''),
			array('I\'m with a quote', 'I\'m with a quote', 'I\'m with a quote', ''),
			array('double "quotes"', 'double "quotes"', 'I have double "quotes"', ''),
			array('питка', 'питка', 'баба ми омеси питка', ''),
			array('you < me', 'you < me', 'ти < аз', ''),
			array('me > you', 'me > you', "аз > ти", ''),
			array('me & you are not &amp;', 'me & you are not &amp;', 'аз & ти не сме &amp;', ''),
			array('baba', 'baba', 'баба', 'Me, myself & Irene'),
		);
	}

	function test_export() {
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

		$file     = file_get_contents( GP_DIR_TESTDATA . '/translation.strings' );
		$exported = $this->strings->print_exported_file( 'p', 'l', 't', $entries_for_export );

		$file     = mb_convert_encoding( $file, 'UTF-8', 'UTF-16LE' );
		$exported = mb_convert_encoding( $exported, 'UTF-8', 'UTF-16' );

		$this->assertEquals( $file, $exported );
	}

	function test_read_originals() {
		$translations = $this->strings->read_originals_from_file( GP_DIR_TESTDATA . '/originals.utf16le.strings' );
		$this->assertEquals( count( $this->entries ), count( $translations->entries ), 'number of read originals is different from the expected' );

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

		GP::$original = $this->getMock( 'GP_Original', array('by_project_id') );
		GP::$original->expects( $this->once() )
					->method( 'by_project_id' )
					->with( $this->equalTo(2) )
					->will( $this->returnValue($stubbed_originals) );

		$translations = $this->strings->read_translations_from_file( GP_DIR_TESTDATA . '/translation.strings', (object)array( 'id' => 2 ) );

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}

}
