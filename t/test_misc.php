<?php
require_once('init.php');

class GP_Test_Misc extends GP_UnitTestCase {
	function GP_Test_Misc() {
		$this->UnitTestCase('Misc functions tests');
	}
	
	function test_gp_parity_factory() {
		$gen = gp_parity_factory();
		$concurrent = gp_parity_factory();
		$this->assertEqual( "even", $gen() );
		$this->assertEqual( "even", $concurrent() );
		$this->assertEqual( "odd", $gen() );
		$this->assertEqual( "even", $gen() );
		$this->assertEqual( "odd", $concurrent() );
		$this->assertEqual( "even", $concurrent() );
		$this->assertEqual( "odd", $gen() );
		$this->assertEqual( "odd", $concurrent() );
	}
	
	function test_gp_array_flatten() {
	    $this->assertEqual( array(), gp_array_flatten( array() ) );
        $this->assertEqual( array( 1, 2, 3 ), gp_array_flatten( array( 1, array( 2, 3 ) ) ) );
        $this->assertEqual( array( 1, 2, 3, 4, 5, 6, 7 ), gp_array_flatten( array( 1, array( 2, array( 3, 4 ), 5, ), 6, array( 7 ) ) ) );
	}
}