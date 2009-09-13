<?php
require_once('init.php');

class GP_Test_Links extends GP_UnitTestCase {
	
	function test_gp_link_get_simple() {
		$this->assertEquals( '<a href="http://dir.bg/">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba' ) );
	}
	
	function test_gp_link_get_attributes() {
		$this->assertEquals( '<a href="http://dir.bg/" target="_blank" class="edit">Baba</a>',
			gp_link_get( 'http://dir.bg/', 'Baba', array( 'target' => '_blank', 'class' => 'edit' ) ) );
	}
	
	function test_gp_link_get_before_after() {
		$this->assertEquals( 'x<a href="http://dir.bg/">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba', array( 'before' => 'x' ) ) );
		$this->assertEquals( '<a href="http://dir.bg/">Baba</a>x', gp_link_get( 'http://dir.bg/', 'Baba', array( 'after' => 'x' ) ) );
		$this->assertEquals( 'a<a href="http://dir.bg/">Baba</a>b', gp_link_get( 'http://dir.bg/', 'Baba', array( 'before' => 'a', 'after' => 'b' ) ) );
	}
	
	function test_gp_link_get_escape() {
		$this->assertEquals( '<a href="http://dir.bg/">Baba & Dyado</a>', gp_link_get( 'http://dir.bg/', 'Baba & Dyado' ) );
		// clean_url() is too restrictive, so it isn't called
		//$this->assertEquals( '<a href="http://dir.bg/?x=5&#038;y=11">Baba</a>', gp_link_get( 'http://dir.bg/?x=5&y=11', 'Baba' ) );
		$this->assertEquals( '<a href="http://dir.bg/" a="&quot;">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba', array( 'a' => '"') ) );
	}
}