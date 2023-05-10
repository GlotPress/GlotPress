<?php
/**
 * GlotPress Format PHP class
 *
 * @since 4.0.0
 *
 * @package GlotPress
 */

/**
 * Format class used to support PHP file format.
 *
 * @since 4.0.0
 */
class GP_Format_PHP extends GP_Format {
	/**
	 * Name of file format, used in file format dropdowns.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $name = 'PHP (.php)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $extension = 'php';

	/**
	 * Generates a string the contains the $entries to export in the PHP file format.
	 *
	 * @since 4.0.0
	 *
	 * @param GP_Project         $project         The project the strings are being exported for, not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Locale          $locale          The locale object the strings are being exported for. not used
	 *                                            in this format but part of the scaffold of the parent object.
	 * @param GP_Translation_Set $translation_set The locale object the strings are being
	 *                                            exported for. not used in this format but part
	 *                                            of the scaffold of the parent object.
	 * @param GP_Translation     $entries         The entries to export.
	 * @return string The exported PHP string.
	 */
	public function print_exported_file( $project, $locale, $translation_set, $entries ) {
		$result = array(
			'generator'                 => 'GlotPress/' . GP_VERSION,
			'translation-revision-date' => GP::$translation->last_modified( $translation_set ) . '+0000',
			'plural-forms'              => "nplurals=$locale->nplurals; plural=$locale->plural_expression;",
			'messages'                  => array(),
		);

		/* @var Translation_Entry $entry */
		foreach ( $entries as $entry ) {
			$key = $entry->context ? $entry->context . chr( 4 ) . $entry->singular : $entry->singular;

			$result['messages'][ $key ] = array_filter(
				$entry->translations,
				function ( $translation ) {
					return null !== $translation;
				}
			);
		}

		return '<?php' . PHP_EOL . 'return ' . $this->var_export( $result, true ) . ';';
	}

	/**
	 * Reads a set of original strings from a PHP file.
	 *
	 * @since 4.0.0
	 *
	 * @param string $file_name The name of the uploaded PHP file.
	 * @return Translations|bool The extracted originals on success, false on failure.
	 */
	public function read_originals_from_file( $file_name ) {
		// TODO: Either implement in a secure way or mark as unsupported.
		return false;
	}

	/**
	 * Reads a set of translations from a PHP file.
	 *
	 * @since 4.0.0
	 *
	 * @param string     $file_name The name of the uploaded properties file.
	 * @param GP_Project $project   Unused. The project object to read the translations into.
	 * @return Translations|bool The extracted translations on success, false on failure.
	 */
	public function read_translations_from_file( $file_name, $project = null ) {
		return $this->read_originals_from_file( $file_name );
	}

	/**
	 * Outputs or returns a parsable string representation of a variable.
	 *
	 * Like {@see var_export()} but "minified", using short array syntax
	 * and no newlines.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $value       The variable you want to export.
	 * @param bool  $return_only Optional. Whether to return the variable representation instead of outputing it. Default false.
	 * @return string|void The variable representation or void.
	 */
	private function var_export( $value, $return_only = false ) {
		if ( ! is_array( $value ) ) {
			return var_export( $value, $return_only );
		}

		$entries = array();
		foreach ( $value as $key => $val ) {
			$entries[] = var_export( $key, true ) . '=>' . $this->var_export( $val, true );
		}

		$code = '[' . implode( ',', $entries ) . ']';
		if ( $return_only ) {
			return $code;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $code;
	}
}

GP::$formats['php'] = new GP_Format_PHP();
