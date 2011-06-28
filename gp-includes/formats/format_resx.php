<?php

class GP_Format_ResX {
	
	var $extension = 'resx.xml';
	
	var $exported = '';
	
	function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}
	
	function res_header( $name, $value ) {
		$this->line( '<resheader name="'.$name.'">', 1 );
		$this->line( '<value>'.$value.'</value>', 2 );
		$this->line( '</resheader>', 1 );		
	}
		
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );
		$this->line( '<root>' );
		$this->res_header( 'resmimetype', 'text/microsoft-resx' );
		$this->res_header( 'version', '2.0' );
		$this->res_header( 'reader', 'System.Resources.ResXResourceReader, System.Windows.Forms, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089' );
		$this->res_header( 'writer', 'System.Resources.ResXResourceReader, System.Windows.Forms, Version=4.0.0.0, Culture=neutral, PublicKeyToken=b77a5c561934e089' );
		foreach( $entries as $entry ) {
			if ( !preg_match( '/^[a-zA-Z0-9_]+$/', $entry->context ) ) {
				error_log( 'ResX Export: Bad Entry: '. $entry->context );
				continue;
			}
			$this->line( '<data name="' . $entry->context . '" xml:space="preserve">', 1 );
			$this->line( '<value>' . $this->escape( $entry->translations[0] ) . '</value>', 2 );
			if ( isset( $entry->extracted_comments ) && $entry->extracted_comments ) {
				$this->line( '<comment>' . $this->escape( $entry->extracted_comments ) . '</comment>', 2 );
			}
			$this->line( '</data>', 1 );
		}
		$this->line( '</root>' );
		return $this->exported;
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
			$entry->translations = array( $entry->singular );
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
		$errors = libxml_use_internal_errors( 'true' );
		$data = simplexml_load_string( file_get_contents( $file_name ) );
		libxml_use_internal_errors( $errors );
		if ( !is_object( $data ) ) return false;
		$entries = new Translations;
		foreach( $data->data as $string ) {
			$entry = new Translation_Entry();
			if ( isset( $string['type'] ) && gp_in( 'System.Resources.ResXFileRef', (string)$string['type'] ) ) {
				continue;
			}
			$entry->context = (string)$string['name'];
			$entry->singular = $this->unescape( (string)$string->value );
			if ( isset( $string->comment ) && $string->comment ) {
				$entry->extracted_comments = (string)$string->comment;
			}
			$entry->translations = array();
			$entries->add_entry( $entry );
		}
		return $entries;
	}

	
	function unescape( $string ) {
		return $string;
	}
	
	function escape( $string ) {
		$string = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $string );
		return $string;
	}
}

GP::$formats['resx'] = new GP_Format_ResX;