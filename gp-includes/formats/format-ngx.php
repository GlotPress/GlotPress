<?php
/**
 * GlotPress Format NGX Translate class
 *
 * @since 2.4.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support NGX Translate JSON file format.
 *
 * @since 2.4.0
 */
class GP_Format_NGX extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $name = 'NGX-Translate (.json)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 2.4.0
	 *
	 * @var string
	 */
	public $extension = 'ngx.json';

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
	 * @since 2.4.0
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
			$key = $entry->context;
			$arraykeyindex = strpos( $key, '[' );
			if ( false !== $arraykeyindex ) {
				$entrykey = substr( $key, 0, $arraykeyindex );
				$keypair = substr( $key, $arraykeyindex + 1, strlen( $key ) - $arraykeyindex - 2 );
				$valuepair = array(
					'key' => $keypair,
					'translation' => $entry->translations[0],
				);
				if ( is_array( $result[ $entrykey ] ) ) {
					array_push( $result[ $entrykey ], $valuepair );
				} else {
					$result[ $entrykey ] = array();
					array_push( $result[ $entrykey ], $valuepair );
				}
			} else {
				if ( null === $key ) {
					$key = $entry->singular;
				}

				$result[ $key ] = $entry->translations[0];
			}
		}

		/**
		 * Filter whether the exported JSON should be pretty printed.
		 *
		 * @since 2.4.0
		 *
		 * @param bool $pretty_print Whether pretty print should be enabled or not. Default false.
		 */
		$pretty_print = apply_filters( 'gp_json_export_pretty_print', false );

		return wp_json_encode( $result, ( true === $pretty_print ) ? JSON_PRETTY_PRINT : 0 );
	}

	/**
	 * Reads a set of original strings from a JSON file.
	 *
	 * @since 2.4.0
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
			if ( is_array( $value ) ) {
				foreach ( $value as $keyelem => $valueelem ) {
					if ( isset( $valueelem['key'] )
					  && isset( $valueelem['translation'] ) ) {
						$args = array(
							'singular' => $valueelem['translation'],
							'context' => $key . '[' . $valueelem['key'] . ']',
						);
						$entries->add_entry( new Translation_Entry( $args ) );
					}
				};
			} else {
				$args = array(
					'singular' => $value,
					'context' => $key,
				);
				$entries->add_entry( new Translation_Entry( $args ) );
			}
		}

		return $entries;
	}

	/**
	 * Decode a JSON file.
	 *
	 * @since 2.4.0
	 *
	 * @param string $file_name The name of the JSON file to decode.
	 * @return decode JSON file as an array.
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

GP::$formats['ngx'] = new GP_Format_NGX();
