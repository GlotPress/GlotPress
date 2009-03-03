<?php
require_once('init.php');

class GP_Test_Urls extends UnitTestCase {
	function GP_Test_Urls() {
		$this->UnitTestCase('URLs test');
	}
	
	function setUp() {
		$this->url = 'http://example.org/gp/';
		gp_update_option( 'uri', $this->url );
	}

	function test_gp_url() {
		$this->assertEqual( $this->url . 'baba', gp_url( 'baba' ) );
		$this->assertEqual( $this->url . 'baba', gp_url( 'baba', '' ) );
		$this->assertEqual( $this->url . 'baba', gp_url( 'baba', array() ) );
		$this->assertEqual( $this->url . '?a=b', gp_url( '', 'a=b' ) );
		$this->assertEqual( $this->url . '?a=b', gp_url( '', '?a=b' ) );
		$this->assertEqual( $this->url . '?a=b', gp_url( '', array('a' => 'b') ) );
		$this->assertEqual( $this->url . '?a=b&b=c', gp_url( '', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEqual( $this->url . 'baba?a=b&b=c', gp_url( 'baba', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEqual( $this->url . 'baba/wink?a=b&b=c', gp_url( '/baba/wink', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEqual( $this->url . 'baba/wink?a=a%26b&b=c', gp_url( '/baba/wink', array('a' => 'a&b', 'b' => 'c') ) );
	}
}
