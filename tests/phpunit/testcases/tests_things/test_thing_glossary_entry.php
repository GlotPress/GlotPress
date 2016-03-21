<?php

class GP_Test_Glossary_Entry extends GP_UnitTestCase {

	function test_empty_glossary_id() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_term() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => '', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_part_of_speech() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => '', 'last_edited_by' =>'1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_negative_last_edited_by() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'tern', 'part_of_speech' => 'verb', 'last_edited_by' =>'-1' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_empty_last_edited_by() {
		$glossary_entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'tern', 'part_of_speech' => 'verb', 'last_edited_by' =>'0' ) );
		$verdict = $glossary_entry->validate();

		$this->assertFalse( $verdict );
	}

	function test_by_glossary_id() {
		$glossary_entry_1 = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$glossary_entry_2 = GP::$glossary_entry->create( array( 'glossary_id' => '2', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		$new = GP::$glossary_entry->by_glossary_id( '1' );
		$this->assertEquals( array( $glossary_entry_1 ), $new );
		$this->assertNotEquals( array( $glossary_entry_2 ), $new );
	}

	function test_part_of_speech_array_set() {
		$this->assertCount( 9, GP::$glossary_entry->parts_of_speech );
		$this->assertArrayHasKey( 'noun', GP::$glossary_entry->parts_of_speech );
	}
	
	function test_delete() {
		$entry = GP::$glossary_entry->create( array( 'glossary_id' => '1', 'term' => 'term', 'part_of_speech' => 'verb', 'last_edited_by' =>'1' ) );
		
		$pre_delete = GP::$glossary_entry->find_one( array( 'id' => $entry->id ) );

		$entry->delete();
		
		$post_delete = GP::$glossary_entry->find_one( array( 'id' => $entry->id ) );

		$this->assertFalse( empty( $pre_delete ) );
		$this->assertNotEquals( $pre_delete, $post_delete );
	}

}