<?php
error_reporting(E_ALL);
require_once('PHPUnit/Framework.php');
require_once('pomo.php');

class Test_POMO extends PHPUnit_Framework_TestCase {

	function test_creation() {
		// no singular => empty object
		$entry = new GP_Entry();
		$this->assertNull($entry->singular);
		$this->assertNull($entry->plural);
		$this->assertFalse($entry->is_plural);
		// args -> members
		$entry = new GP_Gettext_Entry(array(
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
		$entry = new GP_Gettext_Entry();
		$this->assertEquals('baba_', $entry->_prepend_each_line('', 'baba_'));
		$this->assertEquals('baba_dyado', $entry->_prepend_each_line('dyado', 'baba_'));
		$this->assertEquals("# baba\n# dyado\n# \n", $entry->_prepend_each_line("baba\ndyado\n\n", '# '));
	}

	function test_poify() {
		$entry = new GP_Gettext_Entry();
		//simple
		$this->assertEquals('"baba"', $entry->poify('baba'));
		//long word, which shouldn't be wrapped
		$a90 = str_repeat("a", 90);
		$this->assertEquals("\"$a90\"", $entry->poify($a90));
		// sentence, which should be wrapped
		$sentence = "Baba told me not to eat mushrooms early in the morning or me ears would go uuup in the skyyy";
		$this->assertEquals("\"\"\n\"Baba told me not to eat mushrooms early in the morning or me ears would go \"\n\"uuup in the skyyy\"", $entry->poify($sentence));
		// tab
		$this->assertEquals('"ba\tba"', $entry->poify("ba\tba"));
		// backslash 
		$this->assertEquals('"ba\\\\ba"', $entry->poify('ba\\ba'));
		// empty line
		$this->assertEquals('"ba\\\\ba"', $entry->poify('ba\\ba'));
		// random wordpress.pot string
		$src = 'Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.';
		$this->assertEquals("\"\"\n\"Categories can be selectively converted to tags using the <a \"\n\"href=\\\"%s\\\">category to tag converter</a>.\"", $entry->poify($src));
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
		$this->assertEquals($po_mail, $entry->poify($mail));
	}

	function test_to_po() {
		$entry = new GP_Gettext_Entry(array('singular' => 'baba'));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", $entry->to_po());
		// plural
		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'plural' => 'babas'));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] ""
msgstr[1] ""', $entry->to_po());
		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'translator_comments' => "baba\ndyado"));
		$this->assertEquals('#  baba
#  dyado
msgid "baba"
msgstr ""', $entry->to_po());
		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'extracted_comments' => "baba"));
		$this->assertEquals('#. baba
msgid "baba"
msgstr ""', $entry->to_po());
		$entry = new GP_Gettext_Entry(array(
			'singular' => 'baba',
			'extracted_comments' => "baba",
			'references' => range(1, 29)));
		$this->assertEquals('#. baba
#. 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28
#. 29
msgid "baba"
msgstr ""', $entry->to_po());
		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'translations' => array()));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"\"", $entry->to_po());

		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'translations' => array('куку', 'буку')));
		$this->assertEquals("msgid \"baba\"\nmsgstr \"куку\"", $entry->to_po());

		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку')));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"', $entry->to_po());

		$entry = new GP_Gettext_Entry(array('singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга')));
		$this->assertEquals('msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', $entry->to_po());
		// context
		$entry = new GP_Gettext_Entry(array('context' => 'ctxt', 'singular' => 'baba', 'plural' => 'babas', 'translations' => array('кукубуку', 'кукуруку', 'бабаяга'), 'flags' => array('fuzzy', 'php-format')));
		$this->assertEquals('#, fuzzy
#, php-format
msgctxt "ctxt"
msgid "baba"
msgid_plural "babas"
msgstr[0] "кукубуку"
msgstr[1] "кукуруку"
msgstr[2] "бабаяга"', $entry->to_po());

	}
}

?>
