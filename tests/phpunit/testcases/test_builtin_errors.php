<?php

class GP_Test_Builtin_Translation_Errors extends GP_UnitTestCase {

	function setUp(): void {
		parent::setUp();
		$this->w                 = new GP_Builtin_Translation_Errors();
		$this->l                 = $this->factory->locale->create();
		$project                 = $this->factory->project->create();
		$this->original          = $this->factory->original->create( array( 'project_id' => $project->id ) );
		$this->original->comment = 'Test comment';
	}

	function _assertError( $assert, $error, $original, $translation, $gp_original = null, $locale = null ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->$assert( true, $this->w->$method( $original, $translation, $gp_original, $locale ) );
	}

	function assertHasErrors( $error, $original, $translation, $gp_original, $locale = null ) {
		$this->_assertError( 'assertNotSame', $error, $original, $translation, $gp_original, $locale );
	}

	function assertNoErrors( $error, $original, $translation, $gp_original = null, $locale = null ) {
		$this->_assertError( 'assertSame', $error, $original, $translation, $gp_original, $locale );
	}

	function assertHasErrorsAndContainsOutput( $error, $original, $translation, $gp_original, $output_expected, $locale = null ) {
		$this->assertHasErrors( $error, $original, $translation, $gp_original, $locale );
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "error_$error";
		$this->assertStringContainsString( $output_expected, $this->w->$method( $original, $translation, $gp_original, $locale ) );
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
		$this->assertNoErrors( 'unexpected_sprintf_token', 'This is 100 percent bug free!', 'This is 100% bug free!' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '<a href="%f">95 percent</a>', '<a href="%F">95 percent</a>' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '<a href="%S">95 percent</a>', '<a href="%s">95 percent</a>' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%2$.2f baba', '%2$.2F баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%2$.2F baba', '%2$.2f баба' );
		$this->assertNoErrors( 'unexpected_sprintf_token', '%x of %ld', '%X von %ld' );

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
			'The translation contains the following unexpected placeholders: 00%<'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'95 percent of <a href="%f">100 percent</a>',
			'95% of <a href="%f">100%%</a>',
			null,
			'The translation contains the following unexpected placeholders: 95% (unescaped %, use %% instead)'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">95 percent</a> of 100 percent',
			'<a href="%f">95%%</a> of 100%',
			null,
			'The translation contains the following unexpected placeholders: 00% (unescaped %, use %% instead)'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">95 percent</a> of 100 percent!',
			'<a href="%f">95%%</a> of 100%!',
			null,
			'The translation contains the following unexpected placeholders: 00%! (unescaped %, use %% instead)'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'<a href="%f">95 percent</a> of 100 percent',
			'<a href="%f">95%</a> of 100%',
			null,
			'The translation contains the following unexpected placeholders: 95%< (unescaped %, use %% instead), 00% (unescaped %, use %% instead)'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'This is 100 percent bug free! <a href="%s">See this for proof</a>',
			'Yo! We so great! 100% bug free! <a href="%s">Check it!</a>',
			null,
			'The translation contains the following unexpected placeholders: 00%'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'%s items',
			'%S Elemente',
			null,
			'The translation contains the following unexpected placeholders: %S'
		);
		$this->assertHasErrorsAndContainsOutput(
			'unexpected_sprintf_token',
			'%d items',
			'%D Elemente',
			null,
			'The translation contains the following unexpected placeholders: %D'
		);
		$this->assertHasErrors( 'unexpected_sprintf_token', '%s items', '%l Elemente', null );
	}

	function test_error_extra_sprintf_placeholders() {
		$this->assertNoErrors( 'extra_sprintf_placeholders', 'The %s contains %d items', 'El %s contiene %d elementos' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', 'The %s contains %d items', 'Der Inhalt von %s' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', '%s items', '%d Elemente' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', '%s of %s', '%2$s von %1$s' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', '%s items', '%1$s Elemente, nochmal %1$s' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', '%s percent', '%s %%' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', 'Hello', 'Hallo %s' );
		$this->assertNoErrors( 'extra_sprintf_placeholders', '100% of %s', '100% von %s und %s' );

		$this->assertHasErrorsAndContainsOutput(
			'extra_sprintf_placeholders',
			'%s items',
			'%s Elemente in %s',
			null,
			'The translation contains more placeholders than the original: %s'
		);
		$this->assertHasErrorsAndContainsOutput(
			'extra_sprintf_placeholders',
			'%s items',
			'%2$s Elemente',
			null,
			'The translation contains more placeholders than the original: %2$s'
		);
		$this->assertHasErrorsAndContainsOutput(
			'extra_sprintf_placeholders',
			'%1$s of %2$s',
			'%1$s von %2$s in %3$s und %4$d',
			null,
			'The translation contains more placeholders than the original: %3$s, %4$d'
		);
		$this->assertHasErrorsAndContainsOutput(
			'extra_sprintf_placeholders',
			'<a href="%s">Docs</a>',
			'<a href="%s">Doku</a> unter example.org/%2Fdocs',
			null,
			'%2F'
		);
	}

	function test_error_extra_sprintf_placeholders_counts_both_plural_forms() {
		$errors = new GP_Translation_Errors();
		$this->w->add_all( $errors );

		$russian  = $this->factory->locale->create(
			array(
				'nplurals'          => 3,
				'plural_expression' => '(n % 10 == 1 && n % 100 != 11) ? 0 : ((n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 12 || n % 100 > 14)) ? 1 : 2)',
			)
		);
		$original = $this->factory->original->create(
			array(
				'singular' => 'One item',
				'plural'   => '%d items',
			)
		);

		$this->assertNull( $errors->check( $original, array( '%d элемент', '%d элемента', '%d элементов' ), $russian ) );

		$problems = $errors->check( $original, array( '%d элемент в %s', '%d элемента', '%d элементов' ), $russian );
		$this->assertArrayHasKey( 'extra_sprintf_placeholders', $problems[0] );
		$this->assertArrayNotHasKey( 1, $problems );
	}

	function test_error_control_characters() {
		$this->assertNoErrors( 'control_characters', 'Original', 'A clean translation' );
		$this->assertNoErrors( 'control_characters', 'Original', "Tabs\tnewlines\nand\rreturns are fine" );

		$this->assertHasErrorsAndContainsOutput( 'control_characters', 'Original', "EOT\x04here", null, 'U+0004' );
		$this->assertHasErrorsAndContainsOutput( 'control_characters', 'Original', "SOH\x01here", null, 'U+0001' );
		$this->assertHasErrorsAndContainsOutput( 'control_characters', 'Original', "Bell\x07here", null, 'U+0007' );
		$this->assertHasErrorsAndContainsOutput( 'control_characters', 'Original', "Escape\x1Bhere", null, 'U+001B' );
		$this->assertHasErrorsAndContainsOutput( 'control_characters', 'Original', "Delete\x7Fhere", null, 'U+007F' );
	}
}
