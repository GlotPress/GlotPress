<?php
require_once('init.php');

class GP_Test_MO extends GP_UnitTestCase {
    function GP_Test_MO() {
        $this->UnitTestCase('MO');
    }

	function test_mo_simple() {
		$mo = new MO();
		$mo->import_from_file('data/simple.mo');
		$this->assertEqual(array('Project-Id-Version' => 'WordPress 2.6-bleeding', 'Report-Msgid-Bugs-To' => 'wp-polyglots@lists.automattic.com'), $mo->headers);
		$this->assertEqual(2, count($mo->entries));
		$this->assertEqual(array('dyado'), $mo->entries['baba']->translations);
		$this->assertEqual(array('yes'), $mo->entries["kuku\nruku"]->translations);
	}

	function test_mo_plural() {
		$mo = new MO();
		$mo->import_from_file('data/plural.mo');
		$this->assertEqual(1, count($mo->entries));
		$this->assertEqual(array("oney dragoney", "twoey dragoney", "manyey dragoney", "manyeyey dragoney", "manyeyeyey dragoney"), $mo->entries["one dragon"]->translations);

		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', 1));
		$this->assertEqual('twoey dragoney', $mo->translate_plural('one dragon', '%d dragons', 2));
		$this->assertEqual('twoey dragoney', $mo->translate_plural('one dragon', '%d dragons', -8));


		$mo->set_header('Plural-Forms', 'nplurals=5; plural=0');
		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', 1));
		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', 2));
		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', -8));

		$mo->set_header('Plural-Forms', 'nplurals=5; plural=n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;');
		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', 1));
		$this->assertEqual('manyey dragoney', $mo->translate_plural('one dragon', '%d dragons', 11));
		$this->assertEqual('twoey dragoney', $mo->translate_plural('one dragon', '%d dragons', 3));
		
		$mo->set_header('Plural-Forms', 'nplurals=2; plural=n !=1;');
		$this->assertEqual('oney dragoney', $mo->translate_plural('one dragon', '%d dragons', 1));
		$this->assertEqual('twoey dragoney', $mo->translate_plural('one dragon', '%d dragons', 2));
		$this->assertEqual('twoey dragoney', $mo->translate_plural('one dragon', '%d dragons', -8));
	}

	function test_mo_context() {
		$mo = new MO();
		$mo->import_from_file('data/context.mo');
		$this->assertEqual(2, count($mo->entries));
		$plural_entry = new Translation_Entry(array('singular' => 'one dragon', 'plural' => '%d dragons', 'translations' => array("oney dragoney", "twoey dragoney","manyey dragoney"), 'context' => 'dragonland'));
		$this->assertEqual($plural_entry, $mo->entries[$plural_entry->key()]);
		$this->assertEqual("dragonland", $mo->entries[$plural_entry->key()]->context);

		$single_entry = new Translation_Entry(array('singular' => 'one dragon', 'translations' => array("oney dragoney"), 'context' => 'not so dragon'));
		$this->assertEqual($single_entry, $mo->entries[$single_entry->key()]);
		$this->assertEqual("not so dragon", $mo->entries[$single_entry->key()]->context);

	}
	
	function test_translations_merge() {
		$host = new Translations();
		$host->add_entry(new Translation_Entry(array('singular' => 'pink',)));
		$host->add_entry(new Translation_Entry(array('singular' => 'green',)));
		$guest = new Translations();
		$guest->add_entry(new Translation_Entry(array('singular' => 'green',)));
		$guest->add_entry(new Translation_Entry(array('singular' => 'red',)));
		$host->merge_with($guest);
		$this->assertEqual(3, count($host->entries));
		$this->assertEqual(array(), array_diff(array('pink', 'green', 'red'), array_keys($host->entries)));
	}
	
	function test_export_mo_file() {
		$entries = array();
		$entries[] = new Translation_Entry(array('singular' => 'pink',
			'translations' => array('розов')));
		$no_translation_entry = new Translation_Entry(array('singular' => 'grey'));
		$entries[] = new Translation_Entry(array('singular' => 'green', 'plural' => 'greens',
			'translations' => array('зелен', 'зелени')));
		$entries[] = new Translation_Entry(array('singular' => 'red', 'context' => 'color',
			'translations' => array('червен')));
		$entries[] = new Translation_Entry(array('singular' => 'red', 'context' => 'bull',
			'translations' => array('бик')));
		$entries[] = new Translation_Entry(array('singular' => 'maroon', 'plural' => 'maroons', 'context' => 'context',
			'translations' => array('пурпурен', 'пурпурни')));

		$mo = new MO();
		$mo->set_header('Project-Id-Version', 'Baba Project 1.0');
		foreach($entries as $entry) {
			$mo->add_entry($entry);
		}
		$mo->add_entry($no_translation_entry);
		
		$temp_fn = $this->temp_filename();
		$mo->export_to_file($temp_fn);
		
		$again = new MO();
		$again->import_from_file($temp_fn);

		$this->assertEqual(count($entries), count($again->entries));
		foreach($entries as $entry) {
			$this->assertEqual($entry, $again->entries[$entry->key()]);
		}
	}
	
	function test_nplurals_with_backslashn() {
		$mo = new MO();
		$mo->import_from_file('data/bad_nplurals.mo');
		$this->assertEqual('%d foro', $mo->translate_plural('%d forum', '%d forums', 1));
		$this->assertEqual('%d foros', $mo->translate_plural('%d forum', '%d forums', 2));
		$this->assertEqual('%d foros', $mo->translate_plural('%d forum', '%d forums', -1));
	}
}