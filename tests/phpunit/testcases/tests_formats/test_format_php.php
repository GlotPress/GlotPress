<?php

class GP_Test_Format_PHP extends GP_UnitTestCase {
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
	protected $format = 'php';

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
		$this->assertSame( 'PHP (.php)', GP::$formats[ $this->format ]->name );
	}

	public function test_format_extension() {
		$this->assertSame( 'php', GP::$formats[ $this->format ]->extension );
	}

	public function test_print_exported_file() {
		$entries = array(
			new Translation_Entry( array( 'singular' => 'foo', 'translations' => array( 'bar' ) ) ),
			new Translation_Entry( array( 'singular' => 'bar', 'translations' => array( 'baz' ) ) ),
		);

		$php = GP::$formats[ $this->format ]->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );

		$this->assertStringContainsString("'messages'=>['foo'=>['bar'],'bar'=>['baz']]", $php );
	}

	public function test_read_originals_from_file() {
		$this->markTestIncomplete( 'Not implemented' );
	}

	public function test_read_translations_from_file() {
		$this->markTestIncomplete( 'Not implemented' );
	}

	/**
	 * Returns the expected data for the parsed example-untranslated.php file.
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
	 * Returns the expected data for the parsed example-untranslated.php file.
	 */
	public function data_example_translations() {
		$translations = new Translations();
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'This file is too big. Files must be less than %d KB in size.',
			'translations' => array(
				'Diese Datei ist zu gross. Dateien müssen kleiner als %d KB sein.',
			),
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => '%d Theme Update',
			'translations' => array(
				'%d Theme-Aktualisierung',
				'%d Theme-Aktualisierungen',
			)
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Medium',
			'context'      => 'password strength',
			'translations' => array(
				'Medium',
			)
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Category',
			'context'      => 'taxonomy singular name',
			'translations' => array(
				'Kategorie',
			)
		) ) );
		$translations->add_entry( new Translation_Entry( array(
			'singular'     => 'Pages',
			'context'      => 'post type general name',
			'translations' => array(
				'Seiten',
			)
		) ) );

		return $translations;
	}
}
