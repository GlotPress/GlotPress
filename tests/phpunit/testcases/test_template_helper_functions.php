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
		$expected_result = 'Please set your favorite ' . $this->glossary_match( 'paleta de cores', 'noun', 'color scheme' ) . '.';

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
		$expected_result = 'Please set your favorite ' . $this->glossary_match( 'paleta de cores', 'noun', 'color-scheme' ) . '.';

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
		$expected_result = 'Prowdly built by your ' . $this->glossary_match( 'Equipa-WP do GlotPress', 'noun', 'GlotPress WP-Team' ) . '.';

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
		$expected_result = 'Please set your ' . $this->glossary_match( 'paleta de cores do administrador', 'noun', 'admin color scheme' ) . '.';

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
		$expected_result = 'This is a ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test &lt;strong class="strong-class another-class strong" alt="A ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' alt" style="some-property:strong;"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/dd&gt;, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test with&lt;img src="strong.img" title="' . $this->glossary_match( 'forte', 'noun', 'Strong' ) . ' text. Very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" class="a-very-strong-really-Strong-class" alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" style="another-property:strong-very-strong;" /&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images.&lt;hr/ alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" class="Strong class StRoNg" title="' . $this->glossary_match( 'forte', 'noun', 'StRoNg' ) . ' very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" src="file.strong"&gt;. The final ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text.';

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
		$singular_expected_result = 'This is a ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test &lt;strong class="strong-class another-class strong" alt="A ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' alt" style="some-property:strong;"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/dd&gt;, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test with&lt;img src="strong.img" title="' . $this->glossary_match( 'forte', 'noun', 'Strong' ) . ' text. Very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" class="a-very-strong-really-Strong-class" alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" style="another-property:strong-very-strong;" /&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images.&lt;hr/ alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" class="Strong class StRoNg" title="' . $this->glossary_match( 'forte', 'noun', 'StRoNg' ) . ' very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" src="file.strong"&gt;. The final ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text.';
		$plural_expected_result   = 'Plural. This is a ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test &lt;strong class="strong-class another-class strong" alt="A ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' alt" style="some-property:strong;"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/strong&gt;. This is another&lt;dd style="a-property:strong;" class="strong strong-class another-class"&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '&lt;/dd&gt;, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' test with&lt;img src="strong.img" title="' . $this->glossary_match( 'forte', 'noun', 'Strong' ) . ' text. Very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" class="a-very-strong-really-Strong-class" alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text" style="another-property:strong-very-strong;" /&gt;' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images, very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' images.&lt;hr/ alt="Alt ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" class="Strong class StRoNg" title="' . $this->glossary_match( 'forte', 'noun', 'StRoNg' ) . ' very ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . '" src="file.strong"&gt;. The final ' . $this->glossary_match( 'forte', 'noun', 'strong' ) . ' text.';

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
	 * Data provider.
	 *
	 * @var array
	 */
	function provide_test_map_glossary_entries_with_placeholders_glued_glossary_words() {
		return array(
			array(
				'test_string'     => 'I %sshow%d.',
				'expected_result' => 'I %s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%d.',
			),
			array(
				'test_string'     => 'I %s show%d.',
				'expected_result' => 'I %s ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%d.',
			),
			array(
				'test_string'     => 'I %sshow %d.',
				'expected_result' => 'I %s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' %d.',
			),
			array(
				'test_string'     => 'I %1$sshow %d.',
				'expected_result' => 'I %1$s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' %d.',
			),
			array(
				'test_string'     => 'I %s show %d.',
				'expected_result' => 'I %s ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' %d.',
			),
			array(
				'test_string'     => 'I %%show want to %sshow and show%s, reshow and show and test %3$show%4$show to %2$dshow%2$b test %show%d %sshow%d %3$sshow%4$s and%3$s%3$s test and show and %3$s show how show %4$s %%4%show %%show how.',
				'expected_result' => 'I %%' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' want to %s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' and ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%s, reshow and ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' and test %3$show%4$show to %2$d' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%2$b test %show%d %s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%d %3$s' . $this->glossary_match( 'amosar', 'verb', 'show' ) . '%4$s and%3$s%3$s test and ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' and %3$s ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' how ' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' %4$s %%4%show %%' . $this->glossary_match( 'amosar', 'verb', 'show' ) . ' how.',
			),
		);
	}

	/**
	 * Expects matching glossary terms glued to placeholders.
	 *
	 * @dataProvider provide_test_map_glossary_entries_with_placeholders_glued_glossary_words
	 */
	function test_map_glossary_entries_with_placeholders_glued_glossary_words( $test_string, $expected_result ) {
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

	/**
	 * Expects matching glossary terms glued to placeholders, both in singular and plural.
	 *
	 * @dataProvider provide_test_map_glossary_entries_with_placeholders_glued_glossary_words
	 */
	function test_map_glossary_entries_with_placeholders_glued_glossary_words_in_the_plural_origin( $test_string, $expected_result ) {
		$singular_string = $test_string;
		$plural_string   = 'Plural. ' . $test_string;
		$singular_expected_result = $expected_result;
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
		$singular_expected_result = 'My alidads and granddaddies and ' . $this->glossary_match( 'pai', 'noun', 'dad' ) . ' and ' . $this->glossary_match( 'pai', 'noun', 'dads' ) . ' and skedaddle and hispanidad and dadaistic';
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
		$singular_expected_result = $this->glossary_match( 'converter', 'verb', 'Converting' ) . ', ' . $this->glossary_match( 'converter', 'verb', 'converts' ) . ', ' . $this->glossary_match( 'converter', 'verb', 'converted' ) . ' and ' . $this->glossary_match( 'converter', 'verb', 'convert' ) . '.';
		$plural_expected_result   = 'Plural. ' . $singular_expected_result;

		$entry = new Translation_Entry( array( 'singular' => $singular_string, 'plural' => $plural_string ) );

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );

		$glossary_entries = array(
			array(
				'term' => 'convert',
				'part_of_speech' => 'verb',
				'translation' => 'converter',
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
		$singular_expected_result = $this->glossary_match( 'retraso', 'noun', 'Delay' ) . ' and ' . $this->glossary_match( 'retraso', 'noun', 'delays' ) . ', ' . $this->glossary_match( 'chave', 'noun', 'key' ) . ' and ' . $this->glossary_match( 'chave', 'noun', 'keys' ) . ', ' . $this->glossary_match( 'xoguete', 'noun', 'toy' ) . ' and ' . $this->glossary_match( 'xoguete', 'noun', 'toys' ) . ', ' . $this->glossary_match( 'rapaz', 'noun', 'guy' ) . ' and ' . $this->glossary_match( 'rapaz', 'noun', 'guys' ) . ', %see%s %1$guys%2$s %s' . $this->glossary_match( 'ver', 'verb', 'see' ) . '%s %1$g' . $this->glossary_match( 'rapaz', 'noun', 'guys' ) . '%2$s, ' . $this->glossary_match( 'converter', 'verb', 'converting' ) . ' and ' . $this->glossary_match( 'converter', 'verb', 'convert' ) . '.';
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
		$expected_result = $this->highlight_invisible_spaces( '  ' ) . 'Two spaces at the begining, double' . $this->highlight_invisible_spaces( '  ' ) . 'and triple' . $this->highlight_invisible_spaces( '   ' ) . 'spaces in the middle, and one space in the end.' . $this->highlight_invisible_spaces( ' ' );

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

	/**
	 * Expects highlighting leading and ending spaces in multi line strings, and double/multiple spaces in the middle.
	 */
	function test_prepare_original_with_leading_and_trailing_spaces_and_multiple_spaces_in_middle_of_multi_line_strings() {
		$test_string     = "  Two spaces at the begining and end, and in the line below:  \n\n One space at the begining and end \n\nNo spaces\n One space at the begining\nOne space at the end \n\n\nMultiple spaces  in   multiline  \n One space at the begining and end ";
		$expected_result = $this->highlight_invisible_spaces( '  ' ) . 'Two spaces at the begining and end, and in the line below:' . $this->highlight_invisible_spaces( '  ' ) . $this->highlight_line_break() . $this->highlight_line_break() . $this->highlight_invisible_spaces( ' ' ) . 'One space at the begining and end' . $this->highlight_invisible_spaces( ' ' ) . $this->highlight_line_break() . $this->highlight_line_break() . 'No spaces' . $this->highlight_line_break() . $this->highlight_invisible_spaces( ' ' ) . 'One space at the begining' . $this->highlight_line_break() . 'One space at the end' . $this->highlight_invisible_spaces( ' ' ) . $this->highlight_line_break() . $this->highlight_line_break() . $this->highlight_line_break() . 'Multiple spaces' . $this->highlight_invisible_spaces( '  ' ) . 'in' . $this->highlight_invisible_spaces( '   ' ) . 'multiline' . $this->highlight_invisible_spaces( '  ' ) . $this->highlight_line_break() . $this->highlight_invisible_spaces( ' ' ) . 'One space at the begining and end' . $this->highlight_invisible_spaces( ' ' );

		$orig = prepare_original( $test_string );

		$this->assertEquals( $orig, $expected_result );
	}

	/**
	 * Expects highlighting line breaks and tabs.
	 */
	function test_prepare_original_with_line_breaks_and_tabs() {
		$test_string     = "This string has 2x tabs\t\tand a line\nbreak.";
		$expected_result = "This string has 2x tabs" . $this->highlight_tab() . $this->highlight_tab() . "and a line" . $this->highlight_line_break() . "break.";

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
	 * @param string $glossary_entry   The translation of the glossary entry.
	 * @param string $part_of_speech   The part of speech of the glossary entry.
	 * @param string $original         The original matched string.
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
