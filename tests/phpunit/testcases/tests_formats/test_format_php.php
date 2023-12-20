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
			new Translation_Entry( array(
				'context'      => 'somecontext',
				'singular'     => 'bar',
				'translations' => array( 'baz' ),
			) ),
			new Translation_Entry( array(
				'singular'     => '%d Theme Update',
				'translations' => array(
					'%d Theme Update',
					'%d Theme Updates',
				),
			) ),
		);

		$php = GP::$formats[ $this->format ]->print_exported_file( $this->translation_set->project, $this->locale, $this->translation_set, $entries );

		$this->assertStringContainsString("'project-id-version'=>'foo_project'", $php );
		$this->assertStringContainsString("'language'=>'aa'", $php );
		$this->assertStringContainsString("'foo'=>'bar'", $php );
		$this->assertStringContainsString("'somecontext\4bar'=>'baz'", $php );
		$this->assertStringContainsString("'%d Theme Update'=>'%d Theme Update' . \"\\0\" . '%d Theme Updates'", $php );
	}

	public function test_read_originals_from_file() {
		$this->markTestIncomplete( 'Not implemented' );
	}

	public function test_read_translations_from_file() {
		$this->markTestIncomplete( 'Not implemented' );
	}
}
