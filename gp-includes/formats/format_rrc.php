<?php

class GP_Format_RRC {
	
	var $extension = 'rrc';
	
	function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$rrc = array();
		foreach( $entries as $entry ) {
			if ( !$entry->context ) continue;
			if ( preg_match( '/^(.+)\|(\d+)$/', $entry->context, $matches ) ) {
				$key = $matches[1];
				$index = $matches[2];
				$rrc[$key][$index] = $entry->translations[0];
			} else {
				$rrc[$entry->context] = $entry->translations[0];
			}
		}
		$result = '';
		foreach( $rrc as $key => $translation ) {
			if ( !is_array( $translation ) ) {
				$result .= "$key#0=" . $this->quote( $translation ) . ";\n";
			} else {
				$result .= "$key#0={\n";
				foreach( $translation as $single_translation ) {
					$result .= "\t" . $this->quote( $single_translation ) . ",\n";
				}
				$result .= "};\n";
			}
		}
		return $result;
	}
	
	function escape( $string ) {
		$string = addcslashes( $string, '"' );
		$string = str_replace( array("\n", "\t", "\r"), array('\n', '\t', '\r'), $string );
		return $string;
	}
	
	function quote( $string ) {
		return '"' . $this->escape( $string ) . '"';
	}	
}

GP::$formats['rrc'] = new GP_Format_RRC;