<?php
require_once('init.php');

class GP_Test_Translations extends GP_UnitTestCase {
    function GP_Test_Translations() {
        $this->UnitTestCase('Translations');
    }

	function test_add_entry() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$empty = new Translation_Entry();
		$po = new Translations();
		$po->add_entry(&$entry);
		$this->assertEqual(array($entry->key() => $entry), $po->entries);
		// add the same entry more than once
		// we do not need to test proper key generation here, see test_key()
		$po->add_entry(&$entry);
		$po->add_entry(&$entry);
		$this->assertEqual(array($entry->key() => $entry), $po->entries);
		$po->add_entry(&$entry2);
		$this->assertEqual(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);
		// add empty entry
		$this->assertEqual(false, $po->add_entry($empty));
		$this->assertEqual(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);
	}

	function test_translate() {
		$entry1 = new Translation_Entry(array('singular' => 'baba', 'translations' => array('babax')));
		$entry2 = new Translation_Entry(array('singular' => 'baba', 'translations' => array('babay'), 'context' => 'x'));
		$domain = new Translations();
		$domain->add_entry(&$entry1);
		$domain->add_entry(&$entry2);
		$this->assertEqual('babax', $domain->translate('baba'));
		$this->assertEqual('babay', $domain->translate('baba', 'x'));
		$this->assertEqual('baba', $domain->translate('baba', 'y'));
		$this->assertEqual('babaz', $domain->translate('babaz'));
	}

	function test_translate_plural() {
		$entry_incomplete = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('babax')));
		$entry_toomany = new Translation_Entry(array('singular' => 'wink', 'plural' => 'winks', 'translations' => array('winki', 'winka', 'winko')));
		$entry_2 = new Translation_Entry(array('singular' => 'dyado', 'plural' => 'dyados', 'translations' => array('dyadox', 'dyadoy')));
		$domain = new Translations();
		$domain->add_entry(&$entry_incomplete);
		$domain->add_entry(&$entry_toomany);
		$domain->add_entry(&$entry_2);
		$this->assertEqual('other', $domain->translate_plural('other', 'others', 1));
		$this->assertEqual('others', $domain->translate_plural('other', 'others', 111));
		// too few translations + cont logic
		$this->assertEqual('babas', $domain->translate_plural('baba', 'babas', 2));
		$this->assertEqual('babas', $domain->translate_plural('baba', 'babas', 0));
		$this->assertEqual('babas', $domain->translate_plural('baba', 'babas', -1));
		$this->assertEqual('babas', $domain->translate_plural('baba', 'babas', 999));
		// proper
		$this->assertEqual('dyadox', $domain->translate_plural('dyado', 'dyados', 1));
		$this->assertEqual('dyadoy', $domain->translate_plural('dyado', 'dyados', 0));
		$this->assertEqual('dyadoy', $domain->translate_plural('dyado', 'dyados', 18881));
		$this->assertEqual('dyadoy', $domain->translate_plural('dyado', 'dyados', -18881));
	}
   
}