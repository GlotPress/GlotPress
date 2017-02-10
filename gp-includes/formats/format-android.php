<?php

class GP_Format_Android extends GP_Format {

	public $name = 'Android XML (.xml)';
	public $extension = 'xml';

	public $exported = '';

	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$this->exported = '';
		$this->line( '<?xml version="1.0" encoding="utf-8"?>' );

		$this->line( '<!--' );
		$this->line( 'Translation-Revision-Date: ' . GP::$translation->last_modified( $translation_set ) . '+0000' );
		$this->line( "Plural-Forms: nplurals={$locale->nplurals}; plural={$locale->plural_expression};" );
		$this->line( 'Generator: GlotPress/' . GP_VERSION );

		$language_code = $this->get_language_code( $locale );
		if ( false !== $language_code ) {
			$this->line( 'Language: ' . $language_code );
		}

		$this->line( '-->' );

		$this->line( '<resources>' );
		$string_array_items = array();

		foreach( $entries as $entry ) {
			if ( preg_match('/.+\[\d+\]$/', $entry->context ) ) {
				//Array item found
				$string_array_items[] = $entry;
				continue;
			}

			if ( empty( $entry->context ) ) {
				$entry->context = $entry->singular;
			}

			$id = preg_replace( '/[^a-zA-Z0-9_]/U', '_', $entry->context );

			$this->line( '<string name="' . $id . '">' . $this->escape( $entry->translations[0] ) . '</string>', 1 );
		}

		$this->string_arrays( $string_array_items );

		$this->line( '</resources>' );

		return $this->exported;
	}

	public function read_originals_from_file( $file_name ) {
		$errors = libxml_use_internal_errors( true );
		$contents = file_get_contents( $file_name );

		/*
		 * Android strings can use <xliff:g> tags to indicate a part of the string should NOT be translated.
		 *
		 * See the "Mark message parts that should not be translated" section of https://developer.android.com/distribute/tools/localization-checklist.html
		 *
		 * Unfortunately SimpleXML will parse these as valid XML tags, which we don't want so encapsulate them in a CDATA structure
		 * that we can tell SimpleXML to ignore.
		*/
		$contents = str_ireplace( '<xliff:g', '--xlifftag--xliff:g', $contents );
		$contents = str_ireplace( '</xliff:g>', '--xlifftag--/xliff:g>', $contents );

		$data = simplexml_load_string( $contents, null, LIBXML_NOCDATA );
		libxml_use_internal_errors( $errors );

		if ( ! is_object( $data ) )
			return false;

		$entries = new Translations;

		foreach ( $data->string as $string ) {
			if ( isset( $string['translatable'] ) && 'false' == $string['translatable'] ) {
				continue;
			}

			$xliff_info = $this->extract_xliff_info( (string)$string[0] );
			
			if ( false !== $xliff_info ) {
				$string[0] = $xliff_info['string'];
				$string['comment'] .= $xliff_info['description'];
			}
			
			$entry = new Translation_Entry();
			$entry->context = (string)$string['name'];
			$entry->singular = $this->unescape( (string)$string[0] );
			$entry->translations = array();

			if ( isset( $string['comment'] ) && $string['comment'] ) {
				$entry->extracted_comments = $string['comment'];
			}

			$entries->add_entry( $entry );
		}

		foreach ( $data->{'string-array'} as $string_array )
		{
			if ( isset( $string_array['translatable'] ) && 'false' == $string_array['translatable'] ) {
				continue;
			}

			$array_name = (string) $string_array['name'];
			$item_index = 0;

			foreach ( $string_array->item as $item )
			{
				$entry               = new Translation_Entry();
				$entry->context      = $array_name . "[$item_index]";
				$entry->singular     = $this->unescape( $item[0] );
				$entry->translations = array();

				$entries->add_entry( $entry );

				$item_index++;
			}
		}

		return $entries;
	}

	function extract_xliff_info( $string ) {
		// Define the initial xliff tag to look for.
		$search = '--xlifftag--';
		
		/*
		 * If it's not in the string, don't do any more processing.  Note we don't need to worry about
		 * case sensitivity here as a search and replace was done earlier which forced them all to the
		 * exact string above.
		 */
		if ( false === strstr( $string, $search ) ) {
			return false;
		}
		
		$string = str_ireplace( $search, '<', $string );
		
		// Break apart the string in case there are multiple xliff's in it.
		$parts = explode( '<xliff:g', $string );

		// Setup the results array, part 0 will never need to be processed so automatically add it to the returned string.
		$result = array();
		$result['string'] = $parts[0];
		$result['comment'] = '';
		$result['description'] = '';

		// As we can skip the first part, loop through only the remaining parts.
		for ( $i = 1; $i < sizeof( $parts ); $i++ ) {
			// Add back the part we stripped out during the explode() above.
			$current =  '<xliff:g' . $parts[$i];
		
			$matches = array();

			/*
			 * Break apart the entire string in to 5 parts:
			 * 
			 *     0 = The full string.
			 *     1 = Any text before the xliff tag.
			 *     2 = The opening xliff tag.
			 *     3 = The actual text to be translated.
			 *     4 = The closing xliff tag.
			 *     5 = The rest of the string.
			 */
			if ( false !== preg_match( '/(.*)(<xliff:g.*>)(.*)(<\/xliff:g>)(.*)/i', $current, $matches ) ) {
				// If we have a match add to the two parameters to return the correct parts of the match.
				$result['string'] .= $matches[1] . $matches[3] . $matches[5];
				$result['comment'] .= ' ' . $matches[2] . $matches[3] . $matches[4];
				
				$component = $matches[3];
				$text = '';
				
				$id = preg_match( '/.*id="(.*)".*/iU', $result['comment'], $matches );
				if ( false !== $id ) {
					$id = $matches[1];
				}
				
				$example = preg_match( '/.*example="(.*)".*/iU', $result['comment'], $matches );
				if ( false !== $example ) {
					$example = $matches[1];
				}
						
				if ( false !== $id && false !== $example ) {
					$text = sprintf( __( 'This string has content that should not be translated, the "%s" component of the original, which is identified as the "%s" attribute by the developer may be replaced at run time with text like this: %s', 'glotpress' ), $component, $id, $example );
				} else if ( false !== $id ) {
					$text = sprintf( __( 'This string has content that should not be translated, the "%s" component of the original, which is identified as "%s" by the developer and is not intendent to be translated.', 'glotpress' ), $component, $id );
				} else if ( false !== $example ) {
					$text = sprintf( __( 'This string has content that should not be translated, the "%s" component of the original may be replaced at run time with text like this: %s', 'glotpress' ), $component, $example );
				}
						
				$result['description'] .= ' ' . $text;
			} else {
				// If we don't, just append the current string to the result.
				$result['string'] .= ' ' . $current;
			}
		}	

		$result['comment'] = trim( $result['comment'] );
		$result['description'] = trim( $result['description'] );
		
		return $result;
	}
	
	private function line( $string, $prepend_tabs = 0 ) {
		$this->exported .= str_repeat( "\t", $prepend_tabs ) . "$string\n";
	}

	private function string_arrays($entries)
	{
		$mapping = array();

		uasort( $entries, array( $this, 'cmp_context' ) );

		foreach( $entries as $entry )
		{
			$array_name = preg_replace( '/\[\d+\]$/', '', $entry->context );

			if ( ! isset( $mapping[ $array_name ] ) )
				$mapping[ $array_name ] = array();

			// Because Android doesn't fallback on the original locale
			// in string-arrays, we fill the non-translated ones with original locale string.
			$value = $entry->translations[0];

			if ( ! $value )
				$value = $entry->singular;

			$mapping[ $array_name ][] = $this->escape( $value );
		}

		foreach ( array_keys( $mapping ) as $array_name )
		{
			$this->line( '<string-array name="' . $array_name . '">', 1 );

			foreach ( $mapping[ $array_name ] as $item )
			{
				$this->line('<item>' . $item . '</item>', 2);
			}

			$this->line( '</string-array>', 1 );
		}
	}

	private function cmp_context( $a, $b ) {
		return strnatcmp( $a->context, $b->context );
	}

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
	protected function escape( $string ) {
		$string = addcslashes( $string, "'\n" );
		$string = str_replace( array( '&', '<' ), array( '&amp;', '&lt;' ), $string );

		// Android strings that start with an '@' are references to other strings and need to be escaped.  See GH469.
		if ( gp_startswith( $string, '@' ) ) {
			$string = '\\' . $string;
		}

		return $string;
	}

}

GP::$formats['android'] = new GP_Format_Android;
