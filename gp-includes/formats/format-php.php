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
	public $name = 'PHP (.l10n.php)';

	/**
	 * File extension of the file format, used to autodetect formats and when creating the output file names.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $extension = 'l10n.php';

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
		$language_code = $this->get_language_code( $locale );
		if ( false === $language_code ) {
			$language_code = $locale->slug;
		}

		$current        = $project;
		$project_tree   = array();
		$project_tree[] = $current->name;

		while ( $current->parent_project_id > 0 ) {
			$current        = GP::$project->get( $current->parent_project_id );
			$project_tree[] = $current->name;
		}

		$project_tree = array_reverse( $project_tree );

		$project_id_version = implode( ' - ', $project_tree );

		/**
		 * Filter the project name and version header before export.
		 *
		 * @since 4.0.0
		 *
		 * @param string $project_id_version The default project name/version to use in the header and
		 *                                   comments ( "Parent - Child - GrandChild - etc." by default).
		 * @param array  $project_tree       An array of the parent/child project tree, ordered from Parent
		 *                                   to child to grandchild to etc...
		 */
		$project_id_version = apply_filters( 'gp_php_export_project_id_version', $project_id_version, $project_tree );

		$result = array(
			'x-generator'               => 'GlotPress/' . GP_VERSION,
			'translation-revision-date' => GP::$translation->last_modified( $translation_set ) . '+0000',
			'plural-forms'              => "nplurals=$locale->nplurals; plural=$locale->plural_expression;",
			'project-id-version'        => $project_id_version,
			'language'                  => $language_code,
			'messages'                  => array(),
		);

		/* @var Translation_Entry $entry */
		foreach ( $entries as $entry ) {
			$key = $entry->context ? $entry->context . chr( 4 ) . $entry->singular : $entry->singular;

			$result['messages'][ $key ] = implode(
				"\0",
				array_filter(
					$entry->translations,
					static function ( $translation ) {
						return null !== $translation;
					}
				)
			);
		}

		return '<?php' . PHP_EOL . 'return ' . $this->var_export( $result ) . ';';
	}

	/**
	 * Reads a set of original strings from a PHP file.
	 *
	 * @since 4.0.0
	 *
	 * @param string $file_name The name of the uploaded PHP file.
	 * @return false Always returns false, as this is not currently implemented.
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
	 * @return false Always returns false, as this is not currently implemented.
	 */
	public function read_translations_from_file( $file_name, $project = null ) {
		// TODO: Either implement in a secure way or mark as unsupported.
		return false;
	}

	/**
	 * Determines if the given array is a list.
	 *
	 * An array is considered a list if its keys consist of consecutive numbers from 0 to count($array)-1.
	 *
	 * Polyfill for array_is_list() in PHP 8.1.
	 *
	 * @see https://github.com/symfony/polyfill-php81/tree/main
	 *
	 * @since 4.0.0
	 *
	 * @codeCoverageIgnore
	 *
	 * @param array<mixed> $arr The array being evaluated.
	 * @return bool True if array is a list, false otherwise.
	 */
	private function array_is_list( $arr ) {
		if ( function_exists( 'array_is_list' ) ) {
			return array_is_list( $arr );
		}

		if ( ( array() === $arr ) || ( array_values( $arr ) === $arr ) ) {
			return true;
		}

		$next_key = -1;

		foreach ( $arr as $k => $v ) {
			if ( ++$next_key !== $k ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Outputs or returns a parsable string representation of a variable.
	 *
	 * Like {@see var_export()} but "minified", using short array syntax
	 * and no newlines.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $value The variable you want to export.
	 * @return string The variable representation.
	 */
	private function var_export( $value ) {
		if ( ! is_array( $value ) ) {
			return var_export( $value, true );
		}

		$entries = array();

		$is_list = $this->array_is_list( $value );

		foreach ( $value as $key => $val ) {
			$entries[] = $is_list ? $this->var_export( $val ) : var_export( $key, true ) . '=>' . $this->var_export( $val );
		}

		return '[' . implode( ',', $entries ) . ']';
	}
}

GP::$formats['php'] = new GP_Format_PHP();
