<?php
/**
 * GlotPress Format Android XML class
 *
 * @since 1.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support Android XML file format.
 *
 * @since 1.0.0
 */
class GP_Format_Android extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'Android XML (.xml)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $extension = 'xml';

	/**
	 * Which plural rules to use for this format.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $plurals_format = 'cldr';

	/**
	 * Storage for the export file contents while it is being generated.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $exported = '';

	/**
	 * Generates a string the contains the $entries to export in the Android XML file format.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 *
	 * @return string The exported Android XML string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );

		$this->line( '<!--' );
		$this->line( 'Translation-Revision-Date: ' . GP::$translation->last_modified( $translation_set ) . '+0000' );
		$this->line( "Plural-Forms: nplurals={$locale->nplurals}; plural={$locale->plural_expression};" );
		$this->line( 'Generator: GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$this->line( 'Language: ' . $language_code );
		}

		$this->line( '-->' );

		$this->line( '<resources>' );
		$string_array_items = array();

		foreach( $entries as $entry ) {
			if ( preg_match('/.+\[\d+\]$/', $entry->context ) ) {
				// Array item found.
				$string_array_items[] = $entry;
				continue;
			}

			if ( empty( $entry->context ) ) {
				$entry->context = $entry->singular;
			}

			$id = preg_replace( '/[^a-zA-Z0-9_]/U', '_', $entry->context );

			$this->line( '<string name="' . $id . '">' . $this->escape( $entry->translations[0] ) . '</string>', 1 );
		}

		$this->string_arrays( $string_array_items );

		$this->line( '</resources>' );

		return $this->exported;
	}

	/**
	 * Reads a set of original strings from an Android XML file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The name of the uploaded Android XML file.
	 *
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		// Disable the output of errors while processing the XML file.
		$errors = libxml_use_internal_errors( true );

		// Get the contents from the temporary file.
		$contents = file_get_contents( $file_name );

		/*
		 * Android strings can use <xliff:g> tags to indicate a part of the string should NOT be translated.
		 *
		 * See the "Mark message parts that should not be translated" section of https://developer.android.com/distribute/tools/localization-checklist.html
		 *
		 * Unfortunately SimpleXML will parse these as valid XML tags, which we don't want so replace the opening brace with something we can
		 * re-instate later to process the xliff tags ourselves.
		*/
		$contents = str_ireplace( '<xliff:g', '--xlifftag--xliff:g', $contents );
		$contents = str_ireplace( '</xliff:g>', '--xlifftag--/xliff:g>', $contents );

		// Parse the file contents.
		$data = simplexml_load_string( $contents, null, LIBXML_NOCDATA );

		// Reset the error display to it's original setting.
		libxml_use_internal_errors( $errors );

		// Check to see if the XML parsing was successful.
		if ( ! is_object( $data ) )
			return false;

		$entries = new Translations;

		// Loop through all of the single strings we found in the XML file.
		foreach ( $data->string as $string ) {
			// If the string is marked as non-translatable, skip it.
			if ( isset( $string['translatable'] ) && 'false' == $string['translatable'] ) {
				continue;
			}

			// Generate the entry to add.
			$entry = $this->generate_entry( $string, (string) $string['name'] );

			// Add the entry to the results.
			$entries->add_entry( $entry );
		}

		// Loop through all of the multiple strings we found in the XML file.
		foreach ( $data->{'string-array'} as $string_array )
		{
			if ( isset( $string_array['translatable'] ) && 'false' == $string_array['translatable'] ) {
				continue;
			}

			$array_name = (string) $string_array['name'];
			$item_index = 0;

			foreach ( $string_array->item as $string ) {
				// Generate the entry to add.
				$entry = $this->generate_entry( $string, $array_name . "[$item_index]" );

				// Add the entry to the results.
				$entries->add_entry( $entry );

				// Increment our index for the next entry.
				$item_index++;
			}
		}

		return $entries;
	}

	/**
	 * Generates a translation entry object to be added to the results for the "read_originals_from_file()" function.
	 *
	 * @since 1.0.0
	 *
	 * @param obj    $string  The string entry objectto use.
	 * @param string $context The context string to use.
	 *
	 * @return obj A translation entry object.
	 */
	private function generate_entry( $string, $context ) {
		// Check to see if there is an xliff tag in the string.
		$xliff_info = $this->extract_xliff_info( (string) $string[0] );

		// If an xliff tag was found, replace the translation and add a comment for later.
		if ( false !== $xliff_info ) {
			$string[0] = $xliff_info['string'];
			$string['comment'] .= $xliff_info['description'];
		}

		// Create the new translation entry with the parsed data.
		$entry               = new Translation_Entry();
		$entry->context      = $context;
		$entry->singular     = $this->unescape( $string[0] );
		$entry->translations = array();

		// If we have a comment, add it to the entry.
		if ( isset( $string['comment'] ) && $string['comment'] ) {
			$entry->extracted_comments = (string) $string['comment'];
		}

		return $entry;
	}

	/**
	 * Extracts the xliff information from a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to process.
	 *
	 * @return array|bool An array containing the extracted information from the xliff tags (there may be multiple) on success, false on failure.
	 */
	private function extract_xliff_info( $string ) {
		// Define the initial xliff tag to look for.
		$search = '--xlifftag--';

		/*
		 * If it's not in the string, don't do any more processing.  Note we don't need to worry about
		 * case sensitivity here as the search string was added before the XML processing was done.
		 */
		if ( false === strstr( $string, $search ) ) {
			return false;
		}

		// Replace our temporary placeholder with the original text.
		$string = str_ireplace( $search, '<', $string );

		// Break apart the string in case there are multiple xliff's in it.
		$parts = explode( '<xliff:g', $string );

		// Setup the results array, part 0 will never need to be processed so automatically add it to the returned string.
		$result = array();
		$result['string'] = $parts[0];
		$result['comment'] = '';
		$result['description'] = '';

		// As we can skip the first part, loop through only the remaining parts.
		$total = count( $parts );
		for ( $i = 1; $i < $total; $i++ ) {
			// Add back the part we stripped out during the explode() above.
			$current = '<xliff:g' . $parts[ $i ];

			$matches = array();

			/*
			 * Break apart the entire string in to 5 parts:
			 *
			 *     0 = The full string.
			 *     1 = Any text before the xliff tag.
			 *     2 = The opening xliff tag.
			 *     3 = The actual text to be translated.
			 *     4 = The closing xliff tag.
			 *     5 = The rest of the string.
			 */
			if ( false !== preg_match( '/(.*)(<xliff:g.*>)(.*)(<\/xliff:g>)(.*)/i', $current, $matches ) ) {
				// If we have a match add to the results parameters to return the correct parts of the match.
				$result['string'] .= $matches[1] . $matches[3] . $matches[5];
				$result['comment'] .= ' ' . $matches[2] . $matches[3] . $matches[4];

				// Keep a copy of the current xliff tag that we're working with to parse for id/example attributes later.
				$current_comment = $matches[2] . $matches[3] . $matches[4];

				// Keep a copy of the component string to use later.
				$component = $matches[3];
				$text = '';

				// Parse the xliff tag for the id attribute, check for both single and double quotes.
				$id = preg_match( '/.*id="(.*)".*/iU', $current_comment, $matches ) || preg_match( '/.*id=\'(.*)\'.*/iU', $current_comment, $matches );

				// preg_match() returns int(1) when a match is found but since we're or'ing them, check to see if the result is a bool(true).
				if ( true === $id ) {
					// If an id attribute was found, record the contents of it.
					$id = $matches[1];
				} else {
					// preg_match() can return either int(0) for not found or bool(false) on error, in either case let's make it a bool(false) for consistency later.
					$id = false;
				}

				// Parse the xliff tag for the example attribute, check for both single and double quotes.
				$example = preg_match( '/.*example="(.*)".*/iU', $current_comment, $matches ) || preg_match( '/.*example=\'(.*)\'.*/iU', $current_comment, $matches );

				// preg_match() returns int(1) when a match is found but since we're or'ing them, check to see if the result is a bool(true).
				if ( true === $example ) {
					// If an example attribute was found, record the contents of it.
					$example = $matches[1];
				} else {
					// preg_match() can return either int(0) for not found or bool(false) on error, in either case let's make it a bool(false) for consistency later.
					$example = false;
				}

				// Time to make some human readable results based on what combination of id and example attributes that were found.
				if ( false !== $id && false !== $example ) {
					/* translators: 1: Component text 2: Component ID 3: Example output */
					$text = sprintf( __( 'This string has content that should not be translated, the "%1$s" component of the original, which is identified as the "%2$s" attribute by the developer may be replaced at run time with text like this: %3$s', 'glotpress' ), $component, $id, $example );
				} elseif ( false !== $id ) {
					/* translators: 1: Component text 2: Example output */
					$text = sprintf( __( 'This string has content that should not be translated, the "%1$s" component of the original, which is identified as the "%2$s" attribute by the developer and is not intended to be translated.', 'glotpress' ), $component, $id );
				} elseif ( false !== $example ) {
					/* translators: 1: Component ID 2: Example output */
					$text = sprintf( __( 'This string has content that should not be translated, the "%1$s" component of the original may be replaced at run time with text like this: %2$s', 'glotpress' ), $component, $example );
				} else {
					/* translators: 1: Component ID */
					$text = sprintf( __( 'This string has content that should not be translated, the "%1$s" component is not intended to be translated.', 'glotpress' ), $component );
				}

				// Add the description as set above to the return results array.
				$result['description'] .= ' ' . $text;
			} else {
				// If we don't, just append the current string to the result.
				$result['string'] .= ' ' . $current;
			}
		}

		// Make sure to trim the comment and description before returning them.
		$result['comment'] = trim( $result['comment'] );
		$result['description'] = trim( $result['description'] );

		return $result;
	}

	/**
	 * Save a line to the exported class variable.  Supports prepending of tabs and appending
	 * a newline to the string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string       The string to process.
	 * @param int    $prepend_tabs The number of tab characters to prepend to the output.
	 */
	private function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}

	/**
	 * Output the strings array entries to the exported class variable.
	 *
	 * @since 1.0.0
	 *
	 * @param obj $entries The entries to store.
	 */
	private function string_arrays( $entries ) {
		$mapping = array();

		// Sort the entries before processing them.
		uasort( $entries, array( $this, 'cmp_context' ) );

		// Loop through all of the single entries add them to a mapping array.
		foreach ( $entries as $entry ) {
			// Make sure the array name is sanatized.
			$array_name = preg_replace( '/\[\d+\]$/', '', $entry->context );

			// Initialize the mapping array entry if this is the first time.
			if ( ! isset( $mapping[ $array_name ] ) ) {
				$mapping[ $array_name ] = array();
			}

			// Because Android doesn't fallback on the original locale
			// in string-arrays, we fill the non-translated ones with original locale string.
			$value = $entry->translations[0];

			// If we don't have a value for the translation, use the singular.
			if ( ! $value ) {
				$value = $entry->singular;
			}

			// Add the entry to the mapping array after escaping it.
			$mapping[ $array_name ][] = $this->escape( $value );
		}

		// Now do the actual output to the class variable.
		foreach ( array_keys( $mapping ) as $array_name ) {
			// Open the string array tag.
			$this->line( '<string-array name="' . $array_name . '">', 1 );

			// Output each item in the array.
			foreach ( $mapping[ $array_name ] as $item ) {
				$this->line('<item>' . $item . '</item>', 2);
			}

			// Close the string arrary tag.
			$this->line( '</string-array>', 1 );
		}
	}

	/**
	 * Compare two context strings for a uasort callback.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a The first string to compare.
	 * @param string $b The second string to compare.
	 *
	 * @return int Returns the result of the comparison.
	 */
	private function cmp_context( $a, $b ) {
		return strnatcmp( $a->context, $b->context );
	}

	/**
	 * Preserve a Unicode sequence (like \u1234) by adding another backslash.
	 *
	 * @since 3.0
	 *
	 * @param string $string The string to process.
	 *
	 * @return string Returns the string with double-escaped Unicode sequences.
	 */
	private function preserve_escaped_unicode( $string ) {
		return preg_replace( '#\\\\u([0-9a-fA-F]{4})#', '\\\\$0', $string );
	}

	/**
	 * Unescapes a string with c style slashes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to unescape.
	 *
	 * @return string Returns the unescaped string.
	 */
	private function unescape( $string ) {
		$string = $this->preserve_escaped_unicode( $string );
		return stripcslashes( $string );
	}

	/**
	 * Escapes a string with c style slashes and html entities as required.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string Returns the escaped string.
	 */
	protected function escape( $string ) {
		$string = addcslashes( $string, "'\n\"" );
		$string = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $string );

		// Android strings that start with an '@' are references to other strings and need to be escaped.  See GH469.
		if ( gp_startswith( $string, '@' ) ) {
			$string = '\\' . $string;
		}

		return $string;
	}

}

GP::$formats['android'] = new GP_Format_Android;
