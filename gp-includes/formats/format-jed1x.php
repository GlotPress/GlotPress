<?php
/**
 * GlotPress Format Jed 1.x class
 *
 * @since 2.3.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support Jed 1.x compatible JSON file format.
 *
 * @since 2.3.0
 */
class GP_Format_Jed1x extends GP_Format_JSON {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $name = 'Jed 1.x (.json)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $extension = 'jed.json';

	/**
	 * Generates a string the contains the $entries to export in the Jed 1.x compatible JSON file format.
	 *
	 * @since 2.3.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 * @return string The exported Jed 1.x compatible JSON string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$language_code = $this->get_language_code( $locale );
		if ( false === $language_code ) {
			$language_code = $locale->slug;
		}

		$result = array(
			'translation-revision-date' => GP::$translation->last_modified( $translation_set ) . '+0000',
			'generator'                 => 'GlotPress/' . GP_VERSION,
			'domain'                    => 'messages',
			'locale_data'               => array(
				'messages' => array(
					'__GP_EMPTY__' => array(
						'domain'       => 'messages',
						'plural-forms' => sprintf( 'nplurals=%1$s; plural=%2$s;', $locale->nplurals, $locale->plural_expression ),
						'lang'         => $language_code,
					),
				),
			),
		);

		/* @var Translation_Entry $entry */
		foreach ( $entries as $entry ) {
			$key = $entry->context ? $entry->context . chr( 4 ) . $entry->singular : $entry->singular;

			$result['locale_data']['messages'][ $key ] = array_filter( $entry->translations, function ( $translation ) {
				return null !== $translation;
			} );
		}

		/** This filter is documented in gp-includes/formats/format-json.php */
		$pretty_print = apply_filters( 'gp_json_export_pretty_print', false );

		$result = wp_json_encode( $result, ( true === $pretty_print ) ? JSON_PRETTY_PRINT : 0 );

		/*
		 * Replace '__GP_EMPTY__' with an actual empty string.
		 *
		 * Empty object property names are not supported in PHP, so they would get lost.
		 *
		 * Note: When decoding, PHP replaces empty strings with '_empty_'.
		 *
		 * @link https://bugs.php.net/bug.php?id=50867
		 */
		return str_replace( '__GP_EMPTY__', '', $result );
	}

	/**
	 * Reads a set of original strings from a JSON file.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file_name The name of the uploaded JSON file.
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		$json = $this->decode_json_file( $file_name );

		if ( ! $json ) {
			return false;
		}

		$entries = new Translations();

		foreach ( $json['locale_data'][ $json['domain'] ] as $key => $value ) {
			if ( '' === $key ) {
				continue;
			}

			$args = array(
				'singular' => $key,
			);

			if ( false !== strpos( $key, chr( 4 ) ) ) {
				$key              = explode( chr( 4 ), $key );
				$args['context']  = $key[0];
				$args['singular'] = $key[1];
			}

			$value = (array) $value;

			if ( isset( $value[0] ) ) {
				$args['translations'] = $value[0];
			}

			if ( isset( $value[1] ) ) {
				$args['plural'] = $value[1];
			}

			$entries->add_entry( new Translation_Entry( $args ) );
		}

		return $entries;
	}

	/**
	 * Decodes a JSON string and checks for needed array keys.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file_name The name of the JSON file to parse.
	 * @return array|false The encoded value or false on failure.
	 */
	protected function decode_json_file( $file_name ) {
		$json = parent::decode_json_file( $file_name );

		if ( ! $json ) {
			return false;
		}

		if ( ! isset( $json['domain'] ) ||
		     ! isset( $json['locale_data'] ) ||
		     ! isset( $json['locale_data'][ $json['domain'] ] )
		) {
			return false;
		}

		return $json;
	}
}

GP::$formats['jed1x'] = new GP_Format_Jed1x();
