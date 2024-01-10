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
		$test_string = 'This is a strong test <strong class="strong-class another-class strong" alt="A strong alt" style="some-property:strong;">strong</strong>. This is another<dd style="a-property:strong;" class="strong strong-class another-class">strong</dd>, very strong test with<img src="strong.img" title="Strong text. Very strong strong text" class="a-very-strong-really-Strong-class" alt="Alt strong text" style="another-property:strong-very-strong;" />strong images, very strong images.<hr/ alt="Alt strong" class="Strong class StRoNg" title="StRoNg very strong" src="file.strong">. The final strong text.';
		$expected_result = 'This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong class="strong-class another-class strong" alt="A <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> alt" style="some-property:strong;"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text. Very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" class="a-very-strong-really-Strong-class" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" style="another-property:strong-very-strong;" /&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images.&lt;hr/ alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" class="Strong class StRoNg" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">StRoNg</span> very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" src="file.strong"&gt;. The final <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text.';

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
		$singular_string          = 'This is a strong test <strong class="strong-class another-class strong" alt="A strong alt" style="some-property:strong;">strong</strong>. This is another<dd style="a-property:strong;" class="strong strong-class another-class">strong</dd>, very strong test with<img src="strong.img" title="Strong text. Very strong strong text" class="a-very-strong-really-Strong-class" alt="Alt strong text" style="another-property:strong-very-strong;" />strong images, very strong images.<hr/ alt="Alt strong" class="Strong class StRoNg" title="StRoNg very strong" src="file.strong">. The final strong text.';
		$plural_string            = 'Plural. This is a strong test <strong class="strong-class another-class strong" alt="A strong alt" style="some-property:strong;">strong</strong>. This is another<dd style="a-property:strong;" class="strong strong-class another-class">strong</dd>, very strong test with<img src="strong.img" title="Strong text. Very strong strong text" class="a-very-strong-really-Strong-class" alt="Alt strong text" style="another-property:strong-very-strong;" />strong images, very strong images.<hr/ alt="Alt strong" class="Strong class StRoNg" title="StRoNg very strong" src="file.strong">. The final strong text.';
		$singular_expected_result = 'This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong class="strong-class another-class strong" alt="A <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> alt" style="some-property:strong;"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text. Very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" class="a-very-strong-really-Strong-class" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" style="another-property:strong-very-strong;" /&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images.&lt;hr/ alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" class="Strong class StRoNg" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">StRoNg</span> very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" src="file.strong"&gt;. The final <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text.';
		$plural_expected_result   = 'Plural. This is a <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test &lt;strong class="strong-class another-class strong" alt="A <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> alt" style="some-property:strong;"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>&lt;/dd&gt;, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> test with&lt;img src="strong.img" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Strong</span> text. Very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" class="a-very-strong-really-Strong-class" alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text" style="another-property:strong-very-strong;" /&gt;<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images, very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> images.&lt;hr/ alt="Alt <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" class="Strong class StRoNg" title="<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">StRoNg</span> very <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span>" src="file.strong"&gt;. The final <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;forte&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">strong</span> text.';

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

	function test_map_glossary_entries_with_placeholders_glued_glossary_words() {
		$test_string = 'I %%show want to reshow and show and test %3$show%4$show to %2$dshow%2$b test %show%d %sshow%d %3$sshow%4$s and%3$s%3$s test and show and %3$s show how show %4$s %%4%show %%show how.';
		$expected_result = 'I %%<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> want to reshow and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> and test %3$show%4$show to %2$d<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%2$b test %show%d %s<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%d %3$s<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%4$s and%3$s%3$s test and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> and %3$s <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> how <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> %4$s %%4%show %%<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> how.';

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'show',
			'part_of_speech' => 'verb',
			'translation' => 'amosar',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

	function test_map_glossary_entries_with_placeholders_glued_glossary_words_in_the_plural_origin() {
		$singular_string = 'I %%show want to reshow and show and test %3$show%4$show to %2$dshow%2$b test %show%d %sshow%d %3$sshow%4$s and%3$s%3$s test and show and %3$s show how show %4$s %%4%show %%show how.';
		$plural_string   = 'Plural. ' . $singular_string;
		$singular_expected_result = 'I %%<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> want to reshow and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> and test %3$show%4$show to %2$d<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%2$b test %show%d %s<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%d %3$s<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span>%4$s and%3$s%3$s test and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> and %3$s <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> how <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> %4$s %%4%show %%<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;amosar&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">show</span> how.';
		$plural_expected_result = 'Plural. ' . $singular_expected_result;

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'show',
			'part_of_speech' => 'verb',
			'translation' => 'amosar',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $singular_expected_result );
		$this->assertEquals( $orig->plural_glossary_markup, $plural_expected_result );
	}

	/*
	 * Doesn't match the glossary word inside other words.
	 */
	function test_map_glossary_entries_with_placeholders_inside_another_words() {
		$singular_string          = 'My alidads and granddaddies and dad and dads and skedaddle and hispanidad and dadaistic';
		$plural_string            = 'Plural. ' . $singular_string;
		$singular_expected_result = 'My alidads and granddaddies and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;pai&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">dad</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;pai&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">dads</span> and skedaddle and hispanidad and dadaistic';
		$plural_expected_result   = 'Plural. ' . $singular_expected_result;

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entry = array(
			'term' => 'dad',
			'part_of_speech' => 'noun',
			'translation' => 'pai',
			'glossary_id' => $glossary->id,
		);

		GP::$glossary_entry->create_and_select( $glossary_entry );

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $singular_expected_result );
		$this->assertEquals( $orig->plural_glossary_markup, $plural_expected_result );	}

	/*
	 * Matches the glossary variations.
	 */
	function test_map_glossary_entries_with_variations() {
		$singular_string          = 'Converting, converts, converted and convert.';
		$plural_string            = 'Plural. ' . $singular_string;
		$singular_expected_result = '<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Converting</span>, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">converts</span>, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">converted</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">convert</span>.';
		$plural_expected_result   = 'Plural. ' . $singular_expected_result;

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entries = array(
			array(
				'term' => 'delay',
				'part_of_speech' => 'noun',
				'translation' => 'retraso',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'key',
				'part_of_speech' => 'noun',
				'translation' => 'chave',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'toy',
				'part_of_speech' => 'noun',
				'translation' => 'xoguete',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'guy',
				'part_of_speech' => 'noun',
				'translation' => 'rapaz',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'see',
				'part_of_speech' => 'verb',
				'translation' => 'ver',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'convert',
				'part_of_speech' => 'verb',
				'translation' => 'converter',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'chef',
				'part_of_speech' => 'noun',
				'translation' => 'cociñeiro',
				'glossary_id' => $glossary->id,
			),
		);

		foreach ( $glossary_entries as $glossary_entry ) {
			GP::$glossary_entry->create_and_select( $glossary_entry );
		}

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $singular_expected_result );
		$this->assertEquals( $orig->plural_glossary_markup, $plural_expected_result );
	}

	/*
	 * Matches the glossary variations and placeholders.
	 */
	function test_map_glossary_entries_with_variations_and_placeholders() {
		$singular_string          = 'Delay and delays, key and keys, toy and toys, guy and guys, %see%s %1$guys%2$s %ssee%s %1$gguys%2$s, converting and convert.';
		$plural_string            = 'Plural. ' . $singular_string;
		$singular_expected_result = '<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;retraso&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">Delay</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;retraso&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">delays</span>, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;chave&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">key</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;chave&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">keys</span>, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;xoguete&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">toy</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;xoguete&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">toys</span>, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;rapaz&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">guy</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;rapaz&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">guys</span>, %see%s %1$guys%2$s %s<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;ver&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">see</span>%s %1$g<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;rapaz&quot;,&quot;pos&quot;:&quot;noun&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">guys</span>%2$s, <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">converting</span> and <span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;converter&quot;,&quot;pos&quot;:&quot;verb&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">convert</span>.';
		$plural_expected_result   = 'Plural. ' . $singular_expected_result;

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entries = array(
			array(
				'term' => 'delay',
				'part_of_speech' => 'noun',
				'translation' => 'retraso',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'key',
				'part_of_speech' => 'noun',
				'translation' => 'chave',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'toy',
				'part_of_speech' => 'noun',
				'translation' => 'xoguete',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'guy',
				'part_of_speech' => 'noun',
				'translation' => 'rapaz',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'see',
				'part_of_speech' => 'verb',
				'translation' => 'ver',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'convert',
				'part_of_speech' => 'verb',
				'translation' => 'converter',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'chef',
				'part_of_speech' => 'noun',
				'translation' => 'cociñeiro',
				'glossary_id' => $glossary->id,
			),
		);

		foreach ( $glossary_entries as $glossary_entry ) {
			GP::$glossary_entry->create_and_select( $glossary_entry );
		}

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $singular_expected_result );
		$this->assertEquals( $orig->plural_glossary_markup, $plural_expected_result );	}

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
			),
			'color' => array(
				'One color.',
				'Two colors.',
			),
			'half' => array(
				'Half a loaf is better than none.',
				'Two halves are even better.',
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
				'term' => 'issue',
				'part_of_speech' => 'noun',
				'translation' => 'issue',
				'glossary_id' => $glossary->id,
			),
			array(
				'term' => 'report',
				'part_of_speech' => 'verb',
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

	/**
	 * Expects matching the plurals of Nouns ending in a sibilant. Suffix: '-es'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_ending_with_sibilant_in_glossary() {
		$test_string = 'Testing words kiss, kisses, waltz, waltzes, box, boxes, dish, dishes, coach, coaches.';
		$part_of_speech = 'noun';

		$matches = array(
			'beijo' => array( // Portuguese.
				'kiss',         // Singular.
				'kisses',       // Plural.
			),
			'valsa' => array( // Portuguese.
				'waltz',        // Singular.
				'waltzes',      // Plural.
			),
			'caixa' => array( // Portuguese.
				'box',          // Singular.
				'boxes',        // Plural.
			),
			'prato' => array( // Portuguese.
				'dish',         // Singular.
				'dishes',       // Plural.
			),
			'treinador' => array( // Portuguese.
				'coach',            // Singular.
				'coaches',          // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'kiss', // Kiss and kisses.
				'part_of_speech' => $part_of_speech,
				'translation' => 'beijo', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'waltz', // Waltz and waltzes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'valsa', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'box', // Box and boxes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'caixa', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'dish', // Dish and dishes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'prato', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'coach', // Coach and coaches.
				'part_of_speech' => $part_of_speech,
				'translation' => 'treinador', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the plurals of Nouns ending with '-y' preceded by vowel. Suffix: '-s'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_ending_with_y_preceded_by_vowel_in_glossary() {
		$test_string = 'Testing words delay, delays, key, keys, toy, toys, guy, guys.';
		$part_of_speech = 'noun';

		$matches = array(
			'atraso' => array( // Portuguese.
				'delay',         // Singular.
				'delays',        // Plural.
			),
			'chave' => array( // Portuguese.
				'key',          // Singular.
				'keys',         // Plural.
			),
			'brinquedo' => array( // Portuguese.
				'toy',              // Singular.
				'toys',             // Plural.
			),
			'rapaz' => array( // Portuguese.
				'guy',          // Singular.
				'guys',         // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'delay', // Delay and delays.
				'part_of_speech' => $part_of_speech,
				'translation' => 'atraso', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'key', // Key and keys.
				'part_of_speech' => $part_of_speech,
				'translation' => 'chave', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'toy', // Toy and toys.
				'part_of_speech' => $part_of_speech,
				'translation' => 'brinquedo', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'guy', // Guy and guys.
				'part_of_speech' => $part_of_speech,
				'translation' => 'rapaz', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the plurals of Nouns ending with '-y' preceded by consonant. Suffix: '-ies'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_ending_with_o_and_y_preceded_by_consonant_in_glossary() {
		$test_string = 'Testing words lady, ladies.';
		$part_of_speech = 'noun';

		$matches = array(
			'senhora' => array( // Portuguese.
				'lady',           // Singular.
				'ladies',         // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'lady', // Lady and ladies.
				'part_of_speech' => $part_of_speech,
				'translation' => 'senhora', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the plurals of Nouns alternate ending with '-o' preceded by consonant. Suffix: '-es'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_alternate_ending_with_o_preceded_by_consonant_in_glossary() {
		$test_string = 'Testing words hero, heroes, tomato, tomatoes.';
		$part_of_speech = 'noun';

		$matches = array(
			'her\u00f3i' => array( // Portuguese.
				'hero',              // Singular.
				'heroes',            // Plural.
			),
			'tomate' => array( // Portuguese.
				'tomato',        // Singular.
				'tomatoes',      // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'hero', // Hero and heroes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'herói', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'tomato', // Tomato and tomatoes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'tomate', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the plurals of Nouns ending with '-f' or '-fe'. Suffix: '-ves'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_ending_with_f_in_glossary() {
		$test_string = 'Testing words wife, wives, leaf, leaves, wolf, wolves.';
		$part_of_speech = 'noun';

		$matches = array(
			'esposa' => array( // Portuguese.
				'wife',          // Singular.
				'wives',         // Plural.
			),
			'folha' => array( // Portuguese.
				'leaf',         // Singular.
				'leaves',       // Plural.
			),
			'lobo' => array( // Portuguese.
				'wolf',        // Singular.
				'wolves',      // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'wife', // Wife and wives.
				'part_of_speech' => $part_of_speech,
				'translation' => 'esposa', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'leaf', // Leaf and leaves.
				'part_of_speech' => $part_of_speech,
				'translation' => 'folha', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'wolf', // Wolf and wolves.
				'part_of_speech' => $part_of_speech,
				'translation' => 'lobo', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the fallback plurals of Nouns ending with '-s'. Suffix '-es'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_fallback_ending_with_s_in_glossary() {
		$test_string = 'Testing words bus, buses, lens, lenses.';
		$part_of_speech = 'noun';

		$matches = array(
			'autocarro' => array( // Portuguese.
				'bus',              // Singular.
				'buses',            // Plural.
			),
			'lente' => array( // Portuguese.
				'lens',         // Singular.
				'lenses',       // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'bus', // Bus and buses.
				'part_of_speech' => $part_of_speech,
				'translation' => 'autocarro', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'lens', // Lens and lenses.
				'part_of_speech' => $part_of_speech,
				'translation' => 'lente', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the fallback plurals of Nouns not ending with '-s'. Suffix '-s'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_nouns_fallback_not_ending_with_s_in_glossary() {
		$test_string = 'Testing words chef, chefs, chief, chiefs, plan, plans, human, humans.';
		$part_of_speech = 'noun';

		$matches = array(
			'chef' => array( // Portuguese.
				'chef',        // Singular.
				'chefs',       // Plural.
			),
			'chefe' => array( // Portuguese.
				'chief',        // Singular.
				'chiefs',       // Plural.
			),
			'plano' => array( // Portuguese.
				'plan',         // Singular.
				'plans',        // Plural.
			),
			'humano' => array( // Portuguese.
				'human',         // Singular.
				'humans',        // Plural.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'chef', // Chef and chefs.
				'part_of_speech' => $part_of_speech,
				'translation' => 'chef', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'chief', // Chief and chiefs.
				'part_of_speech' => $part_of_speech,
				'translation' => 'chefe', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'plan', // Plan and plans.
				'part_of_speech' => $part_of_speech,
				'translation' => 'plano', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'human', // Human and humans.
				'part_of_speech' => $part_of_speech,
				'translation' => 'humano', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the third-person of Verbs ending in a sibilant. Suffix: '-es'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_3rdperson_ending_with_sibilant_in_glossary() {
		$test_string = 'Testing words pass, passes, bias, biases, focus, focuses, quiz, quizes, fix, fixes, push, pushes, watch, watches.';
		$part_of_speech = 'verb';

		$matches = array(
			'passar' => array( // Portuguese.
				'pass',          // Infinitive.
				'passes',        // Third-person.
			),
			'enviesar' => array( // Portuguese.
				'bias',            // Infinitive.
				'biases',          // Third-person.
			),
			'focar' => array( // Portuguese.
				'focus',        // Infinitive.
				'focuses',      // Third-person.
			),
			'questionar' => array( // Portuguese.
				'quiz',              // Infinitive.
				'quizes',            // Third-person.
			),
			'corrigir' => array( // Portuguese.
				'fix',             // Infinitive.
				'fixes',           // Third-person.
			),
			'empurrar' => array( // Portuguese.
				'push',            // Infinitive.
				'pushes',          // Third-person.
			),
			'ver' => array( // Portuguese.
				'watch',      // Infinitive.
				'watches',    // Third-person.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'pass', // Pass and passes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'passar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'bias', // Bias and biases.
				'part_of_speech' => $part_of_speech,
				'translation' => 'enviesar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'focus', // Focus and focuses.
				'part_of_speech' => $part_of_speech,
				'translation' => 'focar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'quiz', // Quiz and quizes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'questionar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'fix', // Fix and fixes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'corrigir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'push', // Push and pushes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'empurrar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'watch', // Watch and watches.
				'part_of_speech' => $part_of_speech,
				'translation' => 'ver', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the third-person of Verbs ending with '-y' preceded by vowel. Suffix: '-s'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_3rdperson_ending_with_y_preceded_by_vowel_in_glossary() {
		$test_string = 'Testing words play, plays.';
		$part_of_speech = 'verb';

		$matches = array(
			'reproduzir' => array( // Portuguese.
				'play',              // Infinitive.
				'plays',             // Third-person.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'play', // Play and plays.
				'part_of_speech' => $part_of_speech,
				'translation' => 'reproduzir', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the third-person of Verbs ending with '-o' and '-y' preceded by consonant. Suffix: '-es'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_3rdperson_ending_with_o_and_y_preceded_by_consonant_in_glossary() {
		$test_string = 'Testing words try, tries, go, goes, do, does.';
		$part_of_speech = 'verb';

		$matches = array(
			'tentar' => array( // Portuguese.
				'try',           // Infinitive.
				'tries',         // Third-person.
			),
			'ir' => array( // Portuguese.
				'go',        // Infinitive.
				'goes',      // Third-person.
			),
			'fazer' => array( // Portuguese.
				'do',           // Infinitive.
				'does',         // Third-person.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'try', // Try and tries.
				'part_of_speech' => $part_of_speech,
				'translation' => 'tentar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'go', // Go and goes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'ir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'do', // Do and does.
				'part_of_speech' => $part_of_speech,
				'translation' => 'fazer', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the third-person fallback suffix for most Verbs. Suffix: '-s'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_most_verbs_3rdperson_fallback_in_glossary() {
		$test_string = 'Testing words format, formats, make, makes, pull, pulls.';
		$part_of_speech = 'verb';

		$matches = array(
			'formatar' => array( // Portuguese.
				'format',          // Infinitive.
				'formats',         // Third-person.
			),
			'fazer' => array( // Portuguese.
				'make',         // Infinitive.
				'makes',        // Third-person.
			),
			'puxar' => array( // Portuguese.
				'pull',         // Infinitive.
				'pulls',        // Third-person.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'make', // Make and makes.
				'part_of_speech' => $part_of_speech,
				'translation' => 'fazer', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'format', // Format and formats.
				'part_of_speech' => $part_of_speech,
				'translation' => 'formatar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'pull', // Pull and pulls.
				'part_of_speech' => $part_of_speech,
				'translation' => 'puxar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs not ending with '-e'. Suffix '-ed'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_past_not_ending_with_e_in_glossary() {
		$test_string = 'Testing words fix, fixed, push, pushed.';
		$part_of_speech = 'verb';

		$matches = array(
			'corrigir' => array( // Portuguese.
				'fix',             // Infinitive.
				'fixed',           // Past.
			),
			'empurrar' => array( // Portuguese.
				'push',            // Infinitive.
				'pushed',          // Past.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'fix', // Fix and fixed.
				'part_of_speech' => $part_of_speech,
				'translation' => 'corrigir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'push', // Push and pushed.
				'part_of_speech' => $part_of_speech,
				'translation' => 'empurrar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs ending with '-e'. Suffix '-ed'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_past_ending_with_e_in_glossary() {
		$test_string = 'Testing words contribute, contributed, delete, deleted.';
		$part_of_speech = 'verb';

		$matches = array(
			'contribuir' => array( // Portuguese.
				'contribute',        // Infinitive.
				'contributed',       // Past.
			),
			'eliminar' => array( // Portuguese.
				'delete',          // Infinitive.
				'deleted',         // Past.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'contribute', // Contribute and contributed.
				'part_of_speech' => $part_of_speech,
				'translation' => 'contribuir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'delete', // Delete and deleted.
				'part_of_speech' => $part_of_speech,
				'translation' => 'eliminar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs ending with consonant preceded by vowel, independently of the stressed syllable. Suffix '-ed'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_past_ending_with_consonant_preceded_by_vowel_in_glossary() {
		$test_string = 'Testing words visit, visited, develop, developed.';
		$part_of_speech = 'verb';

		$matches = array(
			'visitar' => array( // Portuguese.
				'visit',          // Infinitive.
				'visited',        // Past.
			),
			'desenvolver' => array( // Portuguese.
				'develop',            // Infinitive.
				'developed',          // Past.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'visit', // Visit and visited.
				'part_of_speech' => $part_of_speech,
				'translation' => 'visitar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'develop', // Develop and developed.
				'part_of_speech' => $part_of_speech,
				'translation' => 'desenvolver', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: Repeat ending consonant and '-ed'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_past_ending_with_consonant_preceded_by_vowel_double_consonant_in_glossary() {
		$test_string = 'Testing words commit, committed, prefer, preferred, travel, travelled.';
		$part_of_speech = 'verb';

		$matches = array(
			'comprometer' => array( // Portuguese.
				'commit',             // Infinitive.
				'committed',          // Past.
			),
			'preferir' => array( // Portuguese.
				'prefer',          // Infinitive.
				'preferred',       // Past.
			),
			'viajar' => array( // Portuguese.
				'travel',        // Infinitive.
				'travelled',      // Past.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'commit', // Commit and committed.
				'part_of_speech' => $part_of_speech,
				'translation' => 'comprometer', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'prefer', // Prefer and preferred.
				'part_of_speech' => $part_of_speech,
				'translation' => 'preferir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'travel', // Travel and travelled.
				'part_of_speech' => $part_of_speech,
				'translation' => 'viajar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the present of Verbs not ending with 'e', or ending with 'ee', 'ye' or 'oe'. Suffix '-ing'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_present_not_ending_with_e_or_ending_with_ee_ye_oe_in_glossary() {
		$test_string = 'Testing words fix, fixing, push, pushing, agree, agreeing, see, seeing, dye, dyeing, tiptoe, tiptoeing.';
		$part_of_speech = 'verb';

		$matches = array(
			'corrigir' => array( // Portuguese.
				'fix',             // Infinitive.
				'fixing',          // Present.
			),
			'empurrar' => array( // Portuguese.
				'push',            // Infinitive.
				'pushing',         // Present.
			),
			'concordar' => array( // Portuguese.
				'agree',            // Infinitive.
				'agreeing',         // Present.
			),
			'ver' => array( // Portuguese.
				'see',        // Infinitive.
				'seeing',     // Present.
			),
			'tingir' => array( // Portuguese.
				'dye',           // Infinitive.
				'dyeing',        // Present.
			),
			'andar em pontas dos p\u00e9s' => array( // Portuguese.
				'tiptoe',                              // Infinitive.
				'tiptoeing',                           // Present.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'fix', // Contribute and contributing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'corrigir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'push', // Push and pushing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'empurrar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'agree', // Agree and agreeing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'concordar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'see', // See and seeing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'ver', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'dye', // Dye and dyeing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'tingir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'tiptoe', // Tiptoe and tiptoeing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'andar em pontas dos pés', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the present of Verbs ending with '-e'. Suffix '-ing'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_present_ending_with_e_in_glossary() {
		$test_string = 'Testing words contribute, contributing, delete, deleting, care, caring.';
		$part_of_speech = 'verb';

		$matches = array(
			'contribuir' => array( // Portuguese.
				'contribute',        // Infinitive.
				'contributing',      // Present.
			),
			'eliminar' => array( // Portuguese.
				'delete',          // Infinitive.
				'deleting',        // Present.
			),
			'cuidar' => array( // Portuguese.
				'care',          // Infinitive.
				'caring',        // Present.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'contribute', // Contribute and contributing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'contribuir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'delete', // Delete and deleting.
				'part_of_speech' => $part_of_speech,
				'translation' => 'eliminar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'care', // Care and caring.
				'part_of_speech' => $part_of_speech,
				'translation' => 'cuidar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs ending with consonant preceded by vowel, independently of the stressed syllable. Suffix '-ing'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_present_ending_with_consonant_preceded_by_vowel_in_glossary() {
		$test_string = 'Testing words visit, visiting, develop, developing.';
		$part_of_speech = 'verb';

		$matches = array(
			'visitar' => array( // Portuguese.
				'visit',          // Infinitive.
				'visiting',       // Present.
			),
			'desenvolver' => array( // Portuguese.
				'develop',            // Infinitive.
				'developing',         // Present.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'visit', // Visit and visiting.
				'part_of_speech' => $part_of_speech,
				'translation' => 'visitar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'develop', // Develop and developing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'desenvolver', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the past of Verbs ending with consonant preceded by vowel, independently of the stressed syllable. Suffix: Repeat ending consonant and '-ing'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_present_ending_with_consonant_preceded_by_vowel_double_consonant_in_glossary() {
		$test_string = 'Testing words commit, committing, prefer, preferring, travel, travelling.';
		$part_of_speech = 'verb';

		$matches = array(
			'comprometer' => array( // Portuguese.
				'commit',             // Infinitive.
				'committing',         // Present.
			),
			'preferir' => array( // Portuguese.
				'prefer',          // Infinitive.
				'preferring',      // Present.
			),
			'viajar' => array( // Portuguese.
				'travel',        // Infinitive.
				'travelling',    // Present.
			),
		);

		$glossary_entries = array(
			array(
				'term' => 'commit', // Commit and committing.
				'part_of_speech' => $part_of_speech,
				'translation' => 'comprometer', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'prefer', // Prefer and preferring.
				'part_of_speech' => $part_of_speech,
				'translation' => 'preferir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'travel', // Travel and travelling.
				'part_of_speech' => $part_of_speech,
				'translation' => 'viajar', // Portuguese.
				'glossary_id' => null,
			),
		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}


	/**
	 * Expects matching the Verbs that form Nouns ending with suffix '-tion'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_form_nouns_ending_with_tion_in_glossary() {
		$test_string = 'Testing words abbreviate, abbreviation, authorize, authorization, specify, specification, liquefy, liquefaction, exclaim, exclamation, encrypt, encryption, subscribe, subscription, perceive, perception, resume, resumption, correct, correction, delete, deletion, edit, edition, ignite, ignition, contribute, contribution, resolve, resolution, compose, composition, abstain, abstention, contravene, contravention, prevent, prevention, insert, insertion.';
		$part_of_speech = 'verb';

		$matches = array(
			'abreviar' => array( // Portuguese.
				'abbreviate',      // Verb.
				'abbreviation',    // Noun.
			),
			'autorizar' => array( // Portuguese.
				'authorize',        // Verb.
				'authorization',    // Noun.
			),
			'especificar' => array( // Portuguese.
				'specify',            // Verb.
				'specification',      // Noun.
			),
			'liquefazer' => array( // Portuguese.
				'liquefy',           // Verb.
				'liquefaction',      // Noun.
			),
			'exclamar' => array( // Portuguese.
				'exclaim',         // Verb.
				'exclamation',     // Noun.
			),
			'encriptar' => array( // Portuguese.
				'encrypt',          // Verb.
				'encryption',       // Noun.
			),
			'subscrever' => array( // Portuguese.
				'subscribe',         // Verb.
				'subscription',      // Noun.
			),
			'percepcionar' => array( // Portuguese.
				'perceive',            // Verb.
				'perception',          // Noun.
			),
			'resumir' => array( // Portuguese.
				'resume',         // Verb.
				'resumption',     // Noun.
			),
			'corrigir' => array( // Portuguese.
				'correct',         // Verb.
				'correction',      // Noun.
			),
			'eliminar' => array( // Portuguese.
				'delete',          // Verb.
				'deletion',        // Noun.
			),
			'editar' => array( // Portuguese.
				'edit',          // Verb.
				'edition',       // Noun.
			),
			'acender' => array( // Portuguese.
				'ignite',         // Verb.
				'ignition',       // Noun.
			),
			'contribuir' => array( // Portuguese.
				'contribute',        // Verb.
				'contribution',      // Noun.
			),
			'resolver' => array( // Portuguese.
				'resolve',         // Verb.
				'resolution',      // Noun.
			),
			'comp\u00f4r' => array( // Portuguese.
				'compose',            // Verb.
				'composition',        // Noun.
			),
			'abster' => array( // Portuguese.
				'abstain',       // Verb.
				'abstention',    // Noun.
			),
			'transgredir' => array( // Portuguese.
				'contravene',         // Verb.
				'contravention',      // Noun.
			),
			'prevenir' => array( // Portuguese.
				'prevent',         // Verb.
				'prevention',      // Noun.
			),
			'inserir' => array( // Portuguese.
				'insert',         // Verb.
				'insertion',      // Noun.
			),

		);

		$glossary_entries = array(
			array(
				'term' => 'abbreviate', // Abbreviate and abbreviation.
				'part_of_speech' => $part_of_speech,
				'translation' => 'abreviar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'authorize', // Authorize and authorization.
				'part_of_speech' => $part_of_speech,
				'translation' => 'autorizar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'specify', // Specify and specification.
				'part_of_speech' => $part_of_speech,
				'translation' => 'especificar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'liquefy', // Liquefy and liquefaction.
				'part_of_speech' => $part_of_speech,
				'translation' => 'liquefazer', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'exclaim', // Exclaim and exclamation.
				'part_of_speech' => $part_of_speech,
				'translation' => 'exclamar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'encrypt', // Encrypt and encryption.
				'part_of_speech' => $part_of_speech,
				'translation' => 'encriptar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'subscribe', // Subscribe and subscription.
				'part_of_speech' => $part_of_speech,
				'translation' => 'subscrever', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'perceive', // Perceive and perception.
				'part_of_speech' => $part_of_speech,
				'translation' => 'percepcionar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'resume', // Resume and resumption.
				'part_of_speech' => $part_of_speech,
				'translation' => 'resumir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'correct', // Correct and correction.
				'part_of_speech' => $part_of_speech,
				'translation' => 'corrigir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'delete', // Delete and deletion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'eliminar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'edit', // Edit and edition.
				'part_of_speech' => $part_of_speech,
				'translation' => 'editar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'ignite', // Ignite and ignition.
				'part_of_speech' => $part_of_speech,
				'translation' => 'acender', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'contribute', // Contribute and contribution.
				'part_of_speech' => $part_of_speech,
				'translation' => 'contribuir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'resolve', // Resolve and resolution.
				'part_of_speech' => $part_of_speech,
				'translation' => 'resolver', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'compose', // Compose and composition.
				'part_of_speech' => $part_of_speech,
				'translation' => 'compôr', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'abstain', // Abstain and abstention.
				'part_of_speech' => $part_of_speech,
				'translation' => 'abster', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'contravene', // Contravene and contravention.
				'part_of_speech' => $part_of_speech,
				'translation' => 'transgredir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'prevent', // Prevent and prevention.
				'part_of_speech' => $part_of_speech,
				'translation' => 'prevenir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'insert', // Insert and insertion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'inserir', // Portuguese.
				'glossary_id' => null,
			),

		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Expects matching the Verbs that form Nouns ending with suffix '-sion'.
	 */
	function test_map_glossary_entries_to_translation_originals_with_verbs_form_nouns_ending_with_sion_in_glossary() {
		$test_string = 'Testing words invade, invasion, precede, precession, decide, decision, explode, explosion, exclude, exclusion, supervise, supervision, confuse, confusion, expel, expulsion, submit, submission, compress, compression, extend, extension, convert, conversion, disperse, dispersion, recur, recursion, emerge, emersion.';
		$part_of_speech = 'verb';

		$matches = array(
			'invadir' => array( // Portuguese.
				'invade',         // Verb.
				'invasion',       // Noun.
			),
			'preceder' => array( // Portuguese.
				'precede',         // Verb.
				'precession',      // Noun.
			),
			'decidir' => array( // Portuguese.
				'decide',         // Verb.
				'decision',       // Noun.
			),
			'explodir' => array( // Portuguese.
				'explode',         // Verb.
				'explosion',       // Noun.
			),
			'excluir' => array( // Portuguese.
				'exclude',        // Verb.
				'exclusion',      // Noun.
			),
			'supervisionar' => array( // Portuguese.
				'supervise',            // Verb.
				'supervision',          // Noun.
			),
			'confundir' => array( // Portuguese.
				'confuse',          // Verb.
				'confusion',        // Noun.
			),
			'expulsar' => array( // Portuguese.
				'expel',           // Verb.
				'expulsion',       // Noun.
			),
			'submeter' => array( // Portuguese.
				'submit',          // Verb.
				'submission',      // Noun.
			),
			'comprimir' => array( // Portuguese.
				'compress',         // Verb.
				'compression',      // Noun.
			),
			'estender' => array( // Portuguese.
				'extend',          // Verb.
				'extension',       // Noun.
			),
			'converter' => array( // Portuguese.
				'convert',          // Verb.
				'conversion',       // Noun.
			),
			'dispersar' => array( // Portuguese.
				'disperse',         // Verb.
				'dispersion',       // Noun.
			),
			'repetir' => array( // Portuguese.
				'recur',          // Verb.
				'recursion',      // Noun.
			),
			'emergir' => array( // Portuguese.
				'emerge',         // Verb.
				'emersion',       // Noun.
			),

		);

		$glossary_entries = array(
			array(
				'term' => 'invade', // Invade and invasion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'invadir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'precede', // Precede and precession.
				'part_of_speech' => $part_of_speech,
				'translation' => 'preceder', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'decide', // Decide and decision.
				'part_of_speech' => $part_of_speech,
				'translation' => 'decidir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'explode', // Explode and explosion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'explodir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'exclude', // Exclude and exclusion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'excluir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'supervise', // Supervise and supervision.
				'part_of_speech' => $part_of_speech,
				'translation' => 'supervisionar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'confuse', // Confuse and confusion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'confundir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'expel', // Expel and expulsion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'expulsar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'submit', // Submit and submission.
				'part_of_speech' => $part_of_speech,
				'translation' => 'submeter', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'compress', // Compress and compression.
				'part_of_speech' => $part_of_speech,
				'translation' => 'comprimir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'extend', // Extend and extension.
				'part_of_speech' => $part_of_speech,
				'translation' => 'estender', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'convert', // Convert and conversion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'converter', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'disperse', // Disperse and dispersion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'dispersar', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'recur', // Recur and recursion.
				'part_of_speech' => $part_of_speech,
				'translation' => 'repetir', // Portuguese.
				'glossary_id' => null,
			),
			array(
				'term' => 'emerge', // Emerge and emersion
				'part_of_speech' => $part_of_speech,
				'translation' => 'emergir', // Portuguese.
				'glossary_id' => null,
			),

		);

		$this->check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries );
	}

	/**
	 * Method to test the map_glossary_entries_to_translation_originals() function.
	 *
	 * @param string $test_string      The string to test.
	 * @param string $part_of_speech   The part of speech of the string to test.
	 * @param array  $matches          The matches to expect.
	 * @param array  $glossary_entries The glossary entries to create.
	 *
	 * @return void
	 */
	function check_map_glossary( $test_string, $part_of_speech, $matches, $glossary_entries ) {
		$expected_result = array();
		foreach ( $matches as $glossary_entry => $originals ) {
			foreach ( $originals as $original ) {
				$expected_result[] = '<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;' . $glossary_entry . '&quot;,&quot;pos&quot;:&quot;' . $part_of_speech . '&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">' . $original . '</span>';
			}
		}

		$expected_result = sprintf(
			'Testing words %s.',
			implode( ', ', $expected_result )
		);

		$entry = new Translation_Entry( array( 'singular' => $test_string, ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		foreach ( $glossary_entries as $glossary_entry ) {
			$glossary_entry['glossary_id'] = $glossary->id;
			GP::$glossary_entry->create_and_select( $glossary_entry );
		}

		$orig = map_glossary_entries_to_translation_originals( $entry, $glossary );

		$this->assertEquals( $orig->singular_glossary_markup, $expected_result );
	}

}
