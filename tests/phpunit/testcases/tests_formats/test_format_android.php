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
			array( 'with_escaped_unicode', 'No posts saved \u2014 yet!', 'Keine Beiträge gespeichert \u2014 noch!', '' ),
		);
		$this->plural_entries = array(
			array( 'with_plurals', 'Updated %s value', 'Updated %s value', 'Updated %s values', '' ),
		);
	}

	function test_export() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$project = $set->project;
		$locale = $this->factory->locale->create();
		$entries_for_export = array();

		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;
			$entries_for_export[] = (object) array(
				'context' => $context,
				'singular' => $original,
				'translations' => array($translation),
				'is_plural' => false,
			);
		}

		foreach ( $this->plural_entries as $sample ) {
			list( $context, $original, $translation, $plural_translation, $comment ) = $sample;
			$entries_for_export[] = (object) array(
				'context' => $context,
				'singular' => $original,
				'translations' => array( $translation, $plural_translation ),
				'is_plural' => true,
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

		$data = array(
			array(
					'context'  => 'with_xliff',
					'original' => 'Please don\'t translate this text',
					'comment'  => 'This string has content that should not be translated, the "this" component of the original, which is identified as the "excluded" attribute by the developer and is not intended to be translated.',
			),
			array(
					'context'  => 'with_two_xliff',
					'original' => 'Please don\'t translate this text or even this other text',
					'comment'  => 'This string has content that should not be translated, the "this" component of the original, which is identified as the "first" attribute by the developer and is not intended to be translated. This string has content that should not be translated, the "other" component of the original, which is identified as the "second" attribute by the developer and is not intended to be translated.',
			),
			array(
					'context'  => 'with_xliff_with_example',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component of the original, which is identified as the "excluded" attribute by the developer may be replaced at run time with text like this: this',
			),
			array(
					'context'  => 'with_xliff_with_example_and_no_id',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component of the original may be replaced at run time with text like this: this',
			),
			array(
					'context'  => 'with_xliff_with_no_example_and_no_id',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component is not intended to be translated.',
			),
			array(
					'context'  => 'with_xliff_with_single_quote_attributes',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component of the original, which is identified as the "excluded" attribute by the developer may be replaced at run time with text like this: this',
			),
			array(
					'context'  => 'with_xliff_with_single_and_double_quote_attributes',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component of the original, which is identified as the "excluded" attribute by the developer may be replaced at run time with text like this: this',
			),
			array(
					'context'  => 'with_xliff_with_quotes_inside_attributes',
					'original' => 'Please don\'t translate %s text',
					'comment'  => 'This string has content that should not be translated, the "%s" component of the original, which is identified as the "exclud\'d" attribute by the developer may be replaced at run time with text like this: "this"',
			),
		);

		foreach( $data as $set ) {
			$translatable_entry = new Translation_Entry( array( 'singular' => $set['original'], 'context' => $set['context'] ) );
			$entry = $translations->translate_entry( $translatable_entry );
			$this->assertEquals( $set['original'], $entry->singular );
			$this->assertEquals( $set['context'], $entry->context );
			$this->assertEquals( $set['comment'], $entry->extracted_comments );
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
