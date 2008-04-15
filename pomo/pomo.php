<?php
/**
 * Library for working with PO and MO files
 *
 * @version $Id$
 * @package GlotPress
 * @subpackage pomo
 */

define('POMO_MAX_LINE_LEN', 79);

ini_set('auto_detect_line_endings', 1);

/**
 * GP_Entry class encapsulates a translatable string
 */
class GP_Entry {

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

	/**
	 * @param array $args associative array, support following keys:
	 * 	- singular (string) -- the string to translate, if omitted and empty entry will be created
	 * 	- plural (string) -- the plural form of the string, setting this will set {@link $is_plural} to true
	 * 	- translations (array) -- translations of the string and possibly -- its plural forms
	 * 	- context (string) -- a string differentiating two equal strings used in different contexts
	 * 	- translator_comments (string) -- comments left by translators
	 * 	- extracted_comments (string) -- comments left by developers
	 * 	- references (array) -- places in the code this strings is used, in relative_to_root_path/file.php:linenum form
	 */
	function GP_Entry($args=array()) {
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
 * A string for translation, including some gettext specifics
 */
class GP_Gettext_Entry extends GP_Entry {

	var $flags = array();

	function GP_Gettext_Entry($args=array()) {
		parent::GP_Entry($args);
		if (!is_array($this->flags)) $this->flags = array();
	}

	/**
	 * Formats a string in PO-style
	 *
	 * @param string $string the string to format
	 * @return string the poified string
	 */
	function poify($string) {
		$quote = '"';
		$slash = '\\';
		$newline = "\n";
		$tab = "\t";

		$replaces = array(
			"$slash" 	=> "$slash$slash",
			"$tab" 		=> "$slash$tab",
			"$quote"	=> "$slash$quote",
		);
		$string = str_replace(array_keys($replaces), array_values($replaces), $string);	

		$po = array();
		foreach (explode($newline, $string) as $line) {
			$po[] = wordwrap($line, POMO_MAX_LINE_LEN - 2, " $quote$newline$quote");
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

	function _prepend_each_line($string, $with) {
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
	function _comment_block($text, $char=' ') {
		$text = wordwrap($text, POMO_MAX_LINE_LEN - 3);
		return $this->_prepend_each_line($text, "#$char ");
	}

	/**
	 * Builds a string from the entry for inclusion in PO file
	 *
	 * @return string|bool PO-style formatted string for the entry or
	 * 	false if the entry is empty
	 */
	function to_po() {
		if (is_null($this->singular)) return false;
		$po = array();	
		if (!empty($this->translator_comments)) $po[] = $this->_comment_block($this->translator_comments);
		if (!empty($this->extracted_comments)) $po[] = $this->_comment_block($this->extracted_comments, '.');
		if (!empty($this->references)) $po[] = $this->_comment_block(implode(' ', $this->references), '.');
		if (!empty($this->flags)) $po[] = $this->_comment_block(implode("\n", $this->flags), ',');
		if (!is_null($this->context)) $po[] = 'msgctxt '.$this->poify($this->context);
		$po[] = 'msgid '.$this->poify($this->singular);
		if (!$this->is_plural) {
			$translation = empty($this->translations)? '' : $this->translations[0];
			$po[] = 'msgstr '.$this->poify($translation);
		} else {
			$po[] = 'msgid_plural '.$this->poify($this->plural);
			$translations = empty($this->translations)? array('', '') : $this->translations;
			foreach($translations as $i => $translation) {
				$po[] = "msgstr[$i] ".$this->poify($translation);
			}
		}
		return implode("\n", $po);
	}
}
?>
