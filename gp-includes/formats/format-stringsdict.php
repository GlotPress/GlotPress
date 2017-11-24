<?php
/**
 * GlotPress Format Mac OS X/iOS StringsDict class
 *
 * @since 3.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support Mac OS X/iOS Plural Strings file format.
 *
 * .stringsdict file format defined at: https://developer.apple.com/library/content/documentation/MacOSX/Conceptual/BPInternational/StringsdictFileFormat/StringsdictFileFormat.html
 *
 * @since 3.0.0
 */
class GP_Format_StringsDict extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $name = 'Mac OS X / iOS StringsDict File (.stringsdict)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $extension = 'stringsdict';

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
	 * @since 3.0.0
	 *
	 * @var string
	 */
	public $exported = '';

	/**
	 * Generates a string the contains the $entries to export in the .strings file format.
	 *
	 * @since 3.0.0
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
	 * @return string The exported .strings string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );
		$this->line( '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">' );
		$this->line( '<plist version="1.0">' );

		$this->line( '<!--' );
		$this->line( 'Translation-Revision-Date: ' . GP::$translation->last_modified( $translation_set ) . '+0000' );
		$this->line( 'Generator: GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$this->line( 'Language: ' . $language_code );
		}

		$this->line( '-->' );

		$this->line( '<dict>' );

		$sorted_entries = $entries;
		usort( $sorted_entries, array( 'GP_Format_StringsDict', 'sort_entries' ) );

		foreach ( $sorted_entries as $entry ) {
			if ( $entry->is_plural ) {
				$this->plural( $entry, $locale );
			}
		}

		$this->line( '</dict>' );
		$this->line( '</plist>' );

		return $this->exported;
	}

	/**
	 * Save a line to the exported class variable.  Supports prepending of tabs and appending
	 * a newline to the string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $string       The string to process.
	 * @param int    $prepend_tabs The number of tab characters to prepend to the output.
	 */
	private function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}

	/**
	 * Output the plural entry to the exported class variable.
	 *
	 * @since 2.4.0
	 *
	 * @param obj $entry  The entry to store.
	 * @param obj $locale The locale object to use for exporting.
	 */
	private function plural( $entry, $locale ) {
		$nplurals    = $locale->nplurals;
		$order       = $this->get_plural_order( $locale );
		$plural_type = $this->get_plural_type( $entry->singular, $entry->plural );

		$this->line( '<key>' . $entry->singular . '</key>', 1 );
		$this->line( '<dict>', 1 );
		$this->line( '<string>%#@variable_0@</string>', 2 );
		$this->line( '<key>variable_0</key>', 2 );
		$this->line( '<dict>', 2 );
		$this->line( '<key>NSStringFormatSpecTypeKey</key>', 3 );
		$this->line( '<string>NSStringPluralRuleType</string>', 3 );
		$this->line( '<key>NSStringFormatValueTypeKey</key>', 3 );
		$this->line( '<string>' . $plural_type . '</string>', 3 );

		for ( $i = 0; $i < $nplurals; $i++ ) {
			$this->line( '<key>' . $order[ $i ] . '</key>', 3 );
			$this->line( '<string>' . $entry->translations[ $i ] . '</string>', 3 );
		}

		$this->line( '</dict>', 2 );
		$this->line( '</dict>', 1 );
	}

	/**
	 * Determine what type of variable is being used in the string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $singular The singular string.
	 * @param string $plural   The plural string.
	 */
	private function get_plural_type( $singular, $plural ) {
		foreach ( array( $singular, $plural ) as $string ) {
			// Match the allowable variable types (https://developer.apple.com/library/content/documentation/CoreFoundation/Conceptual/CFStrings/formatSpecifiers.html#//apple_ref/doc/uid/TP40004265).
			if ( 1 === preg_match( '/%[h|hh|l|ll|q|L|z|t|j]?[@|d|D|u|U|x|X|o|O|f|e|E|g|G|c|C|s|S|p|a|A|F]/', $string, $matches ) ) {
				return substr( $matches[0], 1 );
			}
		}
	}

	/**
	 * Determine the order of plurals with relation to the CLDR standard.
	 *
	 * @since 2.4.0
	 *
	 * @param obj $locale  The locale object to use for exporting.
	 */
	private function get_plural_order( $locale ) {
		$order = array();

		foreach ( $locale->cldr_plural_expressions as $key => $value ) {
			if ( '' !== $value ) {
				$order[] = $key;
			}
		}

		return $order;
	}

	/**
	 * Reads a set of original strings from an .stringsdict file.
	 *
	 * @since 3.0.0
	 *
	 * @param string $file_name The name of the uploaded file.
	 *
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		$entries = new Translations();
		$file    = file_get_contents( $file_name );

		if ( false === $file ) {
			return false;
		}

		$file = mb_convert_encoding( $file, 'UTF-8', 'UTF-16LE' );

		$context = null;
		$comment = null;
		$lines   = explode( "\n", $file );

		foreach ( $lines as $line ) {
			if ( is_null( $context ) ) {
				if ( preg_match( '/^\/\*\s*(.*)\s*\*\/$/', $line, $matches ) ) {
					$matches[1] = trim( $matches[1] );

					if ( 'No comment provided by engineer.' !== $matches[1] ) {
						$comment = $matches[1];
					} else {
						$comment = null;
					}
				} elseif ( preg_match( '/^"(.*)" = "(.*)";$/', $line, $matches ) ) {
					$entry           = new Translation_Entry();
					$entry->context  = $this->unescape( $matches[1] );
					$entry->singular = $this->unescape( $matches[2] );

					if ( ! is_null( $comment ) ) {
						$entry->extracted_comments = $comment;
						$comment                   = null;
					}

					$entry->translations = array();
					$entries->add_entry( $entry );
				}
			}
		}

		return $entries;
	}

	/**
	 * Compare two context objects for a usort callback.
	 *
	 * @since 3.0.0
	 *
	 * @param string $a The first object to compare.
	 * @param string $b The second object to compare.
	 *
	 * @return int Returns the result of the comparison.
	 */
	private function sort_entries( $a, $b ) {
		if ( $a->context === $b->context ) {
			return 0;
		}

		return ( $a->context > $b->context ) ? +1 : -1;
	}

	/**
	 * Unescapes a string with c style slashes.
	 *
	 * @since 3.0.0
	 *
	 * @param string $string The string to unescape.
	 *
	 * @return string Returns the unescaped string.
	 */
	private function unescape( $string ) {
		return stripcslashes( $string );
	}

	/**
	 * Escapes a string with c style slashes and html entities as required.
	 *
	 * @since 3.0.0
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string Returns the escaped string.
	 */
	private function escape( $string ) {
		return addcslashes( $string, '"\\/' );
	}

}

GP::$formats['stringsdict'] = new GP_Format_StringsDict();
