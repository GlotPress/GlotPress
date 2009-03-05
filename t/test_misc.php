<?php
require_once('init.php');

class GP_Test_Misc extends UnitTestCase {
	function GP_Test_Misc() {
		$this->UnitTestCase('Misc functions tests');
	}
	
	function test_gp_populate_notices() {
		global $gp_redirect_notices;
		$_GET = array();
		$_GET['_gp_notice_'] = 'baba';
		$_GET['_gp_notice_'] = '';
		$_GET['_gp_notice_notice'] = 'Here is a notice';
		$_GET['_gp_notice_error'] = 'Here is an error';
		$_GET['_gp_notice_problem'] = '';
		gp_populate_notices();
		$this->assertEqual( array( 'notice' => 'Here is a notice', 'error' => 'Here is an error', 'problem' => ''), $gp_redirect_notices );
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