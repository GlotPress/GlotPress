<?php

class GP_Format_Properties extends GP_Format {

	public $name = 'Java Properties File (.properties)';
	public $extension = 'properties';
	public $filename_pattern = '%s_%s';

	public $exported = '';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = '';

		$sorted_entries = $entries;
		usort( $sorted_entries, array( 'GP_Format_Properties', 'sort_entries' ) );

		foreach ( $sorted_entries as $entry ) {
			$entry->context = $this->escape( $entry->context );
			$translation = empty( $entry->translations ) ? $entry->context : $this->escape( $entry->translations[0] );

			$original = empty( $entry->context ) ? $entry->singular : $entry->context;
			$original = str_replace( "\n", "\\n", $original );
			$translation = str_replace( "\n", "\\n", $translation );
			$translation = substr( json_encode( $translation ), 1, -1 );
			$comment = preg_replace( "/(^\s+)|(\s+$)/us", "", $entry->extracted_comments );

			if ( $comment == "" ) {
				$comment = "No comment provided.";
			}

			$result .= "# $comment\n" . $this->escape_key( $original ) . " = $translation\n\n";
		}

		return $result;
	}

	public function read_translations_from_file( $file_name, $project = null ) {
		if ( is_null( $project ) ) {
			return false;
		}

		$translations = $this->read_originals_from_file( $file_name );

		if ( ! $translations ) {
			return false;
		}

		$originals        = GP::$original->by_project_id( $project->id );
		$new_translations = new Translations;

		foreach( $translations->entries as $key => $entry ) {
			// we have been using read_originals_from_file to parse the file
			// so we need to swap singular and translation
			if ( $entry->context == $entry->singular ) {
				$entry->translations = array();
			} else {
				$entry->translations = array( $entry->singular );
			}

			$entry->singular = null;

			foreach( $originals as $original ) {
				if ( $original->context == $entry->context ) {
					$entry->singular = $original->singular;
					break;
				}
			}

			if ( ! $entry->singular ) {
				error_log( sprintf( __( 'Missing context %s in project #%d', 'glotpress' ), $entry->context, $project->id ) );
				continue;
			}

			$new_translations->add_entry( $entry );
		}

		return $new_translations;
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
				if ( preg_match( '/^\(#|!)\s*(.*)\s*$/', $line, $matches ) ) {
					$matches[1] = trim( $matches[1] );

					if ( $matches[1] !== "No comment provided." ) {
						$comment = $matches[1];
					} else {
						$comment = null;
					}
				} else if ( preg_match( '/^(.*)(=|:)(.*)$/', $line, $matches ) ) {
					$entry = new Translation_Entry();
					$entry->context = rtrim( $this->unescape( $matches[1] ) );
					$entry->singular = ltrim( $this->unescape( $matches[2] ) );

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

	private function escape_key( $string ) {
		return addcslashes( substr( json_encode( $string ), 1, -1 ), '=: ' );
	}
	
}

GP::$formats['properties'] = new GP_Format_Properties;
