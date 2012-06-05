<?php
require_once('init.php');

class GP_Test_Translation_Warnings extends GP_UnitTestCase {
	function setUp() {
		parent::setUp();
		$this->is_baba = create_function('$o, $t, $l', 'return $t == "баба"? true : "error";');
		$this->is_equal = create_function('$o, $t, $l', 'return $t == $o? true : "error";');
		$this->w = new GP_Translation_Warnings;
		$this->with_equal = new GP_Translation_Warnings;
		$this->with_equal->add( 'is_equal', $this->is_equal );
		$this->standard_plural_locale = $this->factory->locale->create();
	}

	function test_add() {
		$this->w->add( 'is_baba', $this->is_baba );
		$this->assertEquals( true, $this->w->has( 'is_baba' ) );
		$this->assertEquals( false, $this->w->has( 'is_dyado' ) );
	}

	function test_remove() {
		$this->w->add( 'is_baba', $this->is_baba );
		$this->assertEquals( true, $this->w->has( 'is_baba' ) );
		$this->w->remove( 'is_baba' );
		$this->assertEquals( false, $this->w->has( 'is_baba' ) );
	}

	function test_check() {
		$this->w->add( 'is_baba', $this->is_baba );
		$this->assertEquals( array(
			1 => array('is_baba' => 'error')),
			$this->w->check( 'baba', null, array('баба', 'баби'), $this->standard_plural_locale ) );
		$this->assertEquals( null,
			$this->w->check( 'baba', null, array('баба', 'баба', 'баба'), $this->standard_plural_locale ) );
	}

	/**
	 * For the plural form, corresponding to the number 1 check only against the singular, not also against the plural
	 */
	function test_check_singular_plural_correspondence() {
		$this->assertEquals( null,
			$this->with_equal->check( 'baba', 'babas', array('baba', 'babas'), $this->standard_plural_locale ) );
		$this->assertEquals( array(1 => array('is_equal' => 'error')),
			$this->with_equal->check( 'baba', 'babas', array('baba', 'baba'), $this->standard_plural_locale ) );
		$this->assertEquals( array(0 => array('is_equal' => 'error')),
			$this->with_equal->check( 'baba', 'babas', array('babas', 'babas'), $this->standard_plural_locale ) );
	}

	/**
	 * If a locale has no plural forms, we should check only against the plural original, since
	 * it probably contains the placeholders
	 */
	function test_check_no_plural_forms_locales() {
		$no_plural_locale = new GP_Locale;
		$no_plural_locale->nplurals = 1;
		$no_plural_locale->plural_expression = '0';
		$this->assertEquals( null,
			$this->with_equal->check( 'baba', 'babas', array('babas'), $no_plural_locale ) );
		$this->assertEquals( array(0 => array('is_equal' => 'error')),
			$this->with_equal->check( 'baba', 'babas', array('baba'), $no_plural_locale ) );
		$this->assertEquals( array(0 => array('is_equal' => 'error')),
			$this->with_equal->check( 'baba', 'babas', array('xxx'), $no_plural_locale ) );

	}
}
