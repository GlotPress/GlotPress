<?php
require_once('init.php');

class GP_Test_Format_RRC extends GP_UnitTestCase {
    function GP_Test_Format_RRC() {
		$this->rrc = new GP_Format_RRC;
		$this->entries = array(
			array('WITH_LATIN1', 'for', 'für'),
			array('WITH_UNICODE_ESCAPES', 'baba', 'баба'),
			array('WITH_SLASHES', "twinkle\ntwinkle", "Twinkle,\nTwinkle,litle\tstar!"),
			array('MULTIPLE[0]', 'Off', 'Off'),
			array('MULTIPLE[1]', '1', '1'),
			array('MULTIPLE[2]', '2', '2'),
			array('MULTIPLE[3]', 'brun', "brun!\nbrun!"),
			array('UNTRANSLATED', 'English string', ''),
			array('MULTIPLE_UNTRANSLATED[0]', 'English string#0', 'Partly'),
			array('MULTIPLE_UNTRANSLATED[1]', 'English string#1', ''),
		);
	}
	
	function test_export() {
		$entries_for_export = array();
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$entries_for_export[] = (object)array(
				'context' => $context,
				'singular' => $original,
				'translations' => $translation? array($translation) : array(),
			);
		}
		$this->assertDiscardWhitespace(
				file_get_contents( 'data/translation-exported.rrc' ),
				$this->rrc->print_exported_file( 'project', 'locale', 'translation_set', $entries_for_export )
		);
	}
		
	function test_read_originals() {
		$translations = $this->rrc->read_originals_from_file( 'data/originals.rrc' );
				
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
		$translations = $this->rrc->read_translations_from_file( 'data/translation.rrc', (object)array( 'id' => 2 ) );
		foreach( $this->entries as $sample ) {
			list( $context, $original, $translation ) = $sample;
			$this->assertEquals( $translation, $translations->translate( $original, $context ) );
		}
	}	
}
