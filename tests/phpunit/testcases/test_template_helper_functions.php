<?php

// As the test cases don't load the GP front end, we need to include the helper-functions here to make them available.
require_once GP_TMPL_PATH . 'helper-functions.php';

class GP_Test_Template_Helper_Functions extends GP_UnitTestCase {

	function test_map_glossary_entries_to_translation_originals_with_ampersand_in_glossary() {
		$test_string = 'This string, <code>&lt;/body&gt;</code>, should not have the code tags mangled.';
		$orig = '';
		$expected_result = 'This string, &lt;code&gt;&amp;lt;/body<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;&amp;amp;&quot;,&quot;pos&quot;:&quot;interjection&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">&amp;</span>gt;&lt;/code&gt;, should not have the code tags mangled.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => '&',
			'part_of_speech' => 'interjection',
			'translation' => '&amp;',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects matching a term with a space between words [color scheme].
	 */
	function test_map_glossary_entries_to_translation_originals_with_spaces_in_glossary() {
		$test_string = 'Please set your favorite color scheme.';
		$orig = '';
		$expected_result = 'Please set your favorite <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;paleta de cores&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">color scheme</span>.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'color scheme',
			'part_of_speech' => 'noun',
			'translation' => 'paleta de cores',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects matching a term with an hyphen [color-scheme].
	 */
	function test_map_glossary_entries_to_translation_originals_with_hyphens_in_glossary() {
		$test_string = 'Please set your favorite color-scheme.';
		$orig = '';
		$expected_result = 'Please set your favorite <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;paleta de cores&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">color-scheme</span>.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'color-scheme',
			'part_of_speech' => 'noun',
			'translation' => 'paleta de cores',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects matching a term with space and hyphen mixed [GlotPress WP-Team].
	 */
	function test_map_glossary_entries_to_translation_originals_with_spaces_and_hyphens_in_glossary() {
		$test_string = 'Prowdly built by your GlotPress WP-Team.';
		$orig = '';
		$expected_result = 'Prowdly built by your <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;Equipa-WP do GlotPress&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">GlotPress WP-Team</span>.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'GlotPress WP-Team',
			'part_of_speech' => 'noun',
			'translation' => 'Equipa-WP do GlotPress',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects matching the 3 words term [admin color scheme] instead of the 2 words term [color scheme] or single word term [admin].
	 */
	function test_map_glossary_entries_to_translation_originals_with_word_count_priority() {
		$test_string = 'Please set your admin color scheme.';
		$orig = '';
		$expected_result = 'Please set your <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;paleta de cores do administrador&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">admin color scheme</span>.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entries = array(
			array(
				'term' => 'admin',
				'part_of_speech' => 'noun',
				'translation' => 'administrador',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'color scheme',
				'part_of_speech' => 'noun',
				'translation' => 'paleta de cores',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'admin color scheme',
				'part_of_speech' => 'noun',
				'translation' => 'paleta de cores do administrador',
				'glossary_id' => $glossary->id,
			),
		);

		foreach ( $glossary_entries as $glossary_entry ) {
			GP::$glossary_entry->create_and_select( $glossary_entry );
		}

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects highlighting leading and ending spaces in single line strings, and double/multiple spaces in the middle.
	 */
	function test_prepare_original_with_leading_and_trailing_spaces_and_multiple_spaces_in_middle_of_single_line_strings() {
		$test_string     = '  Two spaces at the begining, double  and triple   spaces in the middle, and one space in the end. ';
		$expected_result = '<span class="invisible-spaces">  </span>Two spaces at the begining, double<span class="invisible-spaces">  </span>and triple<span class="invisible-spaces">   </span>spaces in the middle, and one space in the end.<span class="invisible-spaces"> </span>';

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

	/**
	 * Expects highlighting leading and ending spaces in multi line strings, and double/multiple spaces in the middle.
	 */
	function test_prepare_original_with_leading_and_trailing_spaces_and_multiple_spaces_in_middle_of_multi_line_strings() {
		$test_string     = '  Two spaces at the begining and end, and in the line below:  \n\n One space at the begining and end \n\nNo spaces\n One space at the begining\nOne space at the end \n\n\nMultiple spaces  in   multiline  \n One space at the begining and end ';
		$expected_result = '<span class="invisible-spaces">  </span>Two spaces at the begining and end, and in the line below:<span class="invisible-spaces">  </span>\n\n<span class="invisible-spaces"> </span>One space at the begining and end<span class="invisible-spaces"> </span>\n\nNo spaces\n<span class="invisible-spaces"> </span>One space at the begining\nOne space at the end<span class="invisible-spaces"> </span>\n\n\nMultiple spaces<span class="invisible-spaces">  </span>in<span class="invisible-spaces">   </span>multiline<span class="invisible-spaces">  </span>\n<span class="invisible-spaces"> </span>One space at the begining and end<span class="invisible-spaces"> </span>';

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

}
