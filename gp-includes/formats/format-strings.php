<?php
/**
 * GlotPress Format Mac OSX / iOS Strings Translate class
 *
 * @since 1.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support Mac OS X / iOS Translate strings file format.
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
	 * Generates a string the contains the $entries to export in the strings file format.
	 *
	 * @since 1.0.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for. not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 * @return string The exported strings string.
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

			if ( '' == $comment ) {
				$comment = "No comment provided by engineer.";
			}

			$result .= "/* $comment */\n\"$original\" = \"$translation\";\n\n";
		}

		return $result;
	}

	/**
	 * Reads a set of original strings from a strings file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_name The name of the uploaded strings file.
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$file    = file_get_contents( $file_name );

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

		// Convert multi-line comments into a single line.
		$file = preg_replace_callback(
			'/\/\*\s*(.*?)\s*\*\//s',
			function( $m ) {
				return str_replace( PHP_EOL, '\n', $m[0] );
			},
			$file
		);

		$context = $comment = null;
		$lines   = explode( "\n", $file );

		foreach ( $lines as $line ) {
			if ( is_null( $context ) ) {
				if ( preg_match( '/^\/\*\s*(.*)\s*\*\/$/', $line, $matches ) ) {
					$matches[1] = trim( str_replace( '\n', PHP_EOL, $matches[1] ) );

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
	 * Sorts the translation entries based on the context attribute.
	 *
	 * @since 1.0.0
	 *
	 * @param string $a First string to compare.
	 * @param string $b Second string to compare.
	 * @return int +1 or -1 based on the order to sort.
	 */
	private function sort_entries( $a, $b ) {
		if ( $a->context == $b->context ) {
			return 0;
		}

		return ( $a->context > $b->context ) ? +1 : -1;
	}

	/**
	 * Strips any escaping from a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to strip escapes from.
	 * @return string The unescaped string.
	 */
	private function unescape( $string ) {
		return stripcslashes( $string );
	}

	/**
	 * Adds escaping to a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string The string to add escapes to.
	 * @return string The escaped string.
	 */
	private function escape( $string ) {
		return addcslashes( $string, '"\\/' );
	}

}

GP::$formats['strings'] = new GP_Format_Strings;
