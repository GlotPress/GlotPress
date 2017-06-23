<?php
/**
 * GlotPress Format JSON class
 *
 * @since 2.3.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support JSON file format.
 *
 * @since 2.3.0
 */
class GP_Format_JSON extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $name = 'JSON (.json)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public $extension = 'json';

	/**
	 * Which plural rules to use for this format.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $plurals_format = 'gettext';

	/**
	 * Generates a string the contains the $entries to export in the JSON file format.
	 *
	 * @since 2.3.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for. not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 * @return string The exported JSON string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = array();

		/* @var Translation_Entry $entry */
		foreach ( $entries as $entry ) {
			$key = $entry->context ? $entry->context . chr( 4 ) . $entry->singular : $entry->singular;

			$result[ $key ] = array_filter( $entry->translations, function ( $translation ) {
				return null !== $translation;
			} );
		}

		/**
		 * Filter whether the exported JSON should be pretty printed.
		 *
		 * @since 2.3.0
		 *
		 * @param bool $pretty_print Whether pretty print should be enabled or not. Default false.
		 */
		$pretty_print = apply_filters( 'gp_json_export_pretty_print', false );

		return wp_json_encode( $result, ( true === $pretty_print ) ? JSON_PRETTY_PRINT : 0 );
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

		foreach ( $json as $key => $value ) {
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
	 * Reads a set of translations from a JSON file.
	 *
	 * @since 2.3.0
	 *
	 * @param string     $file_name The name of the uploaded properties file.
	 * @param GP_Project $project   Unused. The project object to read the translations into.
	 * @return Translations|bool The extracted translations on success, false on failure.
	 */
	public function read_translations_from_file( $file_name, $project = null ) {
		return $this->read_originals_from_file( $file_name );
	}

	/**
	 * Loads a given JSON file and decodes its content.
	 *
	 * @since 2.3.0
	 *
	 * @param string $file_name The name of the JSON file to parse.
	 * @return array|false The encoded value or false on failure.
	 */
	protected function decode_json_file( $file_name ) {
		if ( ! file_exists( $file_name ) ) {
			return false;
		}

		$file = file_get_contents( $file_name );

		if ( ! $file ) {
			return false;
		}

		$json = json_decode( $file, true );

		if ( null === $json ) {
			return false;
		}

		return $json;
	}
}

GP::$formats['json'] = new GP_Format_JSON();
