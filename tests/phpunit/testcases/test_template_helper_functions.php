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
				'part_of_speech' => 'noum',
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
				'part_of_speech' => 'noum',
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

	/**
	 * Method to test the test_map_glossary_entries_to_translation_originals_with_entries_bounded_by_placeholders() function.
	 *
	 * @param string $glossary_entry   The string to test.
	 * @param string $part_of_speech   The part of speech of the string to test.
	 * @param string $original         The matches to expect.
	 * @return string                  The formated glossary match output.
	 */
	function glossary_match( $glossary_entry, $part_of_speech, $original ) {
		return '<span class="glossary-word" data-translations="[{&quot;translation&quot;:&quot;' . $glossary_entry . '&quot;,&quot;pos&quot;:&quot;' . $part_of_speech . '&quot;,&quot;comment&quot;:null,&quot;locale_entry&quot;:&quot;&quot;}]">' . $original . '</span>';
	}

	/**
	 * Method to test_prepare_original() and the map_glossary_entries_to_translation_originals() functions.
	 *
	 * @param string $spaces  The spaces to highlight.
	 * @return string         The spaces highlighted.
	 */
	function highlight_invisible_spaces( $spaces ) {
		return '<span class="invisible-spaces">' . $spaces . '</span>';
	}

	/**
	 * Method to test_prepare_original() and the map_glossary_entries_to_translation_originals() functions.
	 *
	 * @return string  The tab highlighted.
	 */
	function highlight_tab() {
		return "<span class='invisibles' title='Tab character'>&rarr;</span>\t";
	}

	/**
	 * Method to test_prepare_original() and the map_glossary_entries_to_translation_originals() functions.
	 *
	 * @return string  The line break highlighted.
	 */
	function highlight_line_break() {
		return "<span class='invisibles' title='New line'>&crarr;</span>\n";
	}

}
