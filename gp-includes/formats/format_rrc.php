<?php

class GP_Format_RRC extends GP_Format {

	public $name = 'Blackberry Resource (.rrc)';
	public $extension = 'rrc';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$rrc = array();
		foreach( $entries as $entry ) {
			if ( !preg_match( '/^([A-Z0-9_]+)(?:\[(\d+)\])?$/', $entry->context, $matches ) ) {
				error_log( 'RRC Export: Bad Entry: '.$entry->context );
				continue;
			}
			$translation_with_original_fallback = gp_array_get( $entry->translations, 0, $entry->singular );
			if ( isset( $matches[2] ) ) {
				$key = $matches[1];
				$index = $matches[2];
				$rrc[$key][$index] = $translation_with_original_fallback;
			} else {
				$rrc[$entry->context] = $translation_with_original_fallback;
			}
		}
		$result = '';
		foreach( $rrc as $key => $translation ) {
			if ( !is_array( $translation ) ) {
				$result .= "$key#0=\"". $this->escape( $translation ) . "\";\n";
			} else {
				$result .= "$key#0={\n";
				foreach( $translation as $single_translation ) {
					$result .= "\t\"" . $this->escape( $single_translation ) . "\",\n";
				}
				$result .= "};\n";
			}
		}
		return $result;
	}

	public function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$f = fopen( $file_name, 'r' );

		if ( ! $f ) {
			return false;
		}

		$context = $index = $base_string_id = null;

		while ( false !== ( $line = fgets( $f ) ) ) {
			$line = trim( $line );
			if ( is_null( $context) ) {
				// single line entry
				if ( preg_match( '/^([A-Z0-9_]+)\#0\s*=\s*"(.*)";$/', $line, $matches ) ) {
					$entry = new Translation_Entry();
					$entry->context = $matches[1];
					$entry->singular = $this->unescape( $matches[2] );
					$entry->translations = array();
					$entries->add_entry( $entry );
				} elseif ( preg_match( '/^([A-Z0-9_]+)\#0\s*=\s*{$/', $line, $matches ) ) {
					$base_string_id = $matches[1];
					$context = 'inside-multiple';
					$index = 0;
				} else {
					error_log("Bad line: $line");
					return false;
				}
			} elseif ( 'inside-multiple' == $context ) {
				if ( '};' == $line ) {
					$context = null;
				} elseif ( preg_match( '/^"(.*)",$/', $line, $matches ) ) {
					$entry = new Translation_Entry;
					$entry->singular = $this->unescape( $matches[1] );
					$entry->context = $base_string_id . '[' . $index++ .']';
					$entry->translations = array();
					$entries->add_entry( $entry );
				} else {
					error_log("Bad multiple line: $line");
					return false;
				}
			}
		}

		return $entries;
	}


	/**
	 * Escapes a UTF-8 string to be used in RRC file
	 *
	 * Suitable characters are encoded in ISO-8859-1, all non-latin1 unicode
	 * characters are encoded via \uXXXX notation, where XXXX is 0-paded hex unicode code-point
	 * Newlines, tabs and carriage returns are backslash-escaped.
	 */
	private function escape( $string ) {
		$string = addcslashes( $string, "\"\n\t\r" );
		preg_match_all( '/./us', $string, $matches );
		$characters = $matches[0];
		$string = '';
		foreach( $characters as $c ) {
			if ( 1 == strlen( $c ) ) {
				$string .= $c;
			} else {
				if ( ( $c_latin1 = mb_convert_encoding( $c, 'ISO-8859-1', 'UTF-8' ) ) != '?' ) {
					$string .= $c_latin1;
				} else {
					$entity = mb_encode_numericentity( $c, array(0x0, 0xffff, 0, 0xffff), 'UTF-8' );
					$code_point = str_replace( array('&', '#', ';'), '', $entity );
					$string .= '\\u' . str_pad( strtoupper( dechex( $code_point ) ), 4, '0', STR_PAD_LEFT );
				}
			}
		}
		return $string;
	}

	/**
	 * The reverse of {@see escape}
	 */
	private function unescape( $string ) {
		// in the resource file all the strings should be in iso-8859-1
		$string = utf8_encode( $string );
		// except for the unicode code points like \uABCD
		$decode_codepoints_callback = lambda( '$m', 'html_entity_decode("&#x".$m[1].";", ENT_NOQUOTES, "UTF-8");' );
		$string = preg_replace_callback( '/\\\\u([a-fA-F0-9]{4})/', $decode_codepoints_callback, $string );
		return stripcslashes( $string );
	}

}

GP::$formats['rrc'] = new GP_Format_RRC;