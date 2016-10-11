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
		$data = simplexml_load_string( file_get_contents( $file_name ) );
		libxml_use_internal_errors( $errors );

		if ( ! is_object( $data ) )
			return false;

		$entries = new Translations;

		foreach ( $data->string as $string ) {
			if ( isset( $string['translatable'] ) && 'false' == $string['translatable'] ) {
				continue;
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
