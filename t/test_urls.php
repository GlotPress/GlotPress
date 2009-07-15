<?php
require_once('init.php');

class GP_Test_Urls extends GP_UnitTestCase {
	
	function setUp() {
        parent::setUp();
		$this->url = '/gp/';
		gp_update_option( 'uri', 'http://example.org'.$this->url );
	}

	function test_gp_url() {
		$this->assertEquals( $this->url . 'baba', gp_url( 'baba' ) );
		$this->assertEquals( $this->url . 'baba', gp_url( 'baba', '' ) );
		$this->assertEquals( $this->url . 'baba', gp_url( 'baba', array() ) );
		$this->assertEquals( $this->url . '?a=b', gp_url( '', 'a=b' ) );
		$this->assertEquals( $this->url . '?a=b', gp_url( '', '?a=b' ) );
		$this->assertEquals( $this->url . '?a=b', gp_url( '', array('a' => 'b') ) );
		$this->assertEquals( $this->url . '?a=b&b=c', gp_url( '', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->url . 'baba?a=b&b=c', gp_url( 'baba', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->url . 'baba/wink?a=b&b=c', gp_url( '/baba/wink', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->url . 'baba/wink?a=a%26b&b=c', gp_url( '/baba/wink', array('a' => 'a&b', 'b' => 'c') ) );
	}
	
	function test_gp_url_join() {
		$this->assertEquals( 'baba', gp_url_join( 'baba') );
		$this->assertEquals( 'baba/dyado', gp_url_join( 'baba', 'dyado' ) );
		$this->assertEquals( 'baba/dyado', gp_url_join( 'baba/', '/dyado' ) );
		$this->assertEquals( '/baba/dyado/', gp_url_join( '/baba//', '/dyado/' ) );
		$this->assertEquals( '/baba/', gp_url_join( '/baba/', '/' ) );
		$this->assertEquals( '/baba/', gp_url_join( '/baba/', '//' ) );
		$this->assertEquals( '/baba/', gp_url_join( '/', '/baba/' ) );
		$this->assertEquals( '/baba/', gp_url_join( '/', '/baba/' ) );
		$this->assertEquals( '/baba/', gp_url_join( '//', '/baba/' ) );
		$this->assertEquals( '/', gp_url_join( '/', '/' ) );
		$this->assertEquals( '/', gp_url_join( '///', '///' ) );
	}
	
	function test_gp_url_with_arrays() {
		$this->assertEquals( 'baba', gp_url_join( array( 'baba' ) ));
		$this->assertEquals( 'baba/dyado', gp_url_join( array( 'baba', 'dyado' ) ));
		// test some shortcuts -- arrays instead of gp_url_join calls
 		$this->assertEquals( gp_url_join( gp_url_project( '/x' ), 'import-originals' ), gp_url_project( '/x', 'import-originals' ));
		$this->assertEquals( gp_url_project( '/x', gp_url_join( 'slug', 'slugslug', 'import-translations' ) ), gp_url_project( '/x', array( 'slug', 'slugslug', 'import-translations' ) ) );
	}
}
