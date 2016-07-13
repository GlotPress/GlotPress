<?php

class GP_Test_Format_PO extends GP_UnitTestCase {
	/**
	 * @var GP_Format_PO
	 */
	protected $format;

	/**
	 * @var array
	 */
	protected $entries;

	/**
	 * @var string
	 */
	protected $translation_file;

	/**
	 * @var string
	 */
	protected $originals_file;

	/**
	 * @var bool
	 */
	protected $has_comments = true;

	public function setUp() {
		parent::setUp();

		$this->translation_file = GP_DIR_TESTDATA . '/translation.po';
		$this->originals_file = GP_DIR_TESTDATA . '/originals.po';

		$this->format = new GP_Format_PO;

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

	private function get_entries_for_export( $translation_set ) {
		$entries_for_export = array();

		foreach ( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;

			$translation_entry = new GP_Translation;
			$translation_entry->context = $context;
			$translation_entry->singular = $original;
			$translation_entry->translations = array( $translation );
			$translation_entry->status = 'current';
			$translation_entry->translation_set_id = $translation_set->id;
			if ( true === $this->has_comments ) {
				$translation_entry->extracted_comments = $comment;
			}

			$entries_for_export[] = new Translation_Entry( (array) $translation_entry );
		}

		return $entries_for_export;
	}

	public function test_export() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$project = $set->project;
		$locale = $this->factory->locale->create();

		$entries_for_export = $this->get_entries_for_export( $set );

		$this->assertEquals( file_get_contents( $this->translation_file ), $this->format->print_exported_file( $project, $locale, $set, $entries_for_export ) );
	}

	/**
	 * @ticket GH-450
	 */
	public function test_po_export_includes_project_id_version_header() {
		if ( 'GP_Format_PO' !== get_class( $this->format ) ) {
			$this->markTestSkipped();
		}

		$parent_project_one = $this->factory->project->create();
		$parent_project_two = $this->factory->project->create( array( 'parent_project_id' => $parent_project_one->id ) );
		$set = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'parent_project_id' => $parent_project_two->id ) );
		$project = $set->project;
		$locale = $this->factory->locale->create();

		$entries_for_export = $this->get_entries_for_export( $set );

		$file = $this->format->print_exported_file( $project, $locale, $set, $entries_for_export );
		$expected = sprintf(
			'"Project-Id-Version: %s - %s - %s\n"',
			$parent_project_one->name,
			$parent_project_two->name,
			$project->name
		);

		$this->assertContains( $expected, $file );
	}

	public function test_read_originals() {
		$translations = $this->format->read_originals_from_file( $this->originals_file );

		foreach ( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;

			$translatable_entry = new Translation_Entry( array( 'singular' => $original, 'context' => $context ) );
			$entry = $translations->translate_entry( $translatable_entry );

			$this->assertEquals( $original, $entry->singular );
			$this->assertEquals( $context, $entry->context );
			if ( true === $this->has_comments ) {
				$this->assertEquals( $comment, $entry->extracted_comments );
			}
		}
	}

	public function test_read_translations() {
		$translations = $this->format->read_translations_from_file( $this->translation_file, (object)array( 'id' => 1 ) );

		foreach ( $this->entries as $sample ) {
			list( $context, $original, $translation, $comment ) = $sample;

			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}

	public function test_get_language_code() {
		$test_class = new Testable_GP_Format_PO_get_language_code;

		// Create a locale that has only a 639_1 language code.
		$as = new GP_Locale();
		$as->english_name = 'Assamese';
		$as->lang_code_iso_639_1 = 'as';

		$this->assertEquals( 'as', $test_class->testable_get_language_code( $as ) );

		// Add the 639_2 language code.
		$as->lang_code_iso_639_2 = 'asn';
		$this->assertEquals( 'as', $test_class->testable_get_language_code( $as ) );

		// Add the 639_3 language code.
		$as->lang_code_iso_639_3 = 'asm';
		$this->assertEquals( 'as', $test_class->testable_get_language_code( $as ) );

		// Add the country language code, which is the same as the language code.
		$as->country_code = 'as';
		$this->assertEquals( 'as', $test_class->testable_get_language_code( $as ) );

		// Change the country code to be different than the language code.
		$as->country_code = 'in';
		$this->assertEquals( 'as_IN', $test_class->testable_get_language_code( $as ) );

		// Remove the country code for the next tests.
		$as->country_code = null;

		// Remove the 639_1 language code.
		$as->lang_code_iso_639_1 = '';
		$this->assertEquals( 'asn', $test_class->testable_get_language_code( $as ) );

		// Remove the 639_2 language code.
		$as->lang_code_iso_639_2 = '';
		$this->assertEquals( 'asm', $test_class->testable_get_language_code( $as ) );

		// Setup the locale to have the incorrect case in the locale information for country and language.
		$as->lang_code_iso_639_1 = 'AS';
		$as->country_code = 'IN';
		$this->assertEquals( 'as_IN', $test_class->testable_get_language_code( $as ) );
	}
}

class GP_Test_Format_MO extends GP_Test_Format_PO {

	/**
	 * @var GP_Format_MO
	 */
	protected $format;

   public function setUp() {
		parent::setUp();

		$this->translation_file = GP_DIR_TESTDATA . '/translation.mo';
		$this->originals_file = GP_DIR_TESTDATA . '/originals.mo';
		$this->has_comments = false;

		$this->format = new GP_Format_MO;
	}
}

/**
 * Class that makes it possible to test protected functions.
 */
class Testable_GP_Format_PO_get_language_code extends GP_Format_PO {
	/**
	 * Wraps the protected get_language_code function
	 *
	 * @param GP_Locale $locale The locale object.
	 *
	 * @return string|false Returns false if the locale object does not have any iso_639 language code, otherwise returns the shortest possible language code string.
	 */
	public function testable_get_language_code( $locale ) {
		return $this->get_language_code( $locale );
	}
}
