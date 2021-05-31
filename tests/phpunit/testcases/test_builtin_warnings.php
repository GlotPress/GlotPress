<?php

class GP_Test_Builtin_Translation_Warnings extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->w = new GP_Builtin_Translation_Warnings;
		$this->l = $this->factory->locale->create();
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}

	function _assertWarning( $assert, $warning, $original, $translation, $locale = null ) {
		if ( is_null( $locale ) ) $locale = $this->l;
		$method = "warning_$warning";
		$this->$assert( true, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertHasWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertNotSame', $warning, $original, $translation, $locale );
	}

	function assertNoWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertSame', $warning, $original, $translation, $locale );
	}

	function AssertContainsOutput($warning, $original, $translation, $output_expected, $locale = null ) {
		if ( is_null( $locale ) ) $locale = $this->l;
		$method = "warning_$warning";
		$this->assertStringContainsString( $this->w->$method( $original, $translation, $locale ), $output_expected );
	}

	function test_length() {
		$this->assertNoWarnings( 'length', $this->longer_than_20, $this->longer_than_20 );
		$this->assertHasWarnings( 'length', $this->longer_than_20, $this->shorter_than_5 );
	}

	function test_length_exclude() {
		$w_without_locale = new GP_Builtin_Translation_Warnings;
		$w_without_locale->length_exclude_languages = array( $this->l->slug );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->longer_than_20, $this->l ) );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->shorter_than_5, $this->l ) );
	}

	function test_tags() {
		$this->assertNoWarnings( 'tags', 'Baba', 'Баба' );
		$this->assertNoWarnings( 'tags',
			'<p><abbr title="World Health Organization">WHO</abbr> was founded in 1948.</p>',
			'<p>La<abbr title="Organización Mundial de la Salud">OMS</abbr> se fundó en 1948.</p>' );
		$this->assertNoWarnings( 'tags',
			'<button aria-label="Close">X</button><button aria-label="Open">O</button>',
			'<button aria-label="Cerrar">X</button><button aria-label="Abrir">A</button>' );
		$this->assertNoWarnings( 'tags', '<a href="%s">Baba</a>', '<a href="%s">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" title="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" aria-label="Blimp!">Baba</a>', '<a href="%s" aria-label="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!" aria-label="Blimp!">Baba</a>', '<a href="%s" title="Блимп!" aria-label="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="https://www.example.org" title="Example!" lang="en">Example URL</a>',
			'<a href="https://www.example.org" title="¡Ejemplo!" lang="es">URL de jemplo</a>' );
		$this->assertNoWarnings( 'tags',
			' Text 1 <a href="https://wordpress.org/plugins/example-plugin/">Example plugin</a> Text 2<a href="https://wordpress.com/log-in/">Log in</a> Text 3 <img src="example.jpg" alt="Example alt text">',
			' Texto 1 <a href="https://es.wordpress.org/plugins/example-plugin/">Plugin de ejemplo</a> Texto 2<a href="https://es.wordpress.com/log-in/">Acceder</a> Texto 3 <img src="example.jpg" alt="Texto alternativo de ejemplo">');
		$this->assertNoWarnings('tags',
			'<img src="https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress in the Wikipedia">',
			'<img src="https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress en la Wikipedia">');
		$this->assertNoWarnings( 'tags',
			' Text 1 <a href="https://wordpress.com/log-in">Log in</a> Text 2 <img src="https://en.gravatar.com/matt" alt="Matt\'s Gravatar"> ',
			' Texto 1 <a href="https://es.wordpress.com/log-in">Acceder</a> Texto 2 <img src="https://es.gravatar.com/matt" alt="Gravatar de Matt"> ');
		$this->l->slug = 'ja';
		$this->assertNoWarnings( 'tags',
			'<b>Text 1</b>, <i>Italic text</i>, Text 2, <em>Emphasized text</em>, Text 3',
			'<b>テキスト1</b>、イタリック体、テキスト2、エンファシス体、テキスト3',
					$this->l);

		$this->assertHasWarnings('tags', '<p>Paragraph</p>', '<p>Párrafo');
		$this->AssertContainsOutput('tags', '<p>Paragraph</p>', '<p>Párrafo', 'Missing tags from translation. Expected: </p>');
		$this->assertHasWarnings('tags', 'Paragraph</p>', '<p>Párrafo</p>');
		$this->AssertContainsOutput('tags', 'Paragraph</p>', '<p>Párrafo</p>', 'Too many tags in translation. Found: <p>');
		$this->assertHasWarnings('tags', '<h1>Title</h1><p>Text 1</p><br><b>Text 2</b>', '<h1>Título</h1><p>Texto 1<br><b>Texto 2</b>');
		$this->AssertContainsOutput('tags', '<h1>Title</h1><p>Text 1</p><br><b>Text 2</b>', '<h1>Título</h1><p>Texto 1<br><b>Texto 2</b>',
			'Missing tags from translation. Expected: </p>');
		$this->assertHasWarnings('tags', '<h1>Title</h1>Text 1</p><br><b>Text 2</b>', '<h1>Título</h1><p>Texto 1</p><br><b>Texto 2</b>');
		$this->AssertContainsOutput('tags', '<h1>Title</h1>Text 1</p><br><b>Text 2</b>', '<h1>Título</h1><p>Texto 1</p><br><b>Texto 2</b>',
			'Too many tags in translation. Found: <p>');
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!">Баба</a>' );
		$this->AssertContainsOutput( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!">Баба</a>',
			"The translation appears to be missing the following links: %s\nThe translation contains the following unexpected links: javascript:%s" );
		$this->assertHasWarnings( 'tags', '<a href="https://www.example.org" title="Example!">Example URL</a>',
			'<a href="https://www.example.com" title="¡Ejemplo!">Ejemplo</a>' );
		$this->AssertContainsOutput( 'tags', '<a href="https://www.example.org" title="Example!">Example URL</a>',
			'<a href="https://www.example.com" title="¡Ejemplo!">Ejemplo</a>',
		"The translation appears to be missing the following URLs: https://www.example.org\nThe translation contains the following unexpected URLs: https://www.example.com");
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" x>Баба</a>' );
		$this->AssertContainsOutput( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" x>Баба</a>',
			'Expected <a href="%s" title="Blimp!">, got <a href="%s" x>.' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>',
			'<a href="javascript:%s" title="Блимп!" target="_blank">Баба</a>' );
		$this->AssertContainsOutput( 'tags', '<a href="%s" title="Blimp!">Baba</a>',
			'<a href="javascript:%s" title="Блимп!" target="_blank">Баба</a>',
			"The translation appears to be missing the following links: %s\nThe translation contains the following unexpected links: javascript:%s" );
		$this->assertHasWarnings( 'tags', '<a>Baba</a>', '</a>Баба<a>' );
		$this->AssertContainsOutput( 'tags', '<a>Baba</a>', '</a>Баба<a>', 'Tags in incorrect order: </a>, <a>' );
		$this->assertHasWarnings( 'tags', '<h1>Hello</h1><h2>Peter</h2>',  '<h1>Hola</h1></h2>Pedro<h2>');
		$this->AssertContainsOutput( 'tags', '<h1>Hello</h1><h2>Peter</h2>',  '<h1>Hola</h1></h2>Pedro<h2>',
			'Tags in incorrect order: </h2>, <h2>');
		$this->assertHasWarnings( 'tags', '<h1>Hello</h1><h2>Peter</h2>', '<h2>Pedro</h2><h1>Hola</h1>');
		$this->assertHasWarnings( 'tags', '<p>Hello<b><i>Peter</i></b></p>', '<p>Hola<i><b>Pedro</b></i></p>');
		$this->AssertContainsOutput( 'tags', '<p>Hello<b><i>Peter</i></b></p><h2>Today</h2>',
			'<p>Hola<i><b>Pedro</b></i></p><h2>Hoy</h2>',
			'Tags in incorrect order: <i>, <b>, </b>, </i>');
		$this->AssertContainsOutput( 'tags', '<h1>Hello</h1><h2>Peter</h2>', '<h2>Pedro</h2><h1>Hola</h1>',
			'Tags in incorrect order: <h2>, </h2>, <h1>, </h1>');
		$this->assertHasWarnings('tags',
			'<img src="https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress en la Wikipedia">',
			'<img src="https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress in the Wikipedia">');
		$this->AssertContainsOutput('tags',
			'<img src="https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress en la Wikipedia">',
			'<img src="https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg" alt="WordPress in the Wikipedia">',
		"The translation appears to be missing the following URLs: https://es.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg\nThe translation contains the following unexpected URLs: https://en.wikipedia.org/wiki/WordPress#/media/File:WordPress_logo.svg");
		$this->l->slug = 'ja';
		$this->assertHasWarnings( 'tags',
			'<b>Text 1</b>, <i>Italic text</i>, Text 2, <em>Emphasized text</em>, Text 3',
			'</b>テキスト1<b>、イタリック体、テキスト2、エンファシス体、テキスト3',
			$this->l);
		$this->AssertContainsOutput( 'tags',
			'<b>Text 1</b>, <i>Italic text</i>, Text 2, <em>Emphasized text</em>, Text 3',
			'</b>テキスト1<b>、イタリック体、テキスト2、エンファシス体、テキスト3',
			'Tags in incorrect order: </b>, <b>',
			$this->l);
	}

	function test_add_all() {
		$warnings = $this->getMockBuilder('GP_Translation_Warnings')->getMock();
		// we check for the number of warnings, because PHPUnit doesn't allow
		// us to check if each argument is a callable
		$warnings->expects( $this->exactly( 9 ) )->method( 'add' )->will( $this->returnValue( true ) );
		$this->w->add_all( $warnings );
	}

	function test_placeholders() {
		$this->assertHasWarnings( 'placeholders', '%s baba', 'баба' );
		$this->assertHasWarnings( 'placeholders', '%s baba', '% баба' );
		$this->assertNoWarnings( 'placeholders', '%s baba', '%s баба' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba %2$s dyado', '%1$sбабадядо%2$s' );
		$this->assertNoWarnings( 'placeholders', '% baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '% baba', '% баба' );
		$this->assertHasWarnings( 'placeholders', '%1$s baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba', '%1$s баба' );
		$this->assertNoWarnings( 'placeholders', '%sHome%s', '%sНачало%s' );
		$this->assertNoWarnings( 'placeholders', 'This string has %stwo variables%s.', 'Deze string heeft %stwee variabelen%s.' );
	}

	function test_should_begin_end_on_newline() {
		$this->assertHasWarnings( 'should_begin_on_newline', "\nbaba", "baba" );
		$this->assertHasWarnings( 'should_not_begin_on_newline', "baba", "\nbaba" );
		$this->assertHasWarnings( 'should_end_on_newline', "baba\n", "baba" );
		$this->assertHasWarnings( 'should_not_end_on_newline', "baba", "baba\n" );

		$this->assertNoWarnings( 'should_begin_on_newline', "baba", "baba" );
		$this->assertNoWarnings( 'should_not_begin_on_newline', "baba", "baba" );
		$this->assertNoWarnings( 'should_end_on_newline', "baba", "baba" );
		$this->assertNoWarnings( 'should_not_end_on_newline', "baba", "baba" );

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
		$w = new GP_Translation_Warnings;
		$builtin = new GP_Builtin_Translation_Warnings;
		$w->add( 'placeholder', array( $builtin, 'warning_placeholders' ) );

		$fr = new GP_Locale( array(
			'nplurals' => 2,
			'plural_expression' => 'n > 1'
		));
		$this->assertEquals( null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s' ), $fr ) );
		$this->assertEquals( null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation %s' ), $fr ) );
		$this->assertEquals( array( 1 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation' ), $fr ) );

		$de = new GP_Locale( array(
			'nplurals' => 2,
			'plural_expression' => 'n != 1'
		));
		$this->assertEquals( null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s' ), $de ) );
		$this->assertEquals( null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation %s' ), $de ) );

		$ja = new GP_Locale( array(
			'nplurals' => 1,
			'plural_expression' => '0'
		));

		$this->assertEquals( null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %2$s' ), $ja ) );
		$this->assertEquals( null,
			$w->check( 'original', 'original %s', array( 'translation %s' ), $ja ) );
		$this->assertEquals( array( 0 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation' ), $ja ) );

		$ru = new GP_Locale( array(
			'nplurals' => 3,
			'plural_expression' => '(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)'
		));

		$this->assertEquals( null,
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation %2$s', 'translation 2 %2$s' ), $ru ) );
		$this->assertEquals( null,
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2 %s', 'translation 3 %s' ), $ru ) );
		$this->assertEquals( array( 1 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ) ),
			$w->check( 'original %1$s', 'original %2$s', array( 'translation %1$s', 'translation 2', 'translation 3 %2$s' ), $ru ) );
		$this->assertEquals( array( 2 => array( 'placeholder' => 'Missing %s placeholder in translation.' ) ),
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2 %s', 'translation 3' ), $ru ) );
		$this->assertEquals( array( 1 => array( 'placeholder' => 'Missing %s placeholder in translation.' ),
			2 => array( 'placeholder' => 'Missing %s placeholder in translation.' ) ),
			$w->check( 'original', 'original %s', array( 'translation', 'translation 2', 'translation 3' ), $ru ) );
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
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.org/plugins/example-plugin/', 'https://es.wordpress.org/plugins/example-plugin/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.com/log-in/', 'https://es.wordpress.com/log-in/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.gravatar.com/matt', 'https://es.gravatar.com/matt' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.wikipedia.org/wiki/WordPress', 'https://es.wikipedia.org/wiki/WordPress' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', 'Texto1 https://www.example.com Texto2 https://www.example.org Texto3' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', ' Texto3 https://www.example.org Texto2 https://www.example.com Texto1  ' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', '  https://www.example.org Texto1   Texto3   https://www.example.com  Texto2  ' );
		$this->assertNoWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org Text3', '  https://www.example.org https://www.example.com ' );

		$this->assertHasWarnings( 'mismatching_urls', 'HTTPS://WWW.EXAMPLE', 'https://www.example' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.example', 'HTTPS://WWW.EXAMPLE' );
		$this->assertHasWarnings( 'mismatching_urls', 'HtTpS://WwW.eXaMpLe', 'https://www.example' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.example.com', 'https://www.example.org' );
		$this->assertHasWarnings( 'mismatching_urls', '//www.example.com', 'http://www.example.org' );
		$this->assertHasWarnings( 'mismatching_urls', '//www.example.com', 'https://www.example.org' );
		$this->assertHasWarnings( 'mismatching_urls', 'http://www.example.com', '//www.example.org' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.example.com', '//www.example.org' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.exañple.com', 'https://www.example.com' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.example.com', 'https://www.exañple.com' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.wordpress.org/plugins/example-plugin/', 'https://es.wordpress.org/plugins/example-plugin/' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://www.wordpress.com/log-in/', 'https://es.wordpress.com/log-in/' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://es.gravatar.com/matt', 'https://en.gravatar.com/matt' );
		$this->assertHasWarnings( 'mismatching_urls', 'https://es.wikipedia.org/wiki/WordPress', 'https://en.wikipedia.org/wiki/WordPress' );
		$this->assertHasWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2', 'Texto1 Texto2' );
		$this->assertHasWarnings( 'mismatching_urls', 'Text1 Text2', 'Texto1 https://www.example.com Texto2' );
		$this->assertHasWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2 https://www.example.org', 'Texto1 https://www.example.com Texto2' );
		$this->assertHasWarnings( 'mismatching_urls', 'Text1 https://www.example.com Text2', 'Texto1 https://www.example.com Texto2 https://www.example.org' );
	}

	function test_mismatching_placeholders() {
		$this->assertNoWarnings( 'mismatching_placeholders', '###NEW_EMAIL###', '###NEW_EMAIL###');
		$this->assertNoWarnings( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###');

		$this->assertHasWarnings( 'mismatching_placeholders', '###NEW_EMAIL###', '##NEW_EMAIL##');
		$this->AssertContainsOutput( 'mismatching_placeholders', '###NEW_EMAIL###', '##NEW_EMAIL##',
		"The translation appears to be missing the following placeholders: ###NEW_EMAIL###");
		$this->assertHasWarnings( 'mismatching_placeholders', '##NEW_EMAIL###', '###NEW_EMAIL###');
		$this->AssertContainsOutput( 'mismatching_placeholders', '##NEW_EMAIL###', '###NEW_EMAIL###',
		'The translation contains the following unexpected placeholders: ###NEW_EMAIL###');
		$this->assertHasWarnings( 'mismatching_placeholders', '###NEW_EMAIL###', '###NUEVO_CORREO###');
		$this->AssertContainsOutput( 'mismatching_placeholders', '###NEW_EMAIL###', '###NUEVO_CORREO###',
		"The translation appears to be missing the following placeholders: ###NEW_EMAIL###\nThe translation contains the following unexpected placeholders: ###NUEVO_CORREO###");
		$this->assertHasWarnings( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ##USERNAME##, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###');
		$this->AssertContainsOutput( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ##USERNAME##, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###USERNAME###');
		$this->assertHasWarnings( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «SITENAME» (###SITEURL###) tu nueva contraseña a ###EMAIL###');
		$this->AssertContainsOutput( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «SITENAME» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###SITENAME###');
		$this->assertHasWarnings( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a EMAIL#');
		$this->AssertContainsOutput( 'mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a EMAIL#',
			'The translation appears to be missing the following placeholders: ###EMAIL###');
	}
}
