<?php

class GP_Test_Builtin_Translation_Warnings extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->w              = new GP_Builtin_Translation_Warnings();
		$this->l              = $this->factory->locale->create();
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}

	function _assertWarning( $assert, $warning, $original, $translation, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "warning_$warning";
		$this->$assert( true, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertHasWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertNotSame', $warning, $original, $translation, $locale );
	}

	function assertNoWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertSame', $warning, $original, $translation, $locale );
	}

	function assertHasWarningsAndContainsOutput( $warning, $original, $translation, $output_expected, $locale = null ) {
		$this->assertHasWarnings( $warning, $original, $translation, $locale );
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "warning_$warning";
		$this->assertStringContainsString( $output_expected, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertContainsOutput( $singular, $plural, $translations, $output_expected, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$this->assertEquals( $output_expected, $this->tw->check( $singular, $plural, $translations, $locale ) );
	}

	function test_length() {
		$this->assertNoWarnings( 'length', $this->longer_than_20, $this->longer_than_20 );
		$this->assertNoWarnings( 'length', 'number_format_', '' );
		$this->assertHasWarningsAndContainsOutput(
			'length',
			$this->longer_than_20,
			$this->shorter_than_5,
			'Lengths of source and translation differ too much.'
		);
	}

	function test_length_exclude() {
		$w_without_locale                           = new GP_Builtin_Translation_Warnings();
		$w_without_locale->length_exclude_languages = array( $this->l->slug );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->longer_than_20, $this->l ) );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->shorter_than_5, $this->l ) );
	}

	function test_tags() {
		$this->assertNoWarnings( 'tags', 'Baba', 'Баба' );
		$this->assertNoWarnings(
			'tags',
			'<p><abbr title="World Health Organization">WHO</abbr> was founded in 1948.</p>',
			'<p>La<abbr title="Organización Mundial de la Salud">OMS</abbr> se fundó en 1948.</p>'
		);
		$this->assertNoWarnings(
			'tags',
			'<button aria-label="Close">X</button><button aria-label="Open">O</button>',
			'<button aria-label="Cerrar">X</button><button aria-label="Abrir">A</button>'
		);
		$this->assertNoWarnings( 'tags', '<a href="%s">Baba</a>', '<a href="%s">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" title="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" aria-label="Blimp!">Baba</a>', '<a href="%s" aria-label="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!" aria-label="Blimp!">Baba</a>', '<a href="%s" title="Блимп!" aria-label="Блимп!">Баба</a>' );
		$this->assertNoWarnings(
			'tags',
			'<a href="https://www.example.org" title="Example!" lang="en">Example URL</a>',
			'<a href="https://www.example.org" title="¡Ejemplo!" lang="es">URL de jemplo</a>'
		);
		$this->l->slug = 'ja';
		$this->assertNoWarnings(
			'tags',
			'<b>Text 1</b>, <i>Italic text</i>, Text 2, <em>Emphasized text</em>, Text 3',
			'<b>テキスト1</b>、イタリック体、テキスト2、エンファシス体、テキスト3',
			$this->l
		);
		$this->assertNoWarnings( 'tags', '</a>Incorrect link</a>', '<a>Incorrect link</a>' );
		$this->assertNoWarnings(
			'tags',
			' Text 1 <a href="https://wordpress.org/plugins/example-plugin/">Example plugin</a> Text 2<a href="https://wordpress.com/log-in/">Log in</a> Text 3 <img src="example.jpg" alt="Example alt text">',
			' Texto 1 <a href="https://es.wordpress.org/plugins/example-plugin/">Plugin de ejemplo</a> Texto 2<a href="https://es.wordpress.com/log-in/">Acceder</a> Texto 3 <img src="example.jpg" alt="Texto alternativo de ejemplo">'
		);
		$this->assertNoWarnings(
			'tags',
			'<img src="https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress in the Wikipedia">',
			'<img src="https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress en la Wikipedia">'
		);
		$this->assertNoWarnings(
			'tags',
			' Text 1 <a href="https://wordpress.com/log-in">Log in</a> Text 2 <img src="https://en.gravatar.com/matt" alt="Matt\'s Gravatar"> ',
			' Texto 1 <a href="https://es.wordpress.com/log-in">Acceder</a> Texto 2 <img src="https://es.gravatar.com/matt" alt="Gravatar de Matt"> '
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<p>Paragraph</p>',
			'<p>Párrafo',
			'Missing tags from translation. Expected: </p>'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'Paragraph</p>',
			'<p>Párrafo</p>',
			'Too many tags in translation. Found: <p>'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<h1>Title</h1><p>Text 1</p><br><b>Text 2</b>',
			'<h1>Título</h1><p>Texto 1<br><b>Texto 2</b>',
			'Missing tags from translation. Expected: </p>'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<h1>Title</h1>Text 1</p><br><b>Text 2</b>',
			'<h1>Título</h1><p>Texto 1</p><br><b>Texto 2</b>',
			'Too many tags in translation. Found: <p>'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<a href="%s" title="Blimp!">Baba</a>',
			'<a href="javascript:%s" title="Блимп!">Баба</a>',
			'The translation contains the following unexpected links: javascript:%s'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<a href="javascript:%s" title="Blimp!">Baba</a>',
			'<a href="%s" title="Блимп!">Баба</a>',
			'The translation appears to be missing the following links: javascript:%s'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<a href="https://www.example.org" title="Example!">Example URL</a>',
			'<a href="https://www.example.com" title="¡Ejemplo!">Ejemplo</a>',
			"The translation appears to be missing the following URLs: https://www.example.org\nThe translation contains the following unexpected URLs: https://www.example.com"
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<a href="%s" title="Blimp!">Baba</a>',
			'<a href="%s" x>Баба</a>',
			'Expected <a href="%s" title="Blimp!">, got <a href="%s" x>.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<p>Baba</p>',
			'</p>Баба<p>',
			'The translation contains incorrect HTML tags: Unexpected end tag : p'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<h1>Hello</h1><h2>Peter</h2>',
			'<h1>Hola</h1></h2>Pedro<h2>',
			'The translation contains incorrect HTML tags: Unexpected end tag : h2'
		);
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<img src="https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress en la Wikipedia">',
			'<img src="https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress in the Wikipedia">',
			"The translation appears to be missing the following URLs: https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg\nThe translation contains the following unexpected URLs: https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg"
		);
		$this->l->slug = 'ja';
		$this->assertHasWarningsAndContainsOutput(
			'tags',
			'<b>Text 1</b>, <i>Italic text</i>, Text 2, <em>Emphasized text</em>, Text 3',
			'</b>テキスト1<b>、イタリック体、テキスト2、エンファシス体、テキスト3',
			'The translation contains incorrect HTML tags: Unexpected end tag : b',
			$this->l
		);
	}

	function test_add_all() {
		$warnings = $this->getMockBuilder( 'GP_Translation_Warnings' )->getMock();
		// we check for the number of warnings, because PHPUnit doesn't allow
		// us to check if each argument is a callable
		$warnings->expects( $this->exactly( 10 ) )->method( 'add' )->will( $this->returnValue( true ) );
		$this->w->add_all( $warnings );
	}

	function test_placeholders() {
		$this->assertNoWarnings( 'placeholders', '%s baba', '%s баба' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba %2$s dyado', '%1$sбабадядо%2$s' );
		$this->assertNoWarnings( 'placeholders', '% baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '% baba', '% баба' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba', '%1$s баба' );
		$this->assertNoWarnings( 'placeholders', '%sHome%s', '%sНачало%s' );
		$this->assertNoWarnings( 'placeholders', 'This string has %stwo variables%s.', 'Deze string heeft %stwee variabelen%s.' );
		$this->assertNoWarnings( 'placeholders', '%% baba', '%% баба' );
		$this->assertNoWarnings( 'placeholders', '%s%% baba', '%s%% баба' );

		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%s baba',
			'баба',
			'Missing %s placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%s baba',
			'% баба',
			'Missing %s placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%1$s baba',
			'баба',
			'Missing %1$s placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%% baba',
			'% баба',
			'Missing %% placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%s baba',
			'%%s баба',
			'Missing %s placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'%1$s baba',
			'%%1$s баба',
			'Missing %1$s placeholder in translation.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'placeholders',
			'баба',
			'%s baba',
			'Extra %s placeholder in translation.'
		);
	}

	function test_should_begin_end_on_newline() {
		$this->assertHasWarningsAndContainsOutput(
			'should_begin_on_newline',
			"\nbaba",
			'baba',
			'Original and translation should both begin on newline.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'should_not_begin_on_newline',
			'baba',
			"\nbaba",
			'Translation should not begin on newline.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'should_end_on_newline',
			"baba\n",
			'baba',
			'Original and translation should both end on newline.'
		);
		$this->assertHasWarningsAndContainsOutput(
			'should_not_end_on_newline',
			'baba',
			"baba\n",
			'Translation should not end on newline.'
		);

		$this->assertNoWarnings( 'should_begin_on_newline', 'baba', 'baba' );
		$this->assertNoWarnings( 'should_not_begin_on_newline', 'baba', 'baba' );
		$this->assertNoWarnings( 'should_end_on_newline', 'baba', 'baba' );
		$this->assertNoWarnings( 'should_not_end_on_newline', 'baba', 'baba' );

		$this->assertNoWarnings( 'should_begin_on_newline', "baba\n", "baba\n" );
		$this->assertNoWarnings( 'should_not_begin_on_newline', "baba\n", "baba\n" );
		$this->assertNoWarnings( 'should_end_on_newline', "baba\n", "baba\n" );
		$this->assertNoWarnings( 'should_not_end_on_newline', "baba\n", "baba\n" );

		$this->assertNoWarnings( 'should_begin_on_newline', "\nbaba", "\nbaba" );
		$this->assertNoWarnings( 'should_not_begin_on_newline', "\nbaba", "\nbaba" );
		$this->assertNoWarnings( 'should_end_on_newline', "\nbaba", "\nbaba" );
		$this->assertNoWarnings( 'should_not_end_on_newline', "\nbaba", "\nbaba" );
	}

	function test_placeholders_using_check() {
		$w       = new GP_Translation_Warnings();
		$builtin = new GP_Builtin_Translation_Warnings();
		$w->add( 'placeholder', array( $builtin, 'warning_placeholders' ) );

		$fr = new GP_Locale(
			array(
				'nplurals'          => 2,
				'plural_expression' => 'n > 1',
			)
		);
		$this->assertEquals(
			null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s' ), $fr )
		);
		$this->assertEquals(
			null,
			$w->check( 'original %1$s', 'original %2$s', array( null ), $fr )
		);
		$this->assertEquals(
			null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation %s' ), $fr )
		);
		$this->assertEquals(
			array( 1 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation' ), $fr )
		);
		$this->assertEquals(
			array( 0 => array( 'placeholder' => 'Missing %1$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation', 'translation  %2$s' ), $fr )
		);

		$de = new GP_Locale(
			array(
				'nplurals'          => 2,
				'plural_expression' => 'n != 1',
			)
		);
		$this->assertEquals(
			null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s' ), $de )
		);
		$this->assertEquals(
			null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation %s' ), $de )
		);

		$ja = new GP_Locale(
			array(
				'nplurals'          => 1,
				'plural_expression' => '0',
			)
		);

		$this->assertEquals(
			null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %2$s' ), $ja )
		);
		$this->assertEquals(
			null,
			$w->check( 'original', 'original %s', array( 'translation %s' ), $ja )
		);
		$this->assertEquals(
			array( 0 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation' ), $ja )
		);

		$ru = new GP_Locale(
			array(
				'nplurals'          => 3,
				'plural_expression' => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)',
			)
		);

		$this->assertEquals(
			null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s', 'translation 2 %2$s' ), $ru )
		);
		$this->assertEquals(
			null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2 %s', 'translation 3 %s' ), $ru )
		);
		$this->assertEquals(
			array( 1 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation 2', 'translation 3 %2$s' ), $ru )
		);
		$this->assertEquals(
			array( 2 => array( 'placeholder' => 'Missing %s placeholder in translation.' ) ),
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2 %s', 'translation 3' ), $ru )
		);
		$this->assertEquals(
			array(
				1 => array( 'placeholder' => 'Missing %s placeholder in translation.' ),
				2 => array( 'placeholder' => 'Missing %s placeholder in translation.' ),
			),
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2', 'translation 3' ), $ru )
		);
	}

	function test_mismatching_urls() {
		$this->assertNoWarnings( 'mismatching_urls', 'https://www.example', 'https://www.example' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://www.example', 'http://www.example' );
		$this->assertNoWarnings( 'mismatching_urls', '//www.example', '//www.example' );
		$this->assertNoWarnings( 'mismatching_urls', '"//www.example"', '"//www.example.com"' );
		$this->assertNoWarnings( 'mismatching_urls', "'//www.example'", "'//www.example.com'" );
		$this->assertNoWarnings( 'mismatching_urls', '// www.example', '// www.example.comte	' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://127.0.0.1', 'https://127.0.0.1' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://127.0.0.1', 'http://127.0.0.1' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://www.example.com', 'https://www.example.com/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://www.example.com/', 'https://www.example.com' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://www.example.com', 'https://www.example.com/' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://www.example.com/', 'https://www.example.com' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://wordpress.org/plugins/example-plugin/', 'https://wordpress.org/plugins/example-plugin' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.org/plugins/example-plugin', 'http://wordpress.org/plugins/example-plugin/' );
		$this->assertNoWarnings( 'mismatching_urls', 'http://www.example.com/wp-content/uploads/2020/12/logo.png', 'https://www.example.com/wp-content/uploads/2020/12/logo.png' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', 'Texto1 https://www.example.com Texto2 https://www.example.org Texto3' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', ' Texto3 https://www.example.org Texto2 https://www.example.com Texto1  ' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', '  https://www.example.org Texto1   Texto3   https://www.example.com  Texto2  ' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', '  https://www.example.org https://www.example.com ' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.org/plugins/example-plugin/', 'https://es.wordpress.org/plugins/example-plugin/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.com/log-in/', 'https://es.wordpress.com/log-in/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.gravatar.com/matt', 'https://es.gravatar.com/matt' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.wikipedia.org/wiki/WordPress', 'https://es.wikipedia.org/wiki/WordPress' );

		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'HTTPS://WWW.EXAMPLE',
			'https://www.example',
			"The translation appears to be missing the following URLs: HTTPS://WWW.EXAMPLE\nThe translation contains the following unexpected URLs: https://www.example"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.example',
			'HTTPS://WWW.EXAMPLE',
			"The translation appears to be missing the following URLs: https://www.example\nThe translation contains the following unexpected URLs: HTTPS://WWW.EXAMPLE"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'HtTpS://WwW.eXaMpLe',
			'https://www.example',
			"The translation appears to be missing the following URLs: HtTpS://WwW.eXaMpLe\nThe translation contains the following unexpected URLs: https://www.example"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.example.com',
			'https://www.example.org',
			"The translation appears to be missing the following URLs: https://www.example.com\nThe translation contains the following unexpected URLs: https://www.example.org"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'//www.example.com',
			'http://www.example.org',
			"The translation appears to be missing the following URLs: //www.example.com\nThe translation contains the following unexpected URLs: http://www.example.org"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'//www.example.com',
			'https://www.example.org',
			"The translation appears to be missing the following URLs: //www.example.com\nThe translation contains the following unexpected URLs: https://www.example.org"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'http://www.example.com',
			'//www.example.org',
			"The translation appears to be missing the following URLs: http://www.example.com\nThe translation contains the following unexpected URLs: //www.example.org"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.example.com',
			'//www.example.org',
			"The translation appears to be missing the following URLs: https://www.example.com\nThe translation contains the following unexpected URLs: //www.example.org"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.exañple.com',
			'https://www.example.com',
			"The translation appears to be missing the following URLs: https://www.exañple.com\nThe translation contains the following unexpected URLs: https://www.example.com"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.example.com',
			'https://www.exañple.com',
			"The translation appears to be missing the following URLs: https://www.example.com\nThe translation contains the following unexpected URLs: https://www.exañple.com"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.wordpress.org/plugins/example-plugin/',
			'https://es.wordpress.org/plugins/example-plugin/',
			"The translation appears to be missing the following URLs: https://www.wordpress.org/plugins/example-plugin/\nThe translation contains the following unexpected URLs: https://es.wordpress.org/plugins/example-plugin/"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://www.wordpress.com/log-in/',
			'https://es.wordpress.com/log-in/',
			"The translation appears to be missing the following URLs: https://www.wordpress.com/log-in/\nThe translation contains the following unexpected URLs: https://es.wordpress.com/log-in/"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://es.gravatar.com/matt',
			'https://en.gravatar.com/matt',
			"The translation appears to be missing the following URLs: https://es.gravatar.com/matt\nThe translation contains the following unexpected URLs: https://en.gravatar.com/matt"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'https://es.wikipedia.org/wiki/WordPress',
			'https://en.wikipedia.org/wiki/WordPress',
			"The translation appears to be missing the following URLs: https://es.wikipedia.org/wiki/WordPress\nThe translation contains the following unexpected URLs: https://en.wikipedia.org/wiki/WordPress"
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'Text1 https://www.example.com Text2',
			'Texto1 Texto2',
			'The translation appears to be missing the following URLs: https://www.example.com'
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'Text1 Text2',
			'Texto1 https://www.example.com Texto2',
			'The translation contains the following unexpected URLs: https://www.example.com'
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'Text1 https://www.example.com Text2 https://www.example.org',
			'Texto1 https://www.example.com Texto2',
			'The translation appears to be missing the following URLs: https://www.example.org'
		);
		$this->assertHasWarningsAndContainsOutput(
			'mismatching_urls',
			'Text1 https://www.example.com Text2',
			'Texto1 https://www.example.com Texto2 https://www.example.org',
			'The translation contains the following unexpected URLs: https://www.example.org'
		);
	}


	function test_unexpected_sprintf_token() {
		$this->assertNoWarnings( 'unexpected_sprintf_token', '100 percent', '100%' );
		$this->assertNoWarnings( 'unexpected_sprintf_token', '<a href="%a">100 percent</a>', '<a href="%a">100%</a>' );
		$this->assertNoWarnings( 'unexpected_sprintf_token', '<a href="%s">100 percent</a>', '<a href="%s">100%%</a>' );
		$this->assertNoWarnings( 'unexpected_sprintf_token', '<a href="%1$s">100 percent</a>', '<a href="%1$s">100%%</a>' );
		$this->assertNoWarnings(
			'unexpected_sprintf_token',
			'The %s contains %d items',
			'El %s contiene %d elementos'
		);
		$this->assertNoWarnings(
			'unexpected_sprintf_token',
			'The %2$s contains %1$d items. That\'s a nice %2$s full of %1$d items.',
			'El %2$s contiene %1$d elementos. Es un bonito %2$s lleno de %1$d elementos.'
		);
		$this->assertNoWarnings(
			'unexpected_sprintf_token',
			'The application password %friendly_name%.',
			'La contraseña de aplicación %friendly_name%.'
		);

		$this->assertHasWarningsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%d">100 percent</a>',
			'<a href="%d">100%</a>',
			'The translation contains the following unexpected placeholders: ">100%<'
		);
		$this->assertHasWarningsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			' 95% of <a href="%f">100%%</a>',
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasWarningsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%%</a> of 95% ',
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasWarningsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%</a> of 95% ',
			'The translation contains the following unexpected placeholders: ">100%<, 95% '
		);
	}

	function test_named_placeholders() {
		$this->assertNoWarnings( 'named_placeholders', '###NEW_EMAIL###', '###NEW_EMAIL###' );
		$this->assertNoWarnings(
			'named_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###'
		);

		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'###NEW_EMAIL###',
			'##NEW_EMAIL##',
			'The translation appears to be missing the following placeholders: ###NEW_EMAIL###'
		);
		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'##NEW_EMAIL###',
			'###NEW_EMAIL###',
			'The translation contains the following unexpected placeholders: ###NEW_EMAIL###'
		);
		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'###NEW_EMAIL###',
			'###NUEVO_CORREO###',
			"The translation appears to be missing the following placeholders: ###NEW_EMAIL###\nThe translation contains the following unexpected placeholders: ###NUEVO_CORREO###"
		);
		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ##USERNAME##, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###USERNAME###'
		);
		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «SITENAME» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###SITENAME###'
		);
		$this->assertHasWarningsAndContainsOutput(
			'named_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a EMAIL#',
			'The translation appears to be missing the following placeholders: ###EMAIL###'
		);
	}

	public function test_chained_warnings() {
		$this->tw = new GP_Translation_Warnings();
		$this->w  = new GP_Builtin_Translation_Warnings();
		$this->w->add_all( $this->tw );

		$this->assertContainsOutput(
			'original %1$s',
			'original %2$s',
			array( 'translation %1$s', 'translation %2$s' ),
			null
		);
		$this->assertContainsOutput(
			'original %1$s',
			null,
			array( '<a>translation %1$s' ),
			array(
				array( 'tags' => 'Too many tags in translation. Found: <a>' ),
			)
		);
		$this->assertContainsOutput(
			'original %1$s',
			null,
			array( '<a>translation s' ),
			array(
				array(
					'tags'         => 'Too many tags in translation. Found: <a>',
					'placeholders' => 'Missing %1$s placeholder in translation.',
				),
			)
		);
		$this->assertContainsOutput(
			'original',
			null,
			array( '<a>translation  %1$s' ),
			array(
				array(
					'tags'         => 'Too many tags in translation. Found: <a>',
					'placeholders' => 'Extra %1$s placeholder in translation.',
				),
			)
		);
		$this->assertContainsOutput(
			'original s',
			null,
			array( "\n<a>translation s" ),
			array(
				array(
					'tags'                        => 'Too many tags in translation. Found: <a>',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'original s',
			null,
			array( "<a>translation s\n" ),
			array(
				array(
					'tags'                      => 'Too many tags in translation. Found: <a>',
					'should_not_end_on_newline' => 'Translation should not end on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			"\noriginal s",
			null,
			array( '<a>translation s' ),
			array(
				array(
					'tags'                    => 'Too many tags in translation. Found: <a>',
					'should_begin_on_newline' => 'Original and translation should both begin on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			"original s\n",
			null,
			array( '<a>translation s' ),
			array(
				array(
					'tags'                  => 'Too many tags in translation. Found: <a>',
					'should_end_on_newline' => 'Original and translation should both end on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'original',
			null,
			array( "<a>translation very very very very long \n" ),
			array(
				array(
					'tags'                      => 'Too many tags in translation. Found: <a>',
					'length'                    => 'Lengths of source and translation differ too much.',
					'should_not_end_on_newline' => 'Translation should not end on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'<p>original</p>',
			null,
			array( "</a>translation \n" ),
			array(
				array(
					'tags'                      => 'Missing tags from translation. Expected: <p> </p>',
					'should_not_end_on_newline' => 'Translation should not end on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'<p>original</p>',
			null,
			array( "</p>translation<p> \n" ),
			array(
				array(
					'tags'                      => 'The translation contains incorrect HTML tags: Unexpected end tag : p',
					'should_not_end_on_newline' => 'Translation should not end on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'<p>original</p>',
			null,
			array( "\n<a>translation</a> \n" ),
			array(
				array(
					'tags'                        => "Expected <p>, got <a>.\nExpected </p>, got </a>.",
					'should_not_end_on_newline'   => 'Translation should not end on newline.',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
				),
			)
		);
		$this->assertContainsOutput(
			'<p>https://example.com</p>',
			null,
			array( "\n<a>https://example.org</a> \n" ),
			array(
				array(
					'tags'                        => "Expected <p>, got <a>.\nExpected </p>, got </a>.",
					'should_not_end_on_newline'   => 'Translation should not end on newline.',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
					'mismatching_urls'            => "The translation appears to be missing the following URLs: https://example.com\nThe translation contains the following unexpected URLs: https://example.org",
				),
			)
		);
		$this->assertContainsOutput(
			'<a href="https://example.com">https://example.com</a>',
			null,
			array( "\n<a href=\"https://example.org\">https://example.org</a> \n" ),
			array(
				array(
					'tags'                        => "The translation appears to be missing the following URLs: https://example.com\nThe translation contains the following unexpected URLs: https://example.org",
					'should_not_end_on_newline'   => 'Translation should not end on newline.',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
					'mismatching_urls'            => "The translation appears to be missing the following URLs: https://example.com\nThe translation contains the following unexpected URLs: https://example.org",
				),
			)
		);
		$this->assertContainsOutput(
			'<a href="%s">https://example.com</a>',
			null,
			array( "\n<a href=\"javascript%s\">https://example.org</a> \n" ),
			array(
				array(
					'tags'                        => 'The translation contains the following unexpected links: javascript%s',
					'should_not_end_on_newline'   => 'Translation should not end on newline.',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
					'mismatching_urls'            => "The translation appears to be missing the following URLs: https://example.com\nThe translation contains the following unexpected URLs: https://example.org",
				),
			)
		);
		$this->assertContainsOutput(
			'<p><a href="%s">100 percent</a></p>',
			null,
			array( "\n<a href=\"%s\">100%%</a>\n" ),
			array(
				array(
					'tags'                        => 'Missing tags from translation. Expected: <p> </p>',
					'should_not_end_on_newline'   => 'Translation should not end on newline.',
					'should_not_begin_on_newline' => 'Translation should not begin on newline.',
					'placeholders'                => 'Extra %% placeholder in translation.',
				),
			)
		);
	}
}
