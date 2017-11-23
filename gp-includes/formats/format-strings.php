<?php
/**
 * GlotPress Format Mac OS X/iOS Strings class
 *
 * @since 1.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support Mac OS X/iOS Strings file format.
 *
 * .string file format defined at: https://developer.apple.com/library/content/documentation/MacOSX/Conceptual/BPInternational/MaintaingYourOwnStringsFiles/MaintaingYourOwnStringsFiles.html#//apple_ref/doc/uid/10000171i-CH19-SW22
 *
 * @since 1.0.0
 */
class GP_Format_Strings extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'Mac OS X / iOS Strings File (.strings)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $extension = 'strings';

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
	 * Generates a string the contains the $entries to export in the .strings file format.
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
	 * @return string The exported .strings string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = '';

		$result .= '/* Translation-Revision-Date: ' . GP::$translation->last_modified( $translation_set ) . "+0000 */\n";
		$result .= "/* Plural-Forms: nplurals={$locale->nplurals}; plural={$locale->plural_expression}; */\n";
		$result .= '/* Generator: GlotPress/' . GP_VERSION . " */\n";

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$result .= '/* Language: ' . $language_code . " */\n";
		}

		$result .= "\n";

		$sorted_entries = $entries;
		usort( $sorted_entries, array( 'GP_Format_Strings', 'sort_entries' ) );

		foreach ( $sorted_entries as $entry ) {
			$translation = $this->escape( empty( $entry->translations ) ? $entry->singular : $entry->translations[0] );

			$original    = str_replace( "\n", "\\n", $this->escape( $entry->singular ) );
			$translation = str_replace( "\n", "\\n", $translation );
			$comment     = preg_replace( '/(^\s+)|(\s+$)/us', '', $entry->extracted_comments );

			if ( $comment == "" ) {
				$comment = "No comment provided by engineer.";
			}

			$result .= "/* $comment */\n\"$original\" = \"$translation\";\n\n";
		}

		return $result;
	}

	/**
	 * Reads a set of original strings from an .strings file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The name of the uploaded file.
	 *
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$file = file_get_contents( $file_name );

		if ( false === $file ) {
			return false;
		}

		/**
		 * Check to see if the input file is UTF-16LE encoded, if so convert it to UTF-8.
		 *
		 * Note, Apple recommends UTF-8 but some of their tools (like genstrings) export
		 * UTF-16LE (or BE, but GP has never supported that) so to remain backwards
		 * compatible we support both for importing, but we only export UTF-8.
		 */
		if ( mb_check_encoding( $file, 'UTF-16LE' ) ) {
			$file = mb_convert_encoding( $file, 'UTF-8', 'UTF-16LE' );
		}

		$context = $comment = null;
		$lines = explode( "\n", $file );

		foreach ( $lines as $line ) {
			if ( is_null( $context ) ) {
				if ( preg_match( '/^\/\*\s*(.*)\s*\*\/$/', $line, $matches ) ) {
					$matches[1] = trim( $matches[1] );

					if ( $matches[1] !== "No comment provided by engineer." ) {
						$comment = $matches[1];
					} else {
						$comment = null;
					}
				} else if ( preg_match( '/^"(.*)" = "(.*)";$/', $line, $matches ) ) {
					$entry = new Translation_Entry();
					$entry->context = $this->unescape( $matches[1] );
					$entry->singular = $this->unescape( $matches[2] );

					if ( ! is_null( $comment )) {
						$entry->extracted_comments = $comment;
						$comment = null;
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
	 * @since 1.0.0
	 *
	 * @param string $a The first object to compare.
	 * @param string $b The second object to compare.
	 *
	 * @return int Returns the result of the comparison.
	 */
	private function sort_entries( $a, $b ) {
		if ( $a->context == $b->context ) {
			return 0;
		}

		return ( $a->context > $b->context ) ? +1 : -1;
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
	private function escape( $string ) {
		return addcslashes( $string, '"\\/' );
	}

}

GP::$formats['strings'] = new GP_Format_Strings;
