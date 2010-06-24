<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Thing_Translation_set extends GP_UnitTestCase {
	function test_copy_translations_from_should_copy_into_empty_set() {
		$source_set = $this->factory->translation_set->create();
		$destination_set = $this->factory->translation_set->create();
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $source_set->id ) );
		$destination_set->copy_translations_from( $source_set->id );
		$destination_set_translations = GP::$translation->find( array( 'translation_set_id' => $destination_set->id ) );
		$this->assertEquals( 1, count( $destination_set_translations ) );
		$this->assertEqualFields( $destination_set_translations[0],
			array( 'translation_0' => $translation->translation_0, 'translation_set_id' => $destination_set->id, 'original_id' => $translation->original_id )
		);
	}
}
