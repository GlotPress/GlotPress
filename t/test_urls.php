<?php
require_once('init.php');

class GP_Test_Urls extends GP_UnitTestCase {
	
	function setUp() {
	    $this->sub_dir = '/gp/';
	    $this->url = 'http://example.org' . $this->sub_dir;
		parent::setUp();
	}
	
	function tearDown() {
	    parent::tearDown();	    
	}

	function test_gp_url() {
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba' ) );
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba', '' ) );
		$this->assertEquals( $this->sub_dir . 'baba', gp_url( 'baba', array() ) );
		$this->assertEquals( $this->sub_dir . '?a=b', gp_url( '', 'a=b' ) );
		$this->assertEquals( $this->sub_dir . '?a=b', gp_url( '', '?a=b' ) );
		$this->assertEquals( $this->sub_dir . '?a=b', gp_url( '', array('a' => 'b') ) );
		$this->assertEquals( $this->sub_dir . '?a=b&b=c', gp_url( '', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->sub_dir . 'baba?a=b&b=c', gp_url( 'baba', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->sub_dir . 'baba/wink?a=b&b=c', gp_url( '/baba/wink', array('a' => 'b', 'b' => 'c') ) );
		$this->assertEquals( $this->sub_dir . 'baba/wink?a=a%26b&b=c', gp_url( '/baba/wink', array('a' => 'a&b', 'b' => 'c') ) );
	}
	
	function test_gp_url_join() {
		$this->assertEquals( 'baba', gp_url_join( 'baba' ) );
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
		// skip empty
		$this->assertEquals( 'a', gp_url_join( 'a', '' ) );
	}
	
	function test_gp_url_with_arrays() {
		$this->assertEquals( 'baba', gp_url_join( array( 'baba' ) ));
		$this->assertEquals( 'baba/dyado', gp_url_join( array( 'baba', 'dyado' ) ));
		// test some shortcuts -- arrays instead of gp_url_join calls
 		$this->assertEquals( gp_url_join( gp_url_project( '/x' ), 'import-originals' ), gp_url_project( '/x', 'import-originals' ));
		$this->assertEquals( gp_url_project( '/x', gp_url_join( 'slug', 'slugslug', 'import-translations' ) ), gp_url_project( '/x', array( 'slug', 'slugslug', 'import-translations' ) ) );
	}
}
