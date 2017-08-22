<?php

class GP_Test_Format_JSON extends GP_UnitTestCase {
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
	protected $format = 'ngx';

	public function setUp() {
		parent::setUp();

		$this->translation_set = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'foo_project' ) );

		$this->locale = new GP_Locale( array(
			'slug'              => $this->translation_set->locale,
			'nplurals'          => 2,
			'plural_expression' => 'n != 1',
		) );
	}

	public function test_format_name() {
		$this->assertSame( 'NGX-Translate (.json)', GP::$formats[ $this->format ]->name );
	}

	public function test_format_extension() {
		$this->assertSame( 'json', GP::$formats[ $this->format ]->extension );
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
			new Translation_Entry( array( 'singular' => 'foo', 'context' => 'STG1', 'translations' => 'bar' ) ),
			new Translation_Entry( array( 'singular' => 'bar', 'context' => 'STG2', 'translations' => 'baz' ) ),
		);

		$json = GP::$formats[ $this->format ]->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );

		$actual = json_decode( $json, true );

		$this->assertEquals( array(
			'STG1' => 'bar',
			'STG2' => 'baz',
		), $actual );
	}

	public function test_read_originals_from_file_non_existent_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/foo.json' ) );
	}

	public function test_read_originals_from_file_invalid_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/invalid.json' ) );
	}

	public function test_read_originals_from_file() {
		$expected = $this->data_example_originals();

		/* @var Translations $actual */
		$actual = GP::$formats[ $this->format ]->read_originals_from_file( GP_DIR_TESTDATA . '/originals-ngx.json' );
		$this->assertSame( 5, count( $actual->entries ) );
		$this->assertEquals( $expected, $actual );
	}

	public function test_read_translations_from_file_non_existent_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/foo.json' ) );
	}

	public function test_read_translations_from_file_invalid_file() {
		$this->assertFalse( GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/invalid.json' ) );
	}

	public function test_read_translations_from_file() {
		$expected = $this->data_example_translations();

		/* @var Translations $actual */
		$actual = GP::$formats[ $this->format ]->read_translations_from_file( GP_DIR_TESTDATA . '/translation-ngx.json' );

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
			'context'  => 'ORIGINAL1',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => '%d Theme Update',
			'context'  => 'ORIGINAL2',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'password strength',
			'context'  => 'ORIGINAL3',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'taxonomy singular name',
			'context'  => 'ORIGINAL4',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'post type general name',
			'context'  => 'ORIGINAL5',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'on',
			'context'  => 'ORIGINAL6[1]',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'off',
			'context'  => 'ORIGINAL6[2]',
		) ) );
		return $translations;
	}

	/**
	 * Returns the expected data for the parsed example-untranslated.json file.
	 */
	public function data_example_translations() {
		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Ce fichier est trop grang. Les fichiers doivent avoir une taille plus petite que %d KB.',
			'context'  => 'ORIGINAL1',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => '%d Mise \u00e0 jour de th\u00e8me',
			'context'  => 'ORIGINAL2',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Force du mot de passe',
			'context'  => 'ORIGINAL3',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Nom de la taxonomie au singulier',
			'context'  => 'ORIGINAL4',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'Nom générique pour les posts',
			'context'  => 'ORIGINAL5',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'actif',
			'context'  => 'ORIGINAL6[1]',
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular' => 'inactif',
			'context'  => 'ORIGINAL6[2]',
		) ) );
		return $translations;
	}
}
