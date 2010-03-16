<?php

class GP_Format_Android {
	
	var $extension = 'xml';
	
	var $exported = '';
	
	function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}
		
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );
		$this->line( '<resources>' );
		foreach( $entries as $entry ) {
			if ( !preg_match( '/^[a-z0-9_]+$/', $entry->singular ) ) {
				error_log( 'Android XML Export: Bad Entry: '. $entry->singular );
				continue;
			}
			$this->line( '<string name="' . $entry->singular . '">' . $this->escape( $entry->translations[0] ) . '</string>', 1 );
		}
		$this->line( '</resources>' );
		return $this->exported;
	}
	
	function read_translations_from_file( $file_name, $project = null ) {
		return $this->read_originals_from_file( $file_name );
	}

	function read_originals_from_file( $file_name ) {
		$errors = libxml_use_internal_errors( 'true' );
		$data = simplexml_load_string( file_get_contents( $file_name ) );
		libxml_use_internal_errors( $errors );
		if ( !is_object( $data ) ) return false;
		$entries = new Translations;
		foreach( $data->string as $string ) {
			$entry = new Translation_Entry();
			$entry->singular = (string)$string['name'];
			$translation = $this->unescape( (string)$string[0] );
			$entry->extracted_comments = 'Original: ' . $translation;
			$entry->translations = array( $translation );
			$entries->add_entry( $entry );
		}
		return $entries;		
	}

	
	function unescape( $string ) {
		return stripcslashes( $string );		
	}
	
	function escape( $string ) {
		$string = addcslashes( $string, "'\n");
		$string = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $string );
		return $string;
	}
}

GP::$formats['android'] = new GP_Format_Android;