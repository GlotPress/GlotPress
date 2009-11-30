<?php
require_once('init.php');

class GP_Test_Builtin_Translation_Warnings extends GP_UnitTestCase {
	
	function setUp() {
		$this->w = new GP_Builtin_Translation_Warnings;
		$this->bg = GP_Locales::by_slug( 'bg' );
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}
	
	function test_length() {
		$this->assertSame( true, $this->w->warning_length( $this->longer_than_20, $this->longer_than_20, $this->bg ) );
		$this->assertNotSame( true, $this->w->warning_length( $this->longer_than_20, $this->shorter_than_5, $this->bg ) );
	}
	
	function test_length_exclude() {
		$w_without_bg = new GP_Builtin_Translation_Warnings;
		$w_without_bg->length_exclude_languages = array( 'bg' );
		$this->assertSame( true, $w_without_bg->warning_length( $this->longer_than_20, $this->longer_than_20, $this->bg ) );
		$this->assertSame( true, $w_without_bg->warning_length( $this->longer_than_20, $this->shorter_than_5, $this->bg ) );
	}
	
	function test_tags() {
		$this->assertSame( true, $this->w->warning_tags( '<a href="%s">Baba</a>', '<a href="%s">Баба</a>', $this->bg ) );
		$this->assertSame( true, $this->w->warning_tags( '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" title="Блимп!">Баба</a>', $this->bg ) );
		$this->assertNotSame( true, $this->w->warning_tags( '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!">Баба</a>', $this->bg ) );
		$this->assertNotSame( true, $this->w->warning_tags( '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" x>Баба</a>', $this->bg ) );
		$this->assertNotSame( true, $this->w->warning_tags( '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!" target="_blank">Баба</a>', $this->bg ) );
		$this->assertNotSame( true, $this->w->warning_tags( '<a>Baba</a>', '</a>Баба<a>', $this->bg ) );
		$this->assertSame( true, $this->w->warning_tags( 'Baba', 'Баба', $this->bg ) );
	}
	
	function test_add_all() {
		$warnings = $this->getMock( 'GP_Translation_Warnings' );
		$warnings->expects( $this->exactly( 2 ) )->method( 'add' )->will( $this->returnValue( true ) );
		$this->w->add_all( $warnings );
	}
}