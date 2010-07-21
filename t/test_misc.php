<?php
require_once('init.php');

class GP_Test_Misc extends GP_UnitTestCase {
	function test_gp_parity_factory() {
		$gen = gp_parity_factory();
		$concurrent = gp_parity_factory();
		$this->assertEquals( "even", $gen() );
		$this->assertEquals( "even", $concurrent() );
		$this->assertEquals( "odd", $gen() );
		$this->assertEquals( "even", $gen() );
		$this->assertEquals( "odd", $concurrent() );
		$this->assertEquals( "even", $concurrent() );
		$this->assertEquals( "odd", $gen() );
		$this->assertEquals( "odd", $concurrent() );
	}
	
	function test_gp_array_flatten() {
	    $this->assertEquals( array(), gp_array_flatten( array() ) );
        $this->assertEquals( array( 1, 2, 3 ), gp_array_flatten( array( 1, array( 2, 3 ) ) ) );
        $this->assertEquals( array( 1, 2, 3, 4, 5, 6, 7 ), gp_array_flatten( array( 1, array( 2, array( 3, 4 ), 5, ), 6, array( 7 ) ) ) );
	}
	
	function test_gp_array_zip() {
		$this->assertEquals( array(), gp_array_zip() );
		$this->assertEquals( array(), gp_array_zip( array() ) );
		$this->assertEquals( array(), gp_array_zip( array(), array(), array() ) );
		$this->assertEquals( array( array('baba') ), gp_array_zip( array('baba') ) );
		$this->assertEquals( array(), gp_array_zip( array('baba'), array(), array() ) );
		$this->assertEquals( array( array('baba', 'dyado') ), gp_array_zip( array('baba'), array('dyado') ) );
		$this->assertEquals( array( array('baba', 'dyado') ), gp_array_zip( array('baba', 'boom'), array('dyado') ) );
		$this->assertEquals( array( array( array('baba'), 'dyado') ), gp_array_zip( array( array('baba'), 'boom'), array('dyado') ) );
	}
	
	function test_gp_array_any() {
		$this->assertEquals( false, gp_array_any( 'intval', array( 0 ) ) );
		$this->assertEquals( false, gp_array_any( returner(false), array( 1, 2, 3, 4 ) ) );
		$this->assertEquals( false, gp_array_any( returner(true), array() ) );
		$this->assertEquals( true, gp_array_any( returner(true), array( 1, 2, 3, 4 ) ) );
		$this->assertEquals( true, gp_array_any( returner('$x', '$x % 2'), array( 1, 2, 3, 4 ) ) );
	}
	
	function test_gp_object_has_var_returs_true_if_var_is_null() {
		$this->assertTrue( gp_object_has_var( (object)array( 'baba' => null), 'baba' ) );
	}
}