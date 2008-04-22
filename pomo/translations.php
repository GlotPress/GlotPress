<?php
/**
 * Class for a set of entries for translation and their associated headers
 *
 * @version $Id$
 * @package pomo
 * @subpackage translations
 */

require_once 'entry.php';

class Translations {
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

	/**
	 * Sets $header PO header to $value
	 *
	 * If the header already exists, it will be overwritten
	 *
	 * @param string $header header name, without trailing :
	 * @param string $value header value, without trailing \n
	 */
	function set_header($header, $value) {
		$this->headers[$header] = $value;
	}

	function set_headers(&$headers) {
		$this->headers = array_merge($this->headers, $headers);
	}

	function translate_entry(&$entry) {
		$key = $entry->key();
		return isset($this->entries[$key])? $this->entries[$key] : false;
	}

	function translate($singular, $content=null) {
		$entry = new Translation_Entry(array('singular' => $singular, 'context' => $content));
		$translated = $this->translate_entry($entry);
		return ($translated && !empty($translated->translations))? $translated->translations[0] : $singular;
	}

	function translate_plural($singular, $plural, $count) {
		//TODO: set up a function on setting the Plural header
	}

}

?>
