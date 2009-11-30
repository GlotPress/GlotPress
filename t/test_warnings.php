<?php
require_once('init.php');

class GP_Test_Translation_Warnings extends GP_UnitTestCase {
	function setUp() {
		$this->w = new GP_Translation_Warnings;
		$this->is_baba = create_function('$o, $t, $l', 'return $t == "баба"? true : "The translation is not baba!";');
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
	
	function test_test_entry() {
		$this->w->add( 'is_baba', $this->is_baba );
		$baba_entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array('баба', 'баби')));
		$locale = GP_Locales::by_slug( 'bg' );
		$this->assertEquals( array(0 => array(), 1 => array('is_baba' => 'The translation is not baba!')), $this->w->test_entry( $baba_entry, $locale ) );
	}
}