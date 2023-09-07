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
	 * Expects matching only "alt" and "title" elements inside an HTML tag.
	 */
	function test_map_glossary_entries_to_translation_originals_matching_only_some_terms_in_html_tags() {
		$test_string = 'This is a strong test <strong>strong</strong>. This is another<dd>strong</dd>, very strong test with<img src="strong.img" title="Strong text" alt="Alt strong text" /> images <hr/>.';
		$expected_result = 'This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" /&gt; images &lt;hr/&gt;.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'strong',
			'part_of_speech' => 'noun',
			'translation' => 'forte',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	/**
	 * Expects matching only "alt" and "title" elements inside an HTML tag in the singular and plural origin.
	 */
	function test_map_glossary_entries_to_translation_originals_matching_only_some_terms_in_html_tags_in_the_plural_origin() {
		$singular_string          = 'This is a strong test <strong>strong</strong>. This is another<dd>strong</dd>, very strong test with<img src="strong.img" title="Strong text" alt="Alt strong text" /> images <hr/>.';
		$plural_string            = 'Plural. This is a strong test <strong>strong</strong>. This is another<dd>strong</dd>, very strong test with<img src="strong.img" title="Strong text" alt="Alt strong text" /> images <hr/>.';
		$singular_expected_result = 'This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" /&gt; images &lt;hr/&gt;.';
		$plural_expected_result   = 'Plural. This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" /&gt; images &lt;hr/&gt;.';

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'strong',
			'part_of_speech' => 'noun',
			'translation' => 'forte',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $singular_expected_result );
		$this->assertEquals( $orig->plural_glossary_markup, $plural_expected_result );
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
		$test_string     = "  Two spaces at the begining and end, and in the line below:  \n\n One space at the begining and end \n\nNo spaces\n One space at the begining\nOne space at the end \n\n\nMultiple spaces  in   multiline  \n One space at the begining and end ";
		$expected_result = "<span class=\"invisible-spaces\">  </span>Two spaces at the begining and end, and in the line below:<span class=\"invisible-spaces\">  </span><span class='invisibles' title='New line'>&crarr;</span>\n<span class='invisibles' title='New line'>&crarr;</span>\n<span class=\"invisible-spaces\"> </span>One space at the begining and end<span class=\"invisible-spaces\"> </span><span class='invisibles' title='New line'>&crarr;</span>\n<span class='invisibles' title='New line'>&crarr;</span>\nNo spaces<span class='invisibles' title='New line'>&crarr;</span>\n<span class=\"invisible-spaces\"> </span>One space at the begining<span class='invisibles' title='New line'>&crarr;</span>\nOne space at the end<span class=\"invisible-spaces\"> </span><span class='invisibles' title='New line'>&crarr;</span>\n<span class='invisibles' title='New line'>&crarr;</span>\n<span class='invisibles' title='New line'>&crarr;</span>\nMultiple spaces<span class=\"invisible-spaces\">  </span>in<span class=\"invisible-spaces\">   </span>multiline<span class=\"invisible-spaces\">  </span><span class='invisibles' title='New line'>&crarr;</span>\n<span class=\"invisible-spaces\"> </span>One space at the begining and end<span class=\"invisible-spaces\"> </span>";

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

	/**
	 * Expects highlighting line breaks and tabs.
	 */
	function test_prepare_original_with_line_breaks_and_tabs() {
		$test_string     = "This string has 2x tabs\t\tand a line\nbreak.";
		$expected_result = "This string has 2x tabs<span class='invisibles' title='Tab character'>&rarr;</span>\t<span class='invisibles' title='Tab character'>&rarr;</span>\tand a line<span class='invisibles' title='New line'>&crarr;</span>\nbreak.";

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

	function provide_test_map_glossary_entries_to_translation_originals() {
		foreach ( array(
			'party' => array(
				'Welcome to the party.',
				'My parties.',
				'I know, partys is the wrong plural ending but we need it because of nouns like boys.',
			),
			'color' => array(
				'One color.',
				'Two colors.',
			),
			'half' => array(
				'Half a loaf is better than none.',
				'Two halves are even better.',
			),
			'man' => array(
				'The word man is the root of the word mankind.',
				'There are men but there is no menkind.',
			),
			'issue' => array(
				'If you find a bug, file an issue.',
				'If you find two bugs, please file two issues.',
			),
			'report' => array(
				'I reported a bug.',
				'Now there is a bug report.',
				'We call it bug reporting.',
			),
		) as $expected_result => $test_strings ) {
			foreach ( $test_strings as $test_string ) {
				yield array( $test_string, $expected_result );
			}
		}
	}

	/**
	 * @dataProvider provide_test_map_glossary_entries_to_translation_originals
	 */
	function test_map_glossary_entries_to_translation_originals_with_suffixes( $test_string, $expected_result ) {
		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entries = array(
			array(
				'term' => 'party',
				'part_of_speech' => 'noun',
				'translation' => 'party',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'color',
				'part_of_speech' => 'noun',
				'translation' => 'color',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'half',
				'part_of_speech' => 'noun',
				'translation' => 'half',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'man',
				'part_of_speech' => 'noun',
				'translation' => 'man',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'issue',
				'part_of_speech' => 'noun',
				'translation' => 'issue',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'report',
				'part_of_speech' => 'noun',
				'translation' => 'report',
				'glossary_id' => $glossary->id,
			),
		);

		foreach ( $glossary_entries as $glossary_entry ) {
			GP::$glossary_entry->create_and_select( $glossary_entry );
		}

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertMatchesRegularExpression( '#<span class="glossary-word" data-translations="\[{&quot;translation&quot;:&quot;' . $expected_result . '&quot;,[^"]+">[^<]+</span>#', $orig->singular_glossary_markup );
	}
}
