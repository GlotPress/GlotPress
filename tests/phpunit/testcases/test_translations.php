<?php

class GP_Test_Translations extends GP_UnitTestCase {

	function test_add_entry() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$empty = new Translation_Entry();
		$po = new Translations();
		$po->add_entry($entry);
		$this->assertEquals(array($entry->key() => $entry), $po->entries);
		// add the same entry more than once
		// we do not need to test proper key generation here, see test_key()
		$po->add_entry($entry);
		$po->add_entry($entry);
		$this->assertEquals(array($entry->key() => $entry), $po->entries);
		$po->add_entry($entry2);
		$this->assertEquals(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);
		// add empty entry
		$this->assertEquals(false, $po->add_entry($empty));
		$this->assertEquals(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);

		// give add_entry() the arguments and let it create the entry itself
		$po = new Translations();
		$po->add_entry(array('singular' => 'baba',));
		$entries= array_values($po->entries);
		$this->assertEquals($entry->key(), $entries[0]->key());
	}

	function test_translate() {
		$entry1 = new Translation_Entry(array('singular' => 'baba', 'translations' => array('babax')));
		$entry2 = new Translation_Entry(array('singular' => 'baba', 'translations' => array('babay'), 'context' => 'x'));
		$domain = new Translations();
		$domain->add_entry($entry1);
		$domain->add_entry($entry2);
		$this->assertEquals('babax', $domain->translate('baba'));
		$this->assertEquals('babay', $domain->translate('baba', 'x'));
		$this->assertEquals('baba', $domain->translate('baba', 'y'));
		$this->assertEquals('babaz', $domain->translate('babaz'));
	}

	function test_translate_plural() {
		$entry_incomplete = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('babax')));
		$entry_toomany = new Translation_Entry(array('singular' => 'wink', 'plural' => 'winks', 'translations' => array('winki', 'winka', 'winko')));
		$entry_2 = new Translation_Entry(array('singular' => 'dyado', 'plural' => 'dyados', 'translations' => array('dyadox', 'dyadoy')));
		$domain = new Translations();
		$domain->add_entry($entry_incomplete);
		$domain->add_entry($entry_toomany);
		$domain->add_entry($entry_2);
		$this->assertEquals('other', $domain->translate_plural('other', 'others', 1));
		$this->assertEquals('others', $domain->translate_plural('other', 'others', 111));
		// too few translations + cont logic
		$this->assertEquals('babas', $domain->translate_plural('baba', 'babas', 2));
		$this->assertEquals('babas', $domain->translate_plural('baba', 'babas', 0));
		$this->assertEquals('babas', $domain->translate_plural('baba', 'babas', -1));
		$this->assertEquals('babas', $domain->translate_plural('baba', 'babas', 999));
		// proper
		$this->assertEquals('dyadox', $domain->translate_plural('dyado', 'dyados', 1));
		$this->assertEquals('dyadoy', $domain->translate_plural('dyado', 'dyados', 0));
		$this->assertEquals('dyadoy', $domain->translate_plural('dyado', 'dyados', 18881));
		$this->assertEquals('dyadoy', $domain->translate_plural('dyado', 'dyados', -18881));
	}

	function test_digit_and_merge() {
		$entry_digit_1 = new Translation_Entry(array('singular' => 1, 'translations' => array('1')));
		$entry_digit_2 = new Translation_Entry(array('singular' => 2, 'translations' => array('2')));
		$domain = new Translations();
		$domain->add_entry($entry_digit_1);
		$domain->add_entry($entry_digit_2);
		$dummy_translation = new Translations;
		$this->assertEquals( '1', $domain->translate( '1' ) );
		$domain->merge_with( $dummy_translation );
		$this->assertEquals( '1', $domain->translate( '1' ) );
	}

	function test_if_translation_has_been_updated_since_timestamp() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$this->factory->translation->create( array(
			'original_id'        => $original->id,
			'translation_set_id' => $set->id,
			'status'             => 'current',
		) );

		$this->assertTrue( gp_has_translation_been_updated( $set ) );

		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Sat, 27 Apr 2012 15:49:29 GMT';
		$this->assertTrue( gp_has_translation_been_updated( $set ) );

		$_SERVER['HTTP_IF_MODIFIED_SINCE'] = 'Wed, 2 Feb 2022 22:22:22 GMT';
		$this->assertFalse( gp_has_translation_been_updated( $set ) );
	}
}