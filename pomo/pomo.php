<?php
/**
 * Library for working with PO and MO files
 *
 * @version $Id$
 * @package pomo
 */

define('PO_MAX_LINE_LEN', 79);

ini_set('auto_detect_line_endings', 1);

/**
 * Translation_Entry class encapsulates a translatable string
 */
class Translation_Entry {

	/**
	 * Whether the entry contains a string and its plural form, default is false
	 *
	 * @var boolean
	 */
	var $is_plural = false;

	var $context = null;
	var $singular = null;
	var $plural = null;
	var $translations = array();
	var $translator_comments = '';
	var $extracted_comments = '';
	var $references = array();
	var $flags = array();

	/**
	 * @param array $args associative array, support following keys:
	 * 	- singular (string) -- the string to translate, if omitted and empty entry will be created
	 * 	- plural (string) -- the plural form of the string, setting this will set {@link $is_plural} to true
	 * 	- translations (array) -- translations of the string and possibly -- its plural forms
	 * 	- context (string) -- a string differentiating two equal strings used in different contexts
	 * 	- translator_comments (string) -- comments left by translators
	 * 	- extracted_comments (string) -- comments left by developers
	 * 	- references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
	 * 	- flags (array) -- flags like php-format
	 */
	function Translation_Entry($args=array()) {
		// if no singular -- empty object
		if (!isset($args['singular'])) {
			return;
		}
		// get member variable values from args hash
		$object_varnames = array_keys(get_object_vars($this));
		foreach ($args as $varname => $value) {
			if (in_array($varname, $object_varnames)) {
				$this->$varname = $value;
			}
		}
		if (isset($args['plural'])) $this->is_plural = true;
		if (!is_array($this->translations)) $this->translations = array();
		if (!is_array($this->references)) $this->references = array();
		if (!is_array($this->flags)) $this->flags = array();
	}

	/**
	 * Generates a unique key for this entry
	 *
	 * @return string|bool the key or false if the entry is empty
	 */
	function key() {
		if (is_null($this->singular)) return false;
		// prepend context and EOT, like in MO files
		return is_null($this->context)? $this->singular : $this->context.chr(4).$this->singular;
	}
}

/**
 * Routines for working with PO files
 */
class PO {
	
	var $entries = array();
	var $headers = array();

	/**
	 * Add entry to the PO structure
	 *
	 * @param object &$entry
	 * @return bool true on success, false if the entry doesn't have a key
	 */
	function add_entry(&$entry) {
		$key = $entry->key();
		if (false === $key) return false;
		$this->entries[$key] = &$entry;
		return true;
	}

	function set_header($header, $value) {
		$this->headers[$header] = $value;
	}

	function export_headers() {
		$header_string = '';
		foreach($this->headers as $header => $value) {
			$header_string.= "$header: $value\n";
		}
		$poified = PO::poify($header_string);
		return rtrim("msgid \"\"\nmsgstr $poified\n");
	}

	function export_entries() {
		//TODO sorting
		return implode("\n\n", array_map(array('PO', 'export_entry'), $this->entries));
	}

	function export($headers = true) {
		$res = '';
		if ($headers) {
			$res .= $this->export_headers();
		}
		$res .= $this->export_entries();
		return $res;
	}


	/**
	 * Formats a string in PO-style
	 *
	 * @static
	 * @param string $string the string to format
	 * @return string the poified string
	 */
	static function poify($string) {
		$quote = '"';
		$slash = '\\';
		$newline = "\n";
		$tab = "\t";

		$replaces = array(
			"$slash" 	=> "$slash$slash",
			"$tab" 		=> '\t',
			"$quote"	=> "$slash$quote",
		);
		$string = str_replace(array_keys($replaces), array_values($replaces), $string);	

		$po = array();
		foreach (explode($newline, $string) as $line) {
			$po[] = wordwrap($line, PO_MAX_LINE_LEN - 2, " $quote$newline$quote");
		}
		$po = $quote.implode("${slash}n$quote$newline$quote", $po).$quote;
		// add empty string on first line for readbility
		if (false !== strpos($po, $newline)) {
			$po = "$quote$quote$newline$po";
		}
		// remove empty strings
		$po = str_replace("$newline$quote$quote", '', $po);
		return $po;
	}

	/**
	 * Inserts $with in the beginning of every new line of $string and 
	 * returns the modified string
	 *
	 * @static
	 * @param string $string prepend lines in this string
	 * @param string $with prepend lines with this string
	 */
	static function prepend_each_line($string, $with) {
		$php_with = var_export($with, true);
		$lines = explode("\n", $string);
		// do not prepend the string on the last empty line, artefact by explode
		if ("\n" == substr($string, -1)) unset($lines[count($lines) - 1]);
		$res = implode("\n", array_map(create_function('$x', "return $php_with.\$x;"), $lines));
		// give back the empty line, we ignored above
		if ("\n" == substr($string, -1)) $res .= "\n";
		return $res;
	}

	/**
	 * Prepare a text as a comment -- wraps the lines and prepends #
	 * and a special character to each line
	 *
	 * @access private
	 * @param string $text the comment text
	 * @param string $char character to denote a special PO comment,
	 * 	like :, default is a space
	 */
	static function comment_block($text, $char=' ') {
		$text = wordwrap($text, PO_MAX_LINE_LEN - 3);
		return PO::prepend_each_line($text, "#$char ");
	}

	/**
	 * Builds a string from the entry for inclusion in PO file
	 *
	 * @static
	 * @param object &$entry the entry to convert to po string
	 * @return string|bool PO-style formatted string for the entry or
	 * 	false if the entry is empty
	 */
	static function export_entry(&$entry) {
		if (is_null($entry->singular)) return false;
		$po = array();	
		if (!empty($entry->translator_comments)) $po[] = PO::comment_block($entry->translator_comments);
		if (!empty($entry->extracted_comments)) $po[] = PO::comment_block($entry->extracted_comments, '.');
		if (!empty($entry->references)) $po[] = PO::comment_block(implode(' ', $entry->references), '.');
		if (!empty($entry->flags)) $po[] = PO::comment_block(implode("\n", $entry->flags), ',');
		if (!is_null($entry->context)) $po[] = 'msgctxt '.PO::poify($entry->context);
		$po[] = 'msgid '.PO::poify($entry->singular);
		if (!$entry->is_plural) {
			$translation = empty($entry->translations)? '' : $entry->translations[0];
			$po[] = 'msgstr '.PO::poify($translation);
		} else {
			$po[] = 'msgid_plural '.PO::poify($entry->plural);
			$translations = empty($entry->translations)? array('', '') : $entry->translations;
			foreach($translations as $i => $translation) {
				$po[] = "msgstr[$i] ".PO::poify($translation);
			}
		}
		return implode("\n", $po);
	}

}
?>
