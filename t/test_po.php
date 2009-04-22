<?php
require_once('init.php');

class GP_Test_PO extends GP_UnitTestCase {
    function GP_Test_PO() {
        $this->UnitTestCase('PO');

		// not so random wordpress.pot string -- multiple lines
		$this->mail = "Your new WordPress blog has been successfully set up at:

%1\$s

You can log in to the administrator account with the following information:

Username: %2\$s
Password: %3\$s

We hope you enjoy your new blog. Thanks!

--The WordPress Team
http://wordpress.org/
";
	$this->po_mail = '""
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
		$this->a90 = str_repeat("a", 90);
		$this->po_a90 = "\"$this->a90\"";
    }

	function test_prepend_each_line() {
		$this->assertEqual('baba_', PO::prepend_each_line('', 'baba_'));
		$this->assertEqual('baba_dyado', PO::prepend_each_line('dyado', 'baba_'));
		$this->assertEqual("# baba\n# dyado\n# \n", PO::prepend_each_line("baba\ndyado\n\n", '# '));
	}

	function test_poify() {
		//simple
		$this->assertEqual('"baba"', PO::poify('baba'));
		//long word		
		$this->assertEqual($this->po_a90, PO::poify($this->a90));
		// tab
		$this->assertEqual('"ba\tba"', PO::poify("ba\tba"));
		// do not add leading empty string of one-line string ending on a newline
		$this->assertEqual('"\\\\a\\\\n\\n"', PO::poify("\a\\n\n"));
		// backslash
		$this->assertEqual('"ba\\\\ba"', PO::poify('ba\\ba'));
		// random wordpress.pot string
		$src = 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.';
		$this->assertEqual("\"Categories can be selectively converted to tags using the <a href=\\\"%s\\\">category to tag converter</a>.\"", PO::poify($src));

		$this->assertEqual($this->po_mail, PO::poify($this->mail));
	}
	
	function test_unpoify() {
		$this->assertEqual('baba', PO::unpoify('"baba"'));
		$this->assertEqual("baba\ngugu", PO::unpoify('"baba\n"'."\t\t\t\n".'"gugu"'));
		$this->assertEqual($this->a90, PO::unpoify($this->po_a90));
		$this->assertEqual('\\t\\n', PO::unpoify('"\\\\t\\\\n"'));
		$this->assertEqual($this->mail, PO::unpoify($this->po_mail));
	}

	function test_export_entry() {
		$entry = new Translation_Entry(array('singular' => 'baba'));
		$this->assertEqual("msgid \"baba\"\nmsgstr \"\"", PO::export_entry($entry));
		// plural
		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas'));
		$this->assertEqual('msgid "baba"
msgid_plural "babas"
msgstr[0] ""
msgstr[1] ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'translator_comments' => "baba\ndyado"));
		$this->assertEqual('#  baba
#  dyado
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'extracted_comments' => "baba"));
		$this->assertEqual('#. baba
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array(
			'singular' => 'baba',
			'extracted_comments' => "baba",
			'references' => range(1, 29)));
		$this->assertEqual('#. baba
#: 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28
#: 29
msgid "baba"
msgstr ""', PO::export_entry($entry));
		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array()));
		$this->assertEqual("msgid \"baba\"\nmsgstr \"\"", PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'translations' => array('куку', 'буку')));
		$this->assertEqual("msgid \"baba\"\nmsgstr \"куку\"", PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку')));
		$this->assertEqual('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"', PO::export_entry($entry));

		$entry = new Translation_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга')));
		$this->assertEqual('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', PO::export_entry($entry));
		// context
		$entry = new Translation_Entry(array('context' => 'ctxt', 'singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга'), 'flags' => array('fuzzy', 'php-format')));
		$this->assertEqual('#, fuzzy
#, php-format
msgctxt "ctxt"
msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', PO::export_entry($entry));
    }

	function test_export_entries() {
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po = new PO();
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEqual("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export_entries());
	}

	function test_export_headers() {
		$po = new PO();
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$this->assertEqual("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"", $po->export_headers());
	}

	function test_export() {
		$po = new PO();
		$entry = new Translation_Entry(array('singular' => 'baba',));
		$entry2 = new Translation_Entry(array('singular' => 'dyado',));
		$po->set_header('Project-Id-Version', 'WordPress 2.6-bleeding');
		$po->set_header('POT-Creation-Date', '2008-04-08 18:00+0000');
		$po->add_entry($entry);
		$po->add_entry($entry2);
		$this->assertEqual("msgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export(false));
		$this->assertEqual("msgid \"\"\nmsgstr \"\"\n\"Project-Id-Version: WordPress 2.6-bleeding\\n\"\n\"POT-Creation-Date: 2008-04-08 18:00+0000\\n\"\n\nmsgid \"baba\"\nmsgstr \"\"\n\nmsgid \"dyado\"\nmsgstr \"\"", $po->export());
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
		$this->assertEqual($po->export(false), file_get_contents($temp_fn));

		$temp_fn2 = $this->temp_filename();
		$po->export_to_file($temp_fn2);
		$this->assertEqual($po->export(), file_get_contents($temp_fn2));
	}
}
?>