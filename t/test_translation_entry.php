<?php
require_once('init.php');

class GP_Test_Translation_Entry extends GP_UnitTestCase {

    function GP_Test_Translation_Entry() {

    }

    function test_create_entry() {
		// no singular => empty object
		$entry = new Translation_Entry();
		$this->assertNull($entry->singular);
		$this->assertNull($entry->plural);
		$this->assertFalse($entry->is_plural);
		// args -> members
		$entry = new Translation_Entry(array(
			'singular' => 'baba',
			'plural' => 'babas',
			'non_existant' => 'cookoo',
			'translations' => array('баба', 'баби'),
			'references' => 'should be array here',
			'flags' => 'baba',
		));
		$this->assertEqual('baba', $entry->singular);
		$this->assertEqual('babas', $entry->plural);
		$this->assertTrue($entry->is_plural);
		$this->assertFalse(isset($entry->non_existant));
		$this->assertEqual(array('баба', 'баби'), $entry->translations);
		$this->assertEqual(array(), $entry->references);
		$this->assertEqual(array(), $entry->flags);
	}

	function test_key() {
		$entry_baba = new Translation_Entry(array('singular' => 'baba',));
		$entry_dyado = new Translation_Entry(array('singular' => 'dyado',));
		$entry_baba_ctxt = new Translation_Entry(array('singular' => 'baba', 'context' => 'x'));
		$entry_baba_plural = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas'));
		$this->assertEqual($entry_baba->key(), $entry_baba_plural->key());
		$this->assertNotEqual($entry_baba->key(), $entry_baba_ctxt->key());
		$this->assertNotEqual($entry_baba_plural->key(), $entry_baba_ctxt->key());
		$this->assertNotEqual($entry_baba->key(), $entry_dyado->key());
	}


}
?>