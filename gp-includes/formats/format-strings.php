<?php

class GP_Format_Strings extends GP_Format {

	public $name = 'Mac OS X / iOS Strings File (.strings)';
	public $extension = 'strings';

	public $exported = '';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$prefix = pack( 'CC', 0xff, 0xfe ); // Add BOM

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
			$entry->context = $this->escape( $entry->context );
			$translation = empty( $entry->translations ) ? $entry->context : $this->escape( $entry->translations[0] );

			$original = str_replace( "\n", "\\n", $entry->context );
			$translation = str_replace( "\n", "\\n", $translation );
			$comment = preg_replace( "/(^\s+)|(\s+$)/us", "", $entry->extracted_comments );

			if ( $comment == "" ) {
				$comment = "No comment provided by engineer.";
			}

			$result .= "/* $comment */\n\"$original\" = \"$translation\";\n\n";
		}

		return $prefix . mb_convert_encoding( $result, 'UTF-16LE' );
	}

	public function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$file = file_get_contents( $file_name );

		if ( false === $file ) {
			return false;
		}

		$file = mb_convert_encoding( $file, 'UTF-8', 'UTF-16LE' );

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


	private function sort_entries( $a, $b ) {
		if ( $a->context == $b->context ) {
			return 0;
		}

		return ( $a->context > $b->context ) ? +1 : -1;
	}

	private function unescape( $string ) {
		return stripcslashes( $string );
	}

	private function escape( $string ) {
		return addcslashes( $string, '"\\/' );
	}

}

GP::$formats['strings'] = new GP_Format_Strings;
