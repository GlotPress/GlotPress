<?php
require_once('init.php');

class GP_Test_Format_RRC extends GP_UnitTestCase {
    function GP_Test_Format_RRC() {
		$this->rrc = new GP_Format_RRC;
	}
	
	function test_export() {
		$entries = array(
			(object)array(
				'singular' => 'WITH_LATIN1',
				'translations' => array('für'),
			),
			(object)array(
				'singular' => 'WITH_UNICODE_ESCAPES',
				'translations' => array('баба'),
			),
			(object)array(
				'singular' => 'WITH_SLASHES',
				'translations' => array("Twinkle,\nTwinkle,litle\tstar!"),
			),		
			(object)array(
				'singular' => 'MULTIPLE[0]',
				'translations' => array('Off'),
			),
			(object)array(
				'singular' => 'MULTIPLE[1]',
				'translations' => array('1'),
			),
			(object)array(
				'singular' => 'MULTIPLE[2]',
				'translations' => array('2'),
			),
			(object)array(
				'singular' => 'MULTIPLE[3]',
				'translations' => array("brun!\nbrun!"),
			),
		);
		$this->assertEquals( file_get_contents( 'data/sample.rrc' ), $this->rrc->print_exported_file( 'project', 'locale', 'translation_set', $entries ) );
	}
	
	function test_import() {
		$translations = $this->rrc->read_translations_from_file( 'data/sample.rrc' );
		$entries = $translations->entries;
		$this->assertEquals( 'für', $entries['WITH_LATIN1']->translations[0] );
		$this->assertEquals( true, gp_endswith( $entries['WITH_LATIN1']->extracted_comments, 'für' ) );
		$this->assertEquals( 'баба', $entries['WITH_UNICODE_ESCAPES']->translations[0] );
		$this->assertEquals( "Twinkle,\nTwinkle,litle\tstar!", $entries['WITH_SLASHES']->translations[0] );
		$this->assertEquals( "Off", $entries['MULTIPLE[0]']->translations[0] );
		$this->assertEquals( "1", $entries['MULTIPLE[1]']->translations[0] );
		$this->assertEquals( "2", $entries['MULTIPLE[2]']->translations[0] );
		$this->assertEquals( "brun!\nbrun!", $entries['MULTIPLE[3]']->translations[0] );
	}
}
