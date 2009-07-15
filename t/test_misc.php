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
}