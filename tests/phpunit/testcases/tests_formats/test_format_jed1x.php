<?php

class GP_Test_Format_Jed1x extends GP_UnitTestCase {
	/**
	 * @var GP_Translation_Set
	 */
	protected $translation_set;

	/**
	 * @var GP_Locale
	 */
	protected $locale;

	/**
	 * @var string
	 */
	protected $format = 'jed1x';

	public function setUp() {
		parent::setUp();

		$this->translation_set = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'foo_project' ) );

		$this->locale = new GP_Locale( array(
			'slug'                => $this->translation_set->locale,
			'nplurals'            => 2,
			'plural_expression'   => 'n != 1',
			'lang_code_iso_639_1' => $this->translation_set->locale,
		) );
	}

	public function test_format_name() {
		$this->assertSame( 'Jed 1.x (.json)', GP::$formats[ $this->format ]->name );
	}

	public function test_format_extension() {
		$this->assertSame( 'jed.json', GP::$formats[ $this->format ]->extension );
	}

	public function test_print_exported_file_can_be_decoded() {
		$entries = array(
			new Translation_Entry( array( 'singular' => 'foo', 'translations' => array( 'foox' ) ) ),
		);

		$json = GP::$formats[ $this->format ]->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );

		$this->assertNotNull( json_decode( $json, true ) );
	}

	public function test_print_exported_file_has_valid_format() {
		$entries = array(
			new Translation_Entry( array( 'singular' => 'foo', 'translations' => array( 'bar' ) ) ),
		);

		$json = GP::$formats[ $this->format ]->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );

		$actual = json_decode( $json, true );

		$this->assertEquals( array(
			'translation-revision-date' => '+0000',
			'generator'                 => 'GlotPress/' . GP_VERSION,
			'domain'                    => 'messages',
			'locale_data'               => array(
				'messages' => array(
					''    => array(
						'domain'       => 'messages',
						'plural-forms' => 'nplurals=2; plural=n != 1;',
						'lang'         => $this->translation_set->locale,
					),
					'foo' => array( 'bar' ),
				),
			),
		), $actual );
	}

	public function test_read_originals_from_file_non_existent_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/foo.json' ) );
	}

	public function test_read_originals_from_file_invalid_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/invalid.json' ) );
	}

	public function test_read_originals_from_file_missing_domain() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/translation-jed1x-missing-domain.json' ) );
	}

	public function test_read_originals_from_file_missing_locale_data() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/translation-jed1x-missing-locale-data.json' ) );
	}

	public function test_read_originals_from_file() {
		$expected = $this->data_example_originals();

		/* @var Translations $actual */
		$actual = GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/originals-jed1x.json' );
		$this->assertSame( 5, count( $actual->entries ) );
		$this->assertEquals( $expected, $actual );
	}

	public function test_read_translations_from_file_non_existent_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/foo.json' ) );
	}

	public function test_read_translations_from_file_missing_domain() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/translation-jed1x-missing-domain.json' ) );
	}

	public function test_read_translations_from_file_missing_locale_data() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/translation-jed1x-missing-locale-data.json' ) );
	}

	public function test_read_translations_from_file() {
		$expected = $this->data_example_translations();

		/* @var Translations $actual */
		$actual = GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/translation-jed1x.json' );

		$this->assertSame( 5, count( $actual->entries ) );
		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Returns the expected data for the parsed example-untranslated.json file.
	 */
	public function data_example_originals() {
		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'This file is too big. Files must be less than %d KB in size.',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => '%d Theme Update',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Medium',
			'context'  => 'password strength',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Category',
			'context'  => 'taxonomy singular name',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Pages',
			'context'  => 'post type general name',
		) ) );

		return $translations;
	}

	/**
	 * Returns the expected data for the parsed example-untranslated.json file.
	 */
	public function data_example_translations() {
		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'This file is too big. Files must be less than %d KB in size.',
			'translations' => 'Diese Datei ist zu gross. Dateien müssen kleiner als %d KB sein.',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => '%d Theme Update',
			'translations' => '%d Theme-Aktualisierung',
			'plural'       => '%d Theme-Aktualisierungen',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Medium',
			'context'      => 'password strength',
			'translations' => 'Medium',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Category',
			'context'      => 'taxonomy singular name',
			'translations' => 'Kategorie',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Pages',
			'context'      => 'post type general name',
			'translations' => 'Seiten',
		) ) );

		return $translations;
	}
}
