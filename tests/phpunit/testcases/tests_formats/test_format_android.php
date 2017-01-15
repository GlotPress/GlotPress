<?php

class GP_Test_Format_Android extends GP_UnitTestCase {
    function setUp() {
		parent::setUp();
		$this->android = new GP_Format_Android;
		$this->entries = array(
			array( 'normal_string', 'Normal String', 'Just A Normal String', '' ),
			array( 'with_a_quote', 'I\'m with a quote', 'I\'m with a quote', '' ),
			array( 'with_newlines', "new\nlines", "I\nhave\nnew\nlines", '' ),
			array( 'with_doublequotes', 'double "quotes"', 'I have double "quotes"', '' ),
			array( 'with_utf8', 'питка', 'баба ми омеси питка', '' ),
			array( 'with_lt', 'you < me', 'ти < аз', '' ),
			array( 'with_gt', 'me > you', "аз > ти", '' ),
			array( 'with_amps', 'me & you are not &amp;', 'аз & ти не сме &amp;', '' ),
			array( 'with_comment', 'baba', 'баба', 'Me, myself & Irene' ),
		);
	}

	function test_export() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$project = $set->project;
		$locale = $this->factory->locale->create();
		$entries_for_export = array();

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$entries_for_export[] = (object)array(
				'context' => $context,
				'singular' => $original,
				'translations' => array($translation),
			);
		}
		
		$file_contents = file_get_contents( GP_DIR_TESTDATA . '/translation.android.xml' );
		$file_contents = str_replace( '[GP VERSION]', GP_VERSION, $file_contents );
		
		$this->assertEquals( $file_contents, $this->android->print_exported_file( $project, $locale, $set, $entries_for_export ) );
	}


	function test_read_originals() {
		$translations = $this->android->read_originals_from_file( GP_DIR_TESTDATA . '/originals.android.xml' );

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$translatable_entry = new Translation_Entry( array( 'singular' => $original, 'context' => $context) );
			$entry = $translations->translate_entry( $translatable_entry );
			$this->assertEquals( $original, $entry->singular );
			$this->assertEquals( $context, $entry->context );
			$this->assertEquals( $comment, $entry->extracted_comments );
		}
	}

	function test_read_original_with_xliff() {
		$translations = $this->android->read_originals_from_file( GP_DIR_TESTDATA . '/originals.android-with-xliff.xml' );
		
		$context = 'with_xliff';
		$original = 'Please don\'t translate <xliff:g id="excluded" example="this">this</xliff:g> text';
		
		$translatable_entry = new Translation_Entry( array( 'singular' => $original, 'context' => $context ) );
		$entry = $translations->translate_entry( $translatable_entry );
		$this->assertEquals( $original, $entry->singular );
		$this->assertEquals( $context, $entry->context );
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
		$translations = $this->android->read_translations_from_file( GP_DIR_TESTDATA . '/translation.android.xml', (object)array( 'id' => 2 ) );
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}

	function test_escape() {
		$test_class = new Testable_GP_Format_Strings_escape;

		$this->assertEquals( "test \'string\'", $test_class->testable_escape( "test 'string'" ) );
		$this->assertEquals( "test\\nstring", $test_class->testable_escape( "test\nstring" ) );
		$this->assertEquals( '\@test string', $test_class->testable_escape( '@test string' ) );
		$this->assertEquals( 'test @string', $test_class->testable_escape( 'test @string' ) );
	}

}

/**
 * Class that makes it possible to test protected functions.
 */
class Testable_GP_Format_Strings_escape extends GP_Format_Android {
	/**
	 * Wraps the protected escape function
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string Returns escaped string.
	 */
	public function testable_escape( $string ) {
		return $this->escape( $string );
	}
}
