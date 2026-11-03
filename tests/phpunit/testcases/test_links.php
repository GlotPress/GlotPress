<?php

class GP_Test_Links extends GP_UnitTestCase {

	function test_gp_link_get_simple() {
		$this->assertEquals( '<a href="http://dir.bg/">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba' ) );
	}

	function test_gp_link_get_attributes() {
		$this->assertEquals( '<a href="http://dir.bg/" target="_blank" class="edit">Baba</a>',
			gp_link_get( 'http://dir.bg/', 'Baba', array( 'target' => '_blank', 'class' => 'edit' ) ) );
	}

	function test_gp_link_get_should_put_the_before_attribute_before_the_link() {
		$this->assertEquals( 'x<a href="http://dir.bg/">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba', array( 'before' => 'x' ) ) );
	}

	function test_gp_link_get_should_put_the_after_attribute_after_the_link() {
		$this->assertEquals( '<a href="http://dir.bg/">Baba</a>x', gp_link_get( 'http://dir.bg/', 'Baba', array( 'after' => 'x' ) ) );
	}

	function test_gp_link_get_should_put_the_before_and_after_attributes_before_and_after_the_link() {
		$this->assertEquals( 'a<a href="http://dir.bg/">Baba</a>b', gp_link_get( 'http://dir.bg/', 'Baba', array( 'before' => 'a', 'after' => 'b' ) ) );
	}

	function test_gp_link_get_escape() {
		// The link text is escaped so the output is safe by default.
		$this->assertEquals( '<a href="http://dir.bg/">Baba &amp; Dyado</a>', gp_link_get( 'http://dir.bg/', 'Baba & Dyado' ) );
		$this->assertEquals( '<a href="http://dir.bg/">&lt;b&gt;x&lt;/b&gt;</a>', gp_link_get( 'http://dir.bg/', '<b>x</b>' ) );
		// Existing entities in the text are preserved, not double-encoded.
		$this->assertEquals( '<a href="http://dir.bg/">&larr; Prev</a>', gp_link_get( 'http://dir.bg/', '&larr; Prev' ) );
		$this->assertEquals( '<a href="http://dir.bg/?x=5&#038;y=11">Baba</a>', gp_link_get( 'http://dir.bg/?x=5&y=11', 'Baba' ) );
		$this->assertEquals( '<a href="http://dir.bg/" a="&quot;">Baba</a>', gp_link_get( 'http://dir.bg/', 'Baba', array( 'a' => '"') ) );
	}
}
