<?php

class GP_Test_Locale extends GP_UnitTestCase {

	// Test if old property can be read when someone tries to read it.
	function test_rtl_old() {
		$locale = $this->factory->locale->create();
		$locale->text_direction = 'rtl';

		$this->assertTrue( isset( $locale->rtl ) );
		$this->assertTrue( $locale->rtl );
	}

	/**
	 * Data provider of plural expressions and the index expected for each number.
	 *
	 * Nested ternaries are the interesting cases, because they are the reason the
	 * expression used to be parenthesized before being evaluated.
	 */
	function provide_plural_expressions() {
		return array(
			'no plurals (japanese)' => array(
				1,
				'0',
				array( 0 => 0, 1 => 0, 2 => 0, 100 => 0 ),
			),
			'two forms (english)'   => array(
				2,
				'n != 1',
				array( 0 => 1, 1 => 0, 2 => 1, 21 => 1 ),
			),
			'two forms (french)'    => array(
				2,
				'n > 1',
				array( 0 => 0, 1 => 0, 2 => 1, 21 => 1 ),
			),
			'three forms (russian)' => array(
				3,
				'(n%10==1 && n%100!=11) ? 0 : ((n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20)) ? 1 : 2)',
				array( 0 => 2, 1 => 0, 2 => 1, 5 => 2, 11 => 2, 21 => 0, 22 => 1, 25 => 2 ),
			),
			'three forms (polish)'  => array(
				3,
				'(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<12 || n%100>14) ? 1 : 2)',
				array( 1 => 0, 2 => 1, 5 => 2, 12 => 2, 22 => 1, 25 => 2 ),
			),
			'six forms (arabic)'    => array(
				6,
				'n == 0 ? 0 : n == 1 ? 1 : n == 2 ? 2 : n % 100 >= 3 && n % 100 <= 10 ? 3 : n % 100 >= 11 && n % 100 <= 99 ? 4 : 5',
				array( 0 => 0, 1 => 1, 2 => 2, 5 => 3, 15 => 4, 100 => 5 ),
			),
		);
	}

	/**
	 * @dataProvider provide_plural_expressions
	 */
	function test_index_for_number( $nplurals, $plural_expression, $expected ) {
		$locale                    = $this->factory->locale->create();
		$locale->nplurals          = $nplurals;
		$locale->plural_expression = $plural_expression;

		foreach ( $expected as $number => $index ) {
			$this->assertSame( $index, $locale->index_for_number( $number ), "number {$number}" );
		}
	}

	/**
	 * An expression which cannot be parsed falls back to the default plural form.
	 */
	function test_index_for_number_with_invalid_expression() {
		$locale                    = $this->factory->locale->create();
		$locale->nplurals          = 2;
		$locale->plural_expression = 'this is not an expression';

		$this->assertSame( 1, $locale->index_for_number( 0 ) );
		$this->assertSame( 0, $locale->index_for_number( 1 ) );
		$this->assertSame( 1, $locale->index_for_number( 2 ) );
	}
}