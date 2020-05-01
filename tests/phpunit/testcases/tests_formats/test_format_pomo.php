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

		$this->format = new Testable_GP_Format_PO;

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

		$file_contents = file_get_contents( $this->translation_file );

		$this->assertEquals( $file_contents, $this->format->print_exported_file( $project, $locale, $set, $entries_for_export ) );
	}

	/**
	 * @ticket GH-450
	 */
	public function test_export_includes_project_id_version_header() {
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
		$po_format = new Testable_GP_Format_PO();

		// Create a locale that has only a 639_1 language code.
		$as = new GP_Locale();
		$as->english_name = 'Assamese';
		$as->lang_code_iso_639_1 = 'as';

		$this->assertEquals( 'as', $po_format->get_language_code( $as ) );

		// Add the 639_2 language code.
		$as->lang_code_iso_639_2 = 'asn';
		$this->assertEquals( 'as', $po_format->get_language_code( $as ) );

		// Add the 639_3 language code.
		$as->lang_code_iso_639_3 = 'asm';
		$this->assertEquals( 'as',$po_format->get_language_code( $as ) );

		// Add the country language code, which is the same as the language code.
		$as->country_code = 'as';
		$this->assertEquals( 'as', $po_format->get_language_code( $as ) );

		// Change the country code to be different than the language code.
		$as->country_code = 'in';
		$this->assertEquals( 'as_IN', $po_format->get_language_code( $as ) );

		// Remove the country code for the next tests.
		$as->country_code = null;

		// Remove the 639_1 language code.
		$as->lang_code_iso_639_1 = '';
		$this->assertEquals( 'asn', $po_format->get_language_code( $as ) );

		// Remove the 639_2 language code.
		$as->lang_code_iso_639_2 = '';
		$this->assertEquals( 'asm', $po_format->get_language_code( $as ) );

		// Setup the locale to have the incorrect case in the locale information for country and language.
		$as->lang_code_iso_639_1 = 'AS';
		$as->country_code = 'IN';
		$this->assertEquals( 'as_IN', $po_format->get_language_code( $as ) );
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

		$this->format = new Testable_GP_Format_MO;
	}

	public function test_export_includes_project_id_version_header() {
		// MO files do no have header info, so "skip" this test without reporting that it has been skipped by phpunit.
		$this->assertEquals( 'Testable_GP_Format_MO', get_class( $this->format ) );
	}
}

/**
 * Class used test private/protected and/or override methods.
 *
 * @method string get_language_code( GP_Locale $locale  )
 */
class Testable_GP_Format_PO extends GP_Format_PO {

	/**
	 * List of private/protected methods.
	 *
	 * @var array
	 */
	private $non_accessible_methods = array(
		'get_language_code',
	);

	/**
	 * Make private/protected methods readable for tests.
	 *
	 * @param string   $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|bool Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( in_array( $name, $this->non_accessible_methods, true ) ) {
			return $this->$name( ...$arguments );
		}
		return false;
	}

	/**
	 * Overrides the value of the 'X-Generator' header field.
	 *
	 * @param GP_Format $format The format.
	 * @param string    $header The header field name.
	 * @param string    $text   The header field value.
	 */
	public function set_header( $format, $header, $text ) {
		if ( 'X-Generator' === $header ) {
			$text = 'GlotPress/[GP VERSION]';
		}
		parent::set_header( $format, $header, $text );
	}
}

/**
 * Class used test private/protected and/or override methods.
 */
class Testable_GP_Format_MO extends GP_Format_MO {

	/**
	 * Overrides the value of the 'X-Generator' header field.
	 *
	 * @param GP_Format $format The format.
	 * @param string    $header The header field name.
	 * @param string    $text   The header field value.
	 */
	public function set_header( $format, $header, $text ) {
		if ( 'X-Generator' === $header ) {
			$text = 'GlotPress/[GP VERSION]';
		}
		parent::set_header( $format, $header, $text );
	}
}
