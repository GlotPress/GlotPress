<?php
require_once('init.php');

class GP_Test_Translation_Warnings extends GP_UnitTestCase {
	function setUp() {
		$this->w = new GP_Translation_Warnings;
		$this->is_baba = create_function('$o, $t, $l', 'return $t == "баба"? true : "error";');
		$this->is_equal = create_function('$o, $t, $l', 'return $t == $o? true : "error";');
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
		$locale = GP_Locales::by_slug( 'bg' );
		$this->assertEquals( array(
			1 => array('is_baba' => 'error')),
			$this->w->check( 'baba', null, array('баба', 'баби'), $locale ) );
		$this->assertEquals( null,
			$this->w->check( 'baba', null, array('баба', 'баба', 'баба'), $locale ) );
		// for the plural form, corresponding to the number 1 check only against the singular, not also against the plural
		$with_equal = new GP_Translation_Warnings;
		$with_equal->add( 'is_equal', $this->is_equal );
		$this->assertEquals( null,
			$with_equal->check( 'baba', 'babas', array('baba', 'babas'), $locale ) );
		$this->assertEquals( array(1 => array('is_equal' => 'error')),
			$with_equal->check( 'baba', 'babas', array('baba', 'baba'), $locale ) );
		$this->assertEquals( array(0 => array('is_equal' => 'error')),
			$with_equal->check( 'baba', 'babas', array('babas', 'babas'), $locale ) );
	}
}