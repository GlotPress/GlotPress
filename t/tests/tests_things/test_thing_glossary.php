<?php

class GP_Test_Glossary extends GP_UnitTestCase {

	function test_empty_translation_set_id() {
		$glossary = GP::$glossary->create_and_select( array( 'translation_set_id' => '' ) );
		$verdict = $glossary->validate();

		$this->assertFalse( $verdict );
	}

	function test_by_set_id() {
		$glossary_1 = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary_2 = GP::$glossary->create_and_select( array( 'translation_set_id' => '2' ) );
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertEquals( $glossary_1, $new );
		$this->assertNotEquals( $glossary_2, $new );
	}
}
