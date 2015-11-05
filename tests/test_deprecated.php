<?php

class GP_Test_Deprecated extends GP_UnitTestCase {

	function test_gp_parity_factory() {
		$gen        = gp_parity_factory();
		$concurrent = gp_parity_factory();

		$this->assertEquals( "odd", $gen() );
		$this->assertEquals( "odd", $concurrent() );
		$this->assertEquals( "even", $gen() );
		$this->assertEquals( "odd", $gen() );
		$this->assertEquals( "even", $concurrent() );
		$this->assertEquals( "odd", $concurrent() );
		$this->assertEquals( "even", $gen() );
		$this->assertEquals( "even", $concurrent() );
	}

}