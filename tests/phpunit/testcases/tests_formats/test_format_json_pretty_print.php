<?php

class GP_Test_JSON_Pretty_Print extends GP_UnitTestCase {
	/**
	 * @var GP_Translation_Set
	 */
	protected $translation_set;

	/**
	 * @var GP_Locale
	 */
	protected $locale;

	public function setUp() {
		parent::setUp();

		$this->translation_set = $this->factory->translation_set->create_with_project_and_locale( array(), array( 'name' => 'foo_project' ) );

		$this->locale = new GP_Locale( array(
			'slug'              => $this->translation_set->locale,
			'nplurals'          => 2,
			'plural_expression' => 'n != 1',
		) );
	}

	public function test_jed1x_print_exported_file_pretty_print() {
		$entries = array(
			new Translation_Entry( array( 'singular' => 'foo', 'translations' => array( 'bar' ) ) ),
		);

		add_filter( 'gp_json_export_pretty_print', '__return_true' );
		$actual = GP::$formats['jed1x']->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );
		remove_filter( 'gp_json_export_pretty_print', '__return_true' );

		// The pretty-printed output has 15 lines in total.
		$this->assertSame( 14, substr_count( $actual, "\n" ) );
	}

	public function test_print_exported_file_pretty_print() {
		$entries = array(
			new Translation_Entry( array( 'singular' => 'foo', 'translations' => array( 'bar' ) ) ),
			new Translation_Entry( array( 'singular' => 'bar', 'translations' => array( 'baz' ) ) ),
		);

		add_filter( 'gp_json_export_pretty_print', '__return_true' );
		$actual = GP::$formats['json']->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );
		remove_filter( 'gp_json_export_pretty_print', '__return_true' );

		// The pretty-printed output has 8 lines in total.
		$this->assertSame( 7, substr_count( $actual, "\n" ) );
	}
}
