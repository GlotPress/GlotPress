<?php

class GP_Test_Wporg_Warnings extends GP_UnitTestCase {


	function setUp() {
		parent::setUp();
		update_option( 'gp_is_wporg', true );
		$this->tw    = new GP_Translation_Warnings();
		$this->w     = new GP_Builtin_Translation_Warnings();
		$this->wporg = new GP_Wporg_Translation_Warnings();
		$this->l     = $this->factory->locale->create();
	}

	function tearDown() {
		parent::tearDown();
		delete_option( 'gp_is_wporg' );
	}

	function _assertWarning( $assert, $warning, $original, $translation, $locale = null, $gp_translation_warning = 'w' ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "warning_$warning";
		$this->$assert( true, $this->$gp_translation_warning->$method( $original, $translation, $locale ) );
	}

	function assertHasWarnings( $warning, $original, $translation, $locale = null, $gp_translation_warning = 'w' ) {
		$this->_assertWarning( 'assertNotSame', $warning, $original, $translation, $locale, $gp_translation_warning );
	}

	function assertNoWarnings( $warning, $original, $translation, $locale = null, $gp_translation_warning = 'w' ) {
		$this->_assertWarning( 'assertSame', $warning, $original, $translation, $locale, $gp_translation_warning );
	}

	function assertContainsOutput( $warning, $original, $translation, $output_expected, $locale = null, $gp_translation_warning = 'w' ) {
		if ( is_null( $locale ) ) {
			$locale = $this->l;
		}
		$method = "warning_$warning";
		$this->assertStringContainsString( $this->$gp_translation_warning->$method( $original, $translation, $locale ), $output_expected );
	}

	function test_add_all() {
		$warnings = $this->getMockBuilder( 'GP_Translation_Warnings' )->getMock();
		// we check for the number of warnings, because PHPUnit doesn't allow
		// us to check if each argument is a callable
		$warnings->expects( $this->exactly( 10 ) )->method( 'add' )->will( $this->returnValue( true ) );
		$this->w->add_all( $warnings );
	}

	function test_wporg_mismatching_urls() {
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.org/plugins/example-plugin/', 'https://es.wordpress.org/plugins/example-plugin/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://wordpress.com/log-in/', 'https://es.wordpress.com/log-in/' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.gravatar.com/matt', 'https://es.gravatar.com/matt' );
		$this->assertNoWarnings( 'mismatching_urls', 'https://en.wikipedia.org/wiki/WordPress', 'https://es.wikipedia.org/wiki/WordPress' );
	}

	function test_wporg_tags() {
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
	}

	function test_wporg_mismatching_placeholders() {
		$this->assertNoWarnings( 'wporg_mismatching_placeholders', '###NEW_EMAIL###', '###NEW_EMAIL###', null, 'wporg' );
		$this->assertNoWarnings(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			null,
			'wporg'
		);

		$this->assertHasWarnings( 'wporg_mismatching_placeholders', '###NEW_EMAIL###', '##NEW_EMAIL##', null, 'wporg' );
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'###NEW_EMAIL###',
			'##NEW_EMAIL##',
			'The translation appears to be missing the following placeholders: ###NEW_EMAIL###',
			null,
			'wporg'
		);
		$this->assertHasWarnings( 'wporg_mismatching_placeholders', '##NEW_EMAIL###', '###NEW_EMAIL###', null, 'wporg' );
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'##NEW_EMAIL###',
			'###NEW_EMAIL###',
			'The translation contains the following unexpected placeholders: ###NEW_EMAIL###',
			null,
			'wporg'
		);
		$this->assertHasWarnings( 'wporg_mismatching_placeholders', '###NEW_EMAIL###', '###NUEVO_CORREO###', null, 'wporg' );
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'###NEW_EMAIL###',
			'###NUEVO_CORREO###',
			"The translation appears to be missing the following placeholders: ###NEW_EMAIL###\nThe translation contains the following unexpected placeholders: ###NUEVO_CORREO###",
			null,
			'wporg'
		);
		$this->assertHasWarnings(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ##USERNAME##, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			null,
			'wporg'
		);
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ##USERNAME##, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###USERNAME###',
			null,
			'wporg'
		);
		$this->assertHasWarnings(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «SITENAME» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			null,
			'wporg'
		);
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «SITENAME» (###SITEURL###) tu nueva contraseña a ###EMAIL###',
			'The translation appears to be missing the following placeholders: ###SITENAME###',
			null,
			'wporg'
		);
		$this->assertHasWarnings(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a EMAIL#',
			null,
			'wporg'
		);
		$this->assertContainsOutput(
			'wporg_mismatching_placeholders',
			'Hi ###USERNAME###, we sent to ###EMAIL### your new password from "###SITENAME###" (###SITEURL###)',
			'Hola ###USERNAME###, te enviamos desde «###SITENAME###» (###SITEURL###) tu nueva contraseña a EMAIL#',
			'The translation appears to be missing the following placeholders: ###EMAIL###',
			null,
			'wporg'
		);
	}
}
