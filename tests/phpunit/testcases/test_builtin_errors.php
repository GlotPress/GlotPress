<?php

class GP_Test_Builtin_Translation_Errors extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->w              = new GP_Translation_Errors();
		$this->l              = $this->factory->locale->create();
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}

	function _assertError( $assert, $error, $original, $translation, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->$assert( true, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertHasErrors( $error, $original, $translation, $locale = null ) {
		$this->_assertError( 'assertNotSame', $error, $original, $translation, $locale );
	}

	function assertNoErrors( $error, $original, $translation, $locale = null ) {
		$this->_assertError( 'assertSame', $error, $original, $translation, $locale );
	}

	function assertHasErrorsAndContainsOutput( $error, $original, $translation, $output_expected, $locale = null ) {
		$this->assertHasErrors( $error, $original, $translation, $locale );
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->assertStringContainsString( $output_expected, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertContainsOutput( $singular, $plural, $translations, $output_expected, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$this->assertEquals( $output_expected, $this->tw->check( $singular, $plural, $translations, $locale ) );
	}

	function test_error_unexpected_sprintf_token() {
		$this->assertNoErrors( 'unexpected_sprintf_token', '100 percent', '100%' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '<a href="%a">100 percent</a>', '<a href="%a">100%</a>' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '<a href="%s">100 percent</a>', '<a href="%s">100%%</a>' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '<a href="%1$s">100 percent</a>', '<a href="%1$s">100%%</a>' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%1$.2f baba', '%1$.2f баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%1$.2f baba', '%1$.3f баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%2$.2fMB baba', '%2$.2fMB баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%2$.3fMB baba', '%2$.2fMB баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', 'Data: %1$.2fMB | Index: %2$.2fMB | Free: %3$.2fMB | Engine: %4$s', 'Data: %1$.2fMB | Index: %2$.2fMB | Free: %3$.2fMB | Engine: %4$s' );

		$this->assertNoErrors(
			'unexpected_sprintf_token',
			'The %s contains %d items',
			'El %s contiene %d elementos'
		);
		$this->assertNoErrors(
			'unexpected_sprintf_token',
			'The %2$s contains %1$d items. That\'s a nice %2$s full of %1$d items.',
			'El %2$s contiene %1$d elementos. Es un bonito %2$s lleno de %1$d elementos.'
		);
		$this->assertNoErrors(
			'unexpected_sprintf_token',
			'The application password %friendly_name%.',
			'La contraseña de aplicación %friendly_name%.'
		);

		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%d">100 percent</a>',
			'<a href="%d">100%</a>',
			'The translation contains the following unexpected placeholders: ">100%<'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			' 95% of <a href="%f">100%%</a>',
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%%</a> of 95% ',
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%</a> of 95% ',
			'The translation contains the following unexpected placeholders: ">100%<, 95% '
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_sprintf_token',
			'This is 100 percent bug free! <a href="%s">See this for proof</a>',
			'Yo! We so great! 100% bug free! <a href="%s">Check it!</a>',
			'The translation contains the following unexpected placeholders: 100% ');
	}
}