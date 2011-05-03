<?php

class GP_Format_Strings {
	
	var $extension = 'strings';
	
	var $exported = '';
	
	function sort_entries( $a, $b ) {
		if ( $a->context == $b->context ) {
			return 0;
		}
		return ( $a->context > $b->context ) ? +1 : -1;
	}
	
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = '';
		$prefix = pack( 'CC', 0xff, 0xfe ); // Add BOM
		
		$sorted_entries = $entries;
		usort( $sorted_entries, array( 'GP_Format_Strings', 'sort_entries' ) );
		foreach ( $sorted_entries as $entry ) {
			$original = str_replace( "\n", "\\n", $entry->context );
			$translation = str_replace( "\n", "\\n", empty( $entry->translations ) ? $entry->context : $entry->translations[0] );
			$comment = preg_replace( "/(^\s+)|(\s+$)/us", "", $entry->extracted_comments ); 
			if ( $comment == "" ) {
				$comment = "No comment provided by engineer.";
			}
			$result .= "/* $comment */\n\"$original\" = \"$translation\";\n\n";
		}
		
		return $prefix . mb_convert_encoding( $result, 'UTF-16LE' );
	}
	
	function read_translations_from_file( $file_name, $project = null ) {
		if ( is_null( $project ) ) return false;
		$translations = $this->read_originals_from_file( $file_name );
		if ( !$translations ) return false;
		$originals = GP::$original->by_project_id( $project->id );
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
			if ( !$entry->singular ) {
				error_log( sprintf( __("Missing context %s in project #%d"), $entry->context, $project->id ) );
				continue;
			}
			
			$new_translations->add_entry( $entry );
		}
		return $new_translations;
		
	}

	function read_originals_from_file( $file_name ) {
		$entries = new Translations;
		$f = fopen( $file_name, 'r' );
		if ( !$f ) return false;
		$context = $comment = null;
		$lineno = 1;
		while ( false !== ( $line = fgets( $f ) ) ) {
			if ( $lineno == 1 ) {
				if ( substr( $line, 0, 2 ) == pack( "CC", 0xef, 0xff ) ) {
					$line = substr( $line, 2 );
				}
			}
			$line = mb_convert_encoding( $line, 'UTF-8', 'UTF-16BE' );
			if ( is_null( $context ) ) {
				if ( preg_match( '/^\/\*\s*(.*)\s*\*\/$/', $line, $matches ) ) {
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
					$entry = null;
				}
			}
			$lineno++;
		}
		return $entries;
	}

	
	function unescape( $string ) {
		return stripcslashes( $string );		
	}
	
	function escape( $string ) {
		$string = addcslashes( $string, "'\n");
		return $string;
	}
}

GP::$formats['strings'] = new GP_Format_Strings;