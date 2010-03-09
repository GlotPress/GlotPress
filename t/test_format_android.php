<?php
require_once('init.php');

class GP_Test_Format_Android extends GP_UnitTestCase {
    function GP_Test_Format_Android() {
		$this->android = new GP_Format_Android;
	}
	
	function test_export() {
		$entries = array(
			(object)array(
				'singular' => 'normal_string',
				'translations' => array('Normal String'),
			),
			(object)array(
				'singular' => 'with_a_quote',
				'translations' => array('I\'m with a quote'),
			),
			(object)array(
				'singular' => 'with_newlines',
				'translations' => array("I\nhave\nnew\nlines"),
			),		
			(object)array(
				'singular' => 'with_a_doublequote',
				'translations' => array('I have a double "quote"'),
			),
			(object)array(
				'singular' => 'with_utf8',
				'translations' => array('баба ми омеси питка'),
			),
			(object)array(
				'singular' => 'with_lt',
				'translations' => array('ти < аз'),
			),
			(object)array(
				'singular' => 'with_gt',
				'translations' => array('аз > ти'),
			),
			(object)array(
				'singular' => 'with_amps',
				'translations' => array('аз & ти не сме &amp;'),
			),			
		);
		$this->assertEquals( file_get_contents( 'data/sample.android.xml' ), $this->android->print_exported_file( 'project', 'locale', 'translation_set', $entries ) );
	}
	
	function test_import() {
		$translations = $this->android->read_translations_from_file( 'data/sample.android.xml' );
		$entries = $translations->entries;
		$this->assertEquals( 'Normal String', $entries['normal_string']->translations[0] );
		$this->assertEquals( true, gp_endswith( $entries['normal_string']->extracted_comments, 'Normal String' ) );
		$this->assertEquals( 'I\'m with a quote', $entries['with_a_quote']->translations[0] );
		$this->assertEquals( "I\nhave\nnew\nlines", $entries['with_newlines']->translations[0] );
		$this->assertEquals( 'I have a double "quote"', $entries['with_a_doublequote']->translations[0] );
		$this->assertEquals( 'баба ми омеси питка', $entries['with_utf8']->translations[0] );
		$this->assertEquals( 'ти < аз', $entries['with_lt']->translations[0] );
		$this->assertEquals( 'аз > ти', $entries['with_gt']->translations[0] );
		$this->assertEquals( 'аз & ти не сме &amp;', $entries['with_amps']->translations[0] );
	}
}
