<?php
require_once('init.php');

class GP_Test_Strings extends GP_UnitTestCase {
	function test_gp_sanitize_for_url() {
		$this->assertEquals( 'baba', gp_sanitize_for_url( 'baba') );
		$this->assertEquals( 'baba-and-dyado', gp_sanitize_for_url( 'Baba and Dyado') );
		$this->assertEquals( 'баба-и-дядо', gp_sanitize_for_url( 'Баба и Дядо') );
		$this->assertEquals( 'баба-и-дядо', gp_sanitize_for_url( 'Баба 	и 	Дядо') );
		$this->assertEquals( 'mu', gp_sanitize_for_url( '/Mu#/') );
		$this->assertEquals( 'баба-дядо', gp_sanitize_for_url( 'Баба &amp; дядо') );
		$this->assertEquals( 'caca-и-цацабууу', gp_sanitize_for_url( 'Caca и цацаБУУУ') );
	}
}