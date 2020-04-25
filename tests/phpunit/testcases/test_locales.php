<?php

/**
 * @group locales
 */
class GP_Test_Locales extends GP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'GP_Locales' ) );
	}

	/**
	 * @dataProvider data_provider_locales_plural
	 */
	function test_locale_plural( $locale, $expected ) {
		$id = '';
		if ( isset( $locale->lang_code_iso_639_1, $locale->country_code ) ) {
			$id = $locale->lang_code_iso_639_1 . '_' . strtoupper( $locale->country_code );
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_1 ) ) {
			$id = $locale->lang_code_iso_639_1;
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_2 ) ) {
			$id = $locale->lang_code_iso_639_2;
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_3 ) ) {
			$id = $locale->lang_code_iso_639_3;
		}

		$language = Gettext\Languages\Language::getById( $id );
		if ( $language ) {
			$this->assertSame( $language->formula, $expected, $locale );
			return;
		}

		// Try again with different ID.
		$id = '';
		if ( isset( $locale->lang_code_iso_639_1 ) ) {
			$id = $locale->lang_code_iso_639_1;
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_2 ) ) {
			$id = $locale->lang_code_iso_639_2;
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_3 ) ) {
			$id = $locale->lang_code_iso_639_3;
		}

		$language = Gettext\Languages\Language::getById( $id );
		if ( $language ) {
			$this->assertSame( $language->formula, $expected, $locale );
			return;
		}

		// Try again with different ID.
		$id = '';
		if ( isset( $locale->lang_code_iso_639_2 ) ) {
			$id = $locale->lang_code_iso_639_2;
		}

		if ( ! $id && isset( $locale->lang_code_iso_639_3 ) ) {
			$id = $locale->lang_code_iso_639_3;
		}

		$language = Gettext\Languages\Language::getById( $id );
		if ( ! $language ) {
			$this->markTestSkipped( "{$locale} with {$id} does not exist\n" );
		}

		$this->assertSame( $language->formula, $expected, $locale );
	}

	public function data_provider_locales_plural() {
		unset( $GLOBALS['gp_locales'] );
		$locales = GP_Locales::locales();

		$data = [];

		foreach ( $locales as $locale ) {
			$data[] = [ $locale, $locale->plural_expression ];
		}

		return $data;
	}
}
