<?php
require_once('init.php');

class GP_Test_Format_Android extends GP_UnitTestCase {
    function GP_Test_Format_Android() {
		$this->android = new GP_Format_Android;
		$this->entries = array(
			array('normal_string', 'Normal String', 'Just A Normal String'),
			array('with_a_quote', 'I\'m with a quote', 'I\'m with a quote'),
			array('with_newlines', "new\nlines", "I\nhave\nnew\nlines"),
			array('with_doublequotes', 'double "quotes"', 'I have double "quotes"'),
			array('with_utf8', 'питка', 'баба ми омеси питка'),
			array('with_lt', 'you < me', 'ти < аз'),
			array('with_gt', 'me > you', "аз > ти"),
			array('with_amps', 'me & you are not &amp;', 'аз & ти не сме &amp;'),
		);		
	}
	
	function test_export() {
		$entries_for_export = array();
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$entries_for_export[] = (object)array(
				'context' => $context,
				'singular' => $original,
				'translations' => array($translation),
			);
		}
		$this->assertEquals( file_get_contents( 'data/translation.android.xml' ), $this->android->print_exported_file( 'p', 'l', 't', $entries_for_export ) );
	}
	
	
	function test_read_originals() {
		$translations = $this->android->read_originals_from_file( 'data/originals.android.xml' );
				
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$translatable_entry = new Translation_Entry( array('singular' => $original, 'context' => $context) );
			$entry = $translations->translate_entry( $translatable_entry );
			$this->assertEquals( $original, $entry->singular );
			$this->assertEquals( $context, $entry->context );
		}
	}
	
	function test_read_translations() {
		$stubbed_originals = array();
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$stubbed_originals[] = new GP_Original( array( 'singular' => $original, 'context' => $context ) );
		}
		GP::$original = $this->getMock( 'GP_Original', array('by_project_id') );
		GP::$original->expects( $this->once() )
					->method( 'by_project_id' )
					->with( $this->equalTo(2) )
					->will( $this->returnValue($stubbed_originals) );
		$translations = $this->android->read_translations_from_file( 'data/translation.android.xml', (object)array( 'id' => 2 ) );
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}
	
}
