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
		$this->assertNoWarnings( 'tags', '<a href="%s">Baba</a>', '<a href="%s">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" title="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" aria-label="Blimp!">Baba</a>', '<a href="%s" aria-label="Блимп!">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!" aria-label="Blimp!">Baba</a>', '<a href="%s" title="Блимп!" aria-label="Блимп!">Баба</a>' );

		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!">Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" x>Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!" target="_blank">Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a>Baba</a>', '</a>Баба<a>' );
	}

	function test_add_all() {
		$warnings = $this->getMockBuilder('GP_Translation_Warnings')->getMock();
		// we check for the number of warnings, because PHPUnit doesn't allow
		// us to check if each argument is a callable
		$warnings->expects( $this->exactly( 8 ) )->method( 'add' )->will( $this->returnValue( true ) );
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
}
