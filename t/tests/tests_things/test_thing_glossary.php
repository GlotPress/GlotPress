<?php

class GP_Test_Glossary extends GP_UnitTestCase {

	function test_empty_translation_set_id() {
		$glossary = GP::$glossary->create( array( 'translation_set_id' => '' ) );
		$verdict = $glossary->validate();

		$this->assertFalse( $verdict );
	}

	function test_reload() {
		global $gpdb;
		$root = GP::$glossary->create( array( 'translation_set_id' => '1', 'description' => 'original description' ) );
		$gpdb->update( $gpdb->glossaries, array( 'description' => 'New Description' ), array( 'id' => $root->id ) );
		$root->reload();
		$this->assertEquals( 'New Description', $root->description );
	}

	function test_by_set_id() {
		$glossary_1 = GP::$glossary->create_and_select( array( 'translation_set_id' => '1' ) );
		$glossary_2 = GP::$glossary->create_and_select( array( 'translation_set_id' => '2' ) );
		$new = GP::$glossary->by_set_id( '1' );
		$this->assertEquals( $glossary_1, $new );
		$this->assertNotEquals( $glossary_2, $new );
	}
}