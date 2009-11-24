<?php
require_once('init.php');

class GP_Test_Translation_Warnings extends GP_UnitTestCase {
	function setUp() {
		$this->w = new GP_Translation_Warnings;
	}
	
	function test_add() {
		$this->w->add( 'is_baba', create_function('$e', 'return array($e->singular == "baba", "The singluar is not baba!");') );
		$this->w->add( 'is_dyado', create_function('$e', 'return array($e->singular == "dyado", "The singluar is not dyado!");'), 'error' );
		$this->assertEquals( true, $this->w->has( 'is_baba' ) );
		$this->assertEquals( false, $this->w->has( 'is_baba', 'error' ) );
		$this->assertEquals( true, $this->w->has( 'is_dyado', 'error' ) );
		$this->assertEquals( false, $this->w->has( 'is_dyado' ) );
	}
	
	function test_remove() {
		$this->w->add( 'is_baba', create_function('$e', 'return array($e->singular == "baba", "The singluar is not baba!");'), 'error' );
		$this->assertEquals( true, $this->w->has( 'is_baba', 'error' ) );
		$this->w->remove( 'is_baba', 'error' );
		$this->assertEquals( false, $this->w->has( 'is_baba', 'error' ) );
	}
	
	function test_test() {
		$this->w->add( 'is_baba', create_function('$e', 'return array($e->singular == "baba", "The singluar is not baba!");') );
		$this->w->add( 'is_dyado', create_function('$e', 'return array($e->singular == "dyado", "The singluar is not dyado!");'), 'error' );
		$baba_entry = new Translation_Entry(array('singular' => 'baba'));
		$dyado_entry = new Translation_Entry(array('singular' => 'dyado'));
		$this->assertEquals( array(), $this->w->test( $baba_entry ) );
		$this->assertEquals( array(array('is_baba', "The singluar is not baba!")), $this->w->test( $dyado_entry ) );
		$this->assertEquals( array(), $this->w->test( $dyado_entry, 'error' ) );
		$this->assertEquals( array(array('is_dyado', "The singluar is not dyado!")), $this->w->test( $baba_entry, 'error' ) );
	}
}