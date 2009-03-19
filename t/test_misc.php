<?php
require_once('init.php');

class GP_Test_Misc extends UnitTestCase {
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
}