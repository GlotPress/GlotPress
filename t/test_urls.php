<?php
require_once('init.php');

class GP_Test_Urls extends GP_UnitTestCase {
	function GP_Test_Urls() {
		$this->UnitTestCase('URLs test');
	}
	
	function setUp() {
        parent::setUp();
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
	
	function test_gp_url_join() {
		$this->assertEqual( 'baba', gp_url_join( 'baba') );
		$this->assertEqual( 'baba/dyado', gp_url_join( 'baba', 'dyado' ) );
		$this->assertEqual( 'baba/dyado', gp_url_join( 'baba/', '/dyado' ) );
		$this->assertEqual( '/baba/dyado/', gp_url_join( '/baba//', '/dyado/' ) );
		$this->assertEqual( '/baba/', gp_url_join( '/baba/', '/' ) );
		$this->assertEqual( '/baba/', gp_url_join( '/baba/', '//' ) );
		$this->assertEqual( '/baba/', gp_url_join( '/', '/baba/' ) );
		$this->assertEqual( '/baba/', gp_url_join( '/', '/baba/' ) );
		$this->assertEqual( '/baba/', gp_url_join( '//', '/baba/' ) );
		$this->assertEqual( '/', gp_url_join( '/', '/' ) );
		$this->assertEqual( '/', gp_url_join( '///', '///' ) );
	}
	
	function test_gp_url_with_arrays() {
		$this->assertEqual( 'baba', gp_url_join( array( 'baba' ) ));
		$this->assertEqual( 'baba/dyado', gp_url_join( array( 'baba', 'dyado' ) ));
		// test some shortcuts -- arrays instead of gp_url_join calls
 		$this->assertEqual( gp_url_join( gp_url_project( '/x' ), 'import-originals' ), gp_url_project( '/x', 'import-originals' ));
		$this->assertEqual( gp_url_project( '/x', gp_url_join( 'slug', 'slugslug', 'import-translations' ) ), gp_url_project( '/x', array( 'slug', 'slugslug', 'import-translations' ) ) );
	}
}
