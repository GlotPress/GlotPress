<?php

class GP_Test_Builtin_Translation_Errors extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->w              = new GP_Translation_Errors();
		$this->l              = $this->factory->locale->create();
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}

	function _assertError( $assert, $error, $original, $translation, $comment = null, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->$assert( true, $this->w->$method( $original, $translation, $comment, $locale ) );
	}

	function assertHasErrors( $error, $original, $translation, $comment = null, $locale = null ) {
		$this->_assertError( 'assertNotSame', $error, $original, $translation, $comment, $locale );
	}

	function assertNoErrors( $error, $original, $translation, $comment = null, $locale = null ) {
		$this->_assertError( 'assertSame', $error, $original, $translation, $comment, $locale );
	}

	function assertHasErrorsAndContainsOutput( $error, $original, $translation, $comment, $output_expected, $locale = null ) {
		$this->assertHasErrors( $error, $original, $translation, $comment, $locale );
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->assertStringContainsString( $output_expected, $this->w->$method( $original, $translation, $comment, $locale ) );
	}

	function assertContainsOutput( $singular, $plural, $translations, $comment, $output_expected, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$this->assertEquals( $output_expected, $this->tw->check( $singular, $plural, $translations, $comment, $locale ) );
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
			null,
			'The translation contains the following unexpected placeholders: ">100%<'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			' 95% of <a href="%f">100%%</a>',
			null,
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%%</a> of 95% ',
			null,
			'The translation contains the following unexpected placeholders: 95% '
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">100 percent</a>',
			'<a href="%f">100%</a> of 95% ',
			null,
			'The translation contains the following unexpected placeholders: ">100%<, 95% '
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_sprintf_token',
			'This is 100 percent bug free! <a href="%s">See this for proof</a>',
			'Yo! We so great! 100% bug free! <a href="%s">Check it!</a>',
			null,
			'The translation contains the following unexpected placeholders: 100% ' );
	}

	function test_error_unexpected_timezone() {
		$this->assertNoErrors( 'unexpected_timezone', '0', 'Europe/Madrid', 'default_GMT_offset_or_timezone_string' );
		$this->assertNoErrors( 'unexpected_timezone', '0', '-12', 'default_GMT_offset_or_timezone_string' );
		$this->assertNoErrors( 'unexpected_timezone', '0', '-1', 'default_GMT_offset_or_timezone_string' );
		$this->assertNoErrors( 'unexpected_timezone', '0', '0', 'default_GMT_offset_or_timezone_string' );
		$this->assertNoErrors( 'unexpected_timezone', '0', '+1', 'default_GMT_offset_or_timezone_string' );
		$this->assertNoErrors( 'unexpected_timezone', '0', '+14', 'default_GMT_offset_or_timezone_string' );

		$this->assertHasErrorsAndContainsOutput( 'unexpected_timezone',
			'0',
			'+15',
			'default_GMT_offset_or_timezone_string',
			'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).'
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_timezone',
			'0',
			'',
			'default_GMT_offset_or_timezone_string',
			'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).'
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_timezone',
			'0',
			'abc',
			'default_GMT_offset_or_timezone_string',
			'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).'
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_timezone',
			'0',
			'Europe',
			'default_GMT_offset_or_timezone_string',
			'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).'
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_timezone',
			'0',
			'Europa/Madrid',
			'default_GMT_offset_or_timezone_string',
			'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).'
		);
	}

	function test_unexpected_start_of_week_number() {
		$this->assertNoErrors( 'unexpected_start_of_week_number', '1', 0, 'start_of_week_number' );
		$this->assertNoErrors( 'unexpected_start_of_week_number', '1', 1, 'start_of_week_number' );

		$this->assertHasErrorsAndContainsOutput( 'unexpected_start_of_week_number',
			'1',
			2,
			'start_of_week_number',
			'Must be an integer number between 0 and 1.'
		);
		$this->assertHasErrorsAndContainsOutput( 'unexpected_start_of_week_number',
			'1',
			'',
			'start_of_week_number',
			'Must be an integer number between 0 and 1.'
		);
	}
}