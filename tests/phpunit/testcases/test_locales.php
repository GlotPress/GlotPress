<?php

/**
 * @group locales
 */
class GP_Test_Locales extends GP_UnitTestCase {

	const SKIP_LOCALES = [
		// Actual: n != 1
		// Expected: n > 1
		'ak', 'am', 'as', 'bho', 'bn', 'fuc', 'ful', 'gu', 'hi', 'hy', 'kn', 'mg',
		'nso', 'pa', 'pt-ao', 'si', 'wa', 'zul',

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
	];

	public function test_class_exists() {
		$this->assertTrue( class_exists( 'GP_Locales' ) );
	}

	/**
	 * @dataProvider data_provider_locales
	 */
	public function test_locale_plural( $locale ) {
		if ( in_array( $locale->slug, self::SKIP_LOCALES, true ) ) {
			$this->markTestSkipped( "{$locale} skipped, requires discussion\n" );
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
			$this->assertSame( $language->formula, $locale->plural_expression, $locale );
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
			$this->assertSame( $language->formula, $locale->plural_expression, $locale );
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

		$this->assertSame( $language->formula, $locale->plural_expression, $locale );
	}

	public function data_provider_locales() {
		unset( $GLOBALS['gp_locales'] );
		$locales = GP_Locales::locales();

		$data = [];

		foreach ( $locales as $locale ) {
			$data[] = [ $locale ];
		}

		return $data;
	}
}
