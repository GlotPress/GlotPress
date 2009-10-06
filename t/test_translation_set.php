<?php
require_once('init.php');

class GP_Test_Translation_Set extends GP_UnitTestCase {
	function test_export_po() {
		$set = GP::$translation_set->create(array('name' => 'Set', 'slug' => 'set', 'project_id' => 1, 'locale' => 'bg'));
		GP::$translation->create(array('original_id' => 1, 'translation_set_id' => $set->id, 'translation_0' => 'Baba',
			'user_id' => 1, 'status' => 'current'));
		GP::$translation->create(array('original_id' => 2, 'translation_set_id' => $set->id, 'translation_0' => 'Dudu',
			'user_id' => 1, 'status' => 'waiting'));
		$po_file = $this->temp_filename();
		file_put_contents( $po_file, $set->export_as_po() );
		$po = new PO;
		$po->import_from_file( $po_file );
		$translated = 0;
		foreach( $po->entries as $entry ) {
			if ( isset( $entry->translations[0] ) && $entry->translations[0] ) $translated += 1;
		}
		$this->assertEquals( 1, $translated );
	}
}