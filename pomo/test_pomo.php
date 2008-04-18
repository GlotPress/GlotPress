<?php
/**
 * Tests for pomo
 * @version $Id: test.php 3 2008-04-14 12:56:21Z nbachiyski $
 * @package pomo
 * @subpackage tests
 */
error_reporting(E_ALL);
require_once('PHPUnit/Framework.php');

require_once('po.php');

class Test_POMO extends PHPUnit_Framework_TestCase {

	function temp_filename() {
		$tmp_dir = '';
		$dirs = array('TMP', 'TMPDIR', 'TEMP');
		foreach($dirs as $dir)
			if (isset($_ENV[$dir]) && !empty($_ENV[$dir])) {
				$tmp_dir = $dir;
				break;
			}
		if (empty($dir)) $dir = '/tmp';
		$dir = realpath($dir);
		return tempnam($dir, 'testpomo');

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
		$this->assertEquals('baba', $entry->singular);
		$this->assertEquals('babas', $entry->plural);
		$this->assertTrue($entry->is_plural);
		$this->assertFalse(isset($entry->non_existant));
		$this->assertEquals(array('баба', 'баби'), $entry->translations);
		$this->assertEquals(array(), $entry->references);
		$this->assertEquals(array(), $entry->flags);
	}

	function test_prepend_each_line() {
		$this->assertEquals('baba_', PO::prepend_each_line('', 'baba_'));
		$this->assertEquals('baba_dyado', PO::prepend_each_line('dyado', 'baba_'));
		$this->assertEquals("# baba\n# dyado\n# \n", PO::prepend_each_line("baba\ndyado\n\n", '# '));
	}

	function test_poify() {
		//simple
		$this->assertEquals('"baba"', PO::poify('baba'));
		//long word, which shouldn't be wrapped
		$a90 = str_repeat("a", 90);
		$this->assertEquals("\"$a90\"", PO::poify($a90));
		// sentence, which should be wrapped
		$sentence = "Baba told me not to eat mushrooms early in the morning or me ears would go uuup in the skyyy";
		$this->assertEquals("\"\"\n\"Baba told me not to eat mushrooms early in the morning or me ears would go \"\n\"uuup in the skyyy\"", PO::poify($sentence));
		// tab
		$this->assertEquals('"ba\tba"', PO::poify("ba\tba"));
		// backslash 
		$this->assertEquals('"ba\\\\ba"', PO::poify('ba\\ba'));
		// empty line
		$this->assertEquals('"ba\\\\ba"', PO::poify('ba\\ba'));
		// random wordpress.pot string
		$src = 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.';
		$this->assertEquals("\"\"\n\"Categories can be selectively converted to tags using the <a \"\n\"href=\\\"%s\\\">category to tag converter</a>.\"", PO::poify($src));
		$mail = "Your new WordPress blog has been successfully set up at:

%1\$s

You can log in to the administrator account with the following information:

Username: %2\$s
Password: %3\$s

We hope you enjoy your new blog. Thanks!

--The WordPress Team
http://wordpress.org/
";
	$po_mail = '""
"Your new WordPress blog has been successfully set up at:\n"
"\n"
"%1$s\n"
"\n"
"You can log in to the administrator account with the following information:\n"
"\n"
"Username: %2$s\n"
"Password: %3$s\n"
"\n"
"We hope you enjoy your new blog. Thanks!\n"
"\n"
"--The WordPress Team\n"
"http://wordpress.org/\n"';
		$this->assertEquals($po_mail, PO::poify($mail));
	}

	function test_export_entry() {
		$entry = new Translation_Entry(array('singular' => 'baba'));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", PO::export_entry($entry));
		// plural
		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas'));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] ""
msgstr[1] ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'translator_comments' => "baba\ndyado"));
		$this->assertEquals('#  baba
#  dyado
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'extracted_comments' => "baba"));
		$this->assertEquals('#. baba
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array(
			'singular' => 'baba',
			'extracted_comments' => "baba",
			'references' => range(1, 29)));
		$this->assertEquals('#. baba
#. 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28
#. 29
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array()));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array('куку', 'буку')));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"куку\"", PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку')));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"', PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга')));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', PO::export_entry($entry));
		// context
		$entry = new Translation_Entry(array('context' => 'ctxt', 'singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга'), 'flags' => array('fuzzy', 'php-format')));
		$this->assertEquals('#, fuzzy
#, php-format
msgctxt "ctxt"
msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', PO::export_entry($entry));
	}


	function test_key() {
		$entry_baba = new Translation_Entry(array('singular' => 'baba',));
		$entry_dyado = new Translation_Entry(array('singular' => 'dyado',));
		$entry_baba_ctxt = new Translation_Entry(array('singular' => 'baba', 'context' => 'x'));
		$entry_baba_plural = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas'));
		$this->assertEquals($entry_baba->key(), $entry_baba_plural->key());
		$this->assertNotEquals($entry_baba->key(), $entry_baba_ctxt->key());
		$this->assertNotEquals($entry_baba_plural->key(), $entry_baba_ctxt->key());
		$this->assertNotEquals($entry_baba->key(), $entry_dyado->key());
	}

	function test_add_entry() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$empty = new Translation_Entry();
		$po = new PO();
		$po->add_entry(&$entry);
		$this->assertEquals(array($entry->key() => $entry), $po->entries);
		// add the same entry more than once
		// we do not need to test proper key generation here, see test_key()
		$po->add_entry(&$entry);
		$po->add_entry(&$entry);
		$this->assertEquals(array($entry->key() => $entry), $po->entries);
		$po->add_entry(&$entry2);
		$this->assertEquals(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);
		// add empty entry
		$this->assertEquals(false, $po->add_entry($empty));
		$this->assertEquals(array($entry->key() => $entry, $entry2->key() => $entry2), $po->entries);
	}

	function test_export_entries() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po = new PO();
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export_entries());
	}

	function test_export_headers() {
		$po = new PO();
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$this->assertEquals("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"", $po->export_headers());
	}

	function test_export() {
		$po = new PO();
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export(false));
		$this->assertEquals("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"\n\nmsgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export());
	}


	function test_export_to_file() {
		$po = new PO();
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$po->add_entry($entry);
		$po->add_entry($entry2);

		$temp_fn = $this->temp_filename();
		$po->export_to_file($temp_fn, false);
		$this->assertEquals($po->export(false), file_get_contents($temp_fn));

		$temp_fn2 = $this->temp_filename();
		$po->export_to_file($temp_fn2);
		$this->assertEquals($po->export(), file_get_contents($temp_fn2));
	}

}

?>
