<?php
require_once('init.php');

class GP_Test_Builtin_Translation_Warnings extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->w = new GP_Builtin_Translation_Warnings;
		$this->l = $this->factory->locale->create();
		$this->longer_than_20 = 'The little boy hid behind the counter and then came the wizard of all green wizards!';
		$this->shorter_than_5 = 'Boom';
	}

	function _assertWarning( $assert, $warning, $original, $translation, $locale = null ) {
		if ( is_null( $locale ) ) $locale = $this->l;
		$method = "warning_$warning";
		$this->$assert( true, $this->w->$method( $original, $translation, $locale ) );
	}

	function assertHasWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertNotSame', $warning, $original, $translation, $locale );
	}

	function assertNoWarnings( $warning, $original, $translation, $locale = null ) {
		$this->_assertWarning( 'assertSame', $warning, $original, $translation, $locale );
	}


	function test_length() {
		$this->assertNoWarnings( 'length', $this->longer_than_20, $this->longer_than_20 );
		$this->assertHasWarnings( 'length', $this->longer_than_20, $this->shorter_than_5 );
	}

	function test_length_exclude() {
		$w_without_locale = new GP_Builtin_Translation_Warnings;
		$w_without_locale->length_exclude_languages = array( $this->l->slug );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->longer_than_20, $this->l ) );
		$this->assertSame( true, $w_without_locale->warning_length( $this->longer_than_20, $this->shorter_than_5, $this->l ) );
	}

	function test_tags() {
		$this->assertNoWarnings( 'tags', 'Baba', 'Баба' );
		$this->assertNoWarnings( 'tags', '<a href="%s">Baba</a>', '<a href="%s">Баба</a>' );
		$this->assertNoWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" title="Блимп!">Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!">Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="%s" x>Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a href="%s" title="Blimp!">Baba</a>', '<a href="javascript:%s" title="Блимп!" target="_blank">Баба</a>' );
		$this->assertHasWarnings( 'tags', '<a>Baba</a>', '</a>Баба<a>' );
	}

	function test_add_all() {
		$warnings = $this->getMock( 'GP_Translation_Warnings' );
		// we check for the number of warnings, because PHPUnit doesn't allow
		// us to check if each argument is a callable
		$warnings->expects( $this->exactly( 4 ) )->method( 'add' )->will( $this->returnValue( true ) );
		$this->w->add_all( $warnings );
	}

	function test_placeholders() {
		$this->assertHasWarnings( 'placeholders', '%s baba', 'баба' );
		$this->assertHasWarnings( 'placeholders', '%s baba', '% баба' );
		$this->assertNoWarnings( 'placeholders', '%s baba', '%s баба' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%s baba', 'баба %s' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba %2$s dyado', '%1$sбабадядо%2$s' );
		$this->assertHasWarnings( 'placeholders', '% baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '% baba', '% баба' );
		$this->assertHasWarnings( 'placeholders', '%ququ baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '%ququ baba', '%ququ баба' );
		$this->assertHasWarnings( 'placeholders', '%1$s baba', 'баба' );
		$this->assertNoWarnings( 'placeholders', '%1$s baba', '%1$s баба' );
		$this->assertNoWarnings( 'placeholders', '%sHome%s', '%sНачало%s' );
	}

	function test_both_begin_end_on_newlines() {
		$this->assertHasWarnings( 'both_begin_end_on_newlines', "baba\n", "baba" );
		$this->assertHasWarnings( 'both_begin_end_on_newlines', "baba", "baba\n" );
		$this->assertNoWarnings( 'both_begin_end_on_newlines', "baba", "baba" );
		$this->assertNoWarnings( 'both_begin_end_on_newlines', "baba\n", "baba\n" );
		$this->assertHasWarnings( 'both_begin_end_on_newlines', "\nbaba", "baba" );
		$this->assertHasWarnings( 'both_begin_end_on_newlines', "baba", "\nbaba" );
		$this->assertNoWarnings( 'both_begin_end_on_newlines', "\nbaba", "\nbaba" );
	}
}
