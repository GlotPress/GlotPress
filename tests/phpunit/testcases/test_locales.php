<?php

/**
 * @group locales
 */
class GP_Test_Locales extends GP_UnitTestCase {

	const SKIP_LOCALES = [
		// Actual: n != 1
		// Expected: n > 1
		'ak', 'am', 'as', 'bho', 'bn', 'fuc', 'ful', 'gu', 'hi', 'hy', 'kn', 'mg',
		'nso', 'pcm', 'pt-ao', 'si', 'wa', 'zul',

		// Actual: n > 1
		// Expected: n != 1
		'orm', 'tr', 'tuk',

		// Actual: n != 1
		// Expected: 0
		'bm', 'jv', 'ka', 'mya', 'nqo', 'sah', 'yor',

		// Actual: n > 1
		// Expected: (n % 10 == 1 && n % 100 != 11 && n % 100 != 71 && n % 100 != 91) ? 0 : ((n % 10 == 2 && n % 100 != 12 && n % 100 != 72 && n % 100 != 92) ? 1 : ((((n % 10 == 3 || n % 10 == 4) || n % 10 == 9) && (n % 100 < 10 || n % 100 > 19) && (n % 100 < 70 || n % 100 > 79) && (n % 100 < 90 || n % 100 > 99)) ? 2 : ((n != 0 && n % 1000000 == 0) ? 3 : 4)))
		'br',

		// Actual: n != 1
		// Expected: n != 1 && n != 2 && n != 3 && (n % 10 == 4 || n % 10 == 6 || n % 10 == 9)
		'ceb', 'tl',

		// Actual: (n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3
		// Expected: (n == 0) ? 0 : ((n == 1) ? 1 : ((n == 2) ? 2 : ((n == 3) ? 3 : ((n == 6) ? 4 : 5))))
		'cy',

		// Actual: n != 1
		// Expected: (n == 1) ? 0 : ((n == 2) ? 1 : ((n > 10 && n % 10 == 0) ? 2 : 3))
		'he',

		// Actual: n > 1
		// Expected: 0
		'id',

		// Actual: 0
		// Expected: n != 1
		'kir', 'uz',

		// Actual: n != 1
		// Expected: 0
		'tir',

		// Actual: n > 1
		// Expected: n >= 2 && (n < 11 || n > 99)
		'tzm',

		// Actual: n > 1
		// Expected: (n == 0 || n == 1) ? 0 : ((n != 0 && n % 1000000 == 0) ? 1 : 2)
		'fr', 'fr-be', 'fr-ca', 'fr-ch',
	];

	public function test_class_exists() {
		$this->assertTrue( class_exists( 'GP_Locales' ) );
	}

	/**
	 * Tests syntax of ISO codes for language and country for a locale.
	 *
	 * @dataProvider data_provider_locales
	 *
	 * @param GP_locale $locale The locale to test.
	 */
	public function test_locale_iso_codes( $locale ) {
		if ( isset( $locale->lang_code_iso_639_1 ) ) {
			$this->assertSame( 2, strlen( $locale->lang_code_iso_639_1 ), "ISO 639-1 length check for $locale" );
			$this->assertSame( 0, preg_match( '/[^a-z]/', $locale->lang_code_iso_639_1 ), "ISO 639-1 format check for $locale" );
		}

		if ( isset( $locale->lang_code_iso_639_2 ) ) {
			$this->assertSame( 3, strlen( $locale->lang_code_iso_639_2 ), "ISO 639-2 length check for $locale" );
			$this->assertSame( 0, preg_match( '/[^a-z]/', $locale->lang_code_iso_639_2 ), "ISO 639-2 format check for $locale" );
		}

		if ( isset( $locale->lang_code_iso_639_3 ) ) {
			$this->assertSame( 3, strlen( $locale->lang_code_iso_639_3 ), "ISO 639-3 length check for $locale" );
			$this->assertSame( 0, preg_match( '/[^a-z]/', $locale->lang_code_iso_639_3 ), "ISO 639-3 format check for $locale" );
		}

		if ( isset( $locale->country_code ) ) {
			$this->assertSame( 2, strlen( $locale->country_code ), "ISO 3166 length check for $locale" );
			$this->assertSame( 0, preg_match( '/[^a-z]/', $locale->country_code ), "ISO 3166 format check for $locale" );
		}
	}

	/**
	 * Tests plural form of a locale.
	 *
	 * @dataProvider data_provider_locales
	 *
	 * @param GP_locale $locale The locale to test.
	 */
	public function test_locale_plural( $locale ) {
		if ( in_array( $locale->slug, self::SKIP_LOCALES, true ) ) {
			$this->markTestSkipped( "{$locale->slug} skipped, requires research" );
		}

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
			$this->assertSame( $language->formula, $locale->plural_expression, $locale->slug );
			$this->assertSame( count( $language->categories ), $locale->nplurals, $locale->slug );
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
			$this->assertSame( $language->formula, $locale->plural_expression, $locale->slug );
			$this->assertSame( count( $language->categories ), $locale->nplurals, $locale->slug );
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
			$this->markTestSkipped( "{$locale->slug} not found in CLDR" );
		}

		$this->assertSame( $language->formula, $locale->plural_expression, $locale->slug );
		$this->assertSame( count( $language->categories ), $locale->nplurals, $locale->slug );
	}

	public function data_provider_locales() {
		unset( $GLOBALS['gp_locales'] );
		$locales = GP_Locales::locales();

		$data = [];

		foreach ( $locales as $locale ) {
			$data[ $locale->slug ] = [ $locale ];
		}

		return $data;
	}
}
