<?php

/**
 * GlotPress Format base class. It is supposed to be inherited.
 */
abstract class GP_Format {

	public $name             = '';
	public $extension        = '';
	public $alt_extensions   = array();
	public $filename_pattern = '%s-%s';

	/**
	 * Maximum length of a single value included in a log message.
	 *
	 * @since 4.1.0
	 *
	 * @var int
	 */
	const LOG_VALUE_MAX_LENGTH = 200;

	abstract public function print_exported_file( $project, $locale, $translation_set, $entries );
	abstract public function read_originals_from_file( $file_name );

	/**
	 * Gets the list of supported file extensions.
	 *
	 * @since 2.0.0
	 *
	 * @return array Supported file extensions.
	 */
	public function get_file_extensions() {
		return array_merge( array( $this->extension ), $this->alt_extensions );
	}

	/**
	 * Prepares a value read from an uploaded file for inclusion in a log message.
	 *
	 * Whitespace is collapsed and the length is capped so that the value stays on one
	 * readable line whatever the file contained.
	 *
	 * @since 4.1.0
	 *
	 * @param string $value The value to prepare.
	 * @return string The prepared value.
	 */
	protected function sanitize_for_log( $value ) {
		$value = (string) $value;

		// A value read from an uploaded file is not guaranteed to be valid UTF-8. Both
		// steps use the byte-wise functions when it is not, so that neither depends on
		// an encoding the value does not have.
		if ( preg_match( '//u', $value ) ) {
			$value = preg_replace( '/\s+/u', ' ', $value );

			if ( mb_strlen( $value ) > self::LOG_VALUE_MAX_LENGTH ) {
				$value = mb_substr( $value, 0, self::LOG_VALUE_MAX_LENGTH ) . '…';
			}

			return $value;
		}

		$value = preg_replace( '/\s+/', ' ', $value );

		if ( strlen( $value ) > self::LOG_VALUE_MAX_LENGTH ) {
			$value = substr( $value, 0, self::LOG_VALUE_MAX_LENGTH ) . '...';
		}

		return $value;
	}

	public function read_translations_from_file( $file_name, $project = null ) {
		if ( is_null( $project ) ) {
			return false;
		}

		$translations = $this->read_originals_from_file( $file_name );

		if ( ! $translations ) {
			return false;
		}

		$originals        = GP::$original->by_project_id( $project->id );
		$new_translations = new Translations();

		foreach ( $translations->entries as $key => $entry ) {
			// we have been using read_originals_from_file to parse the file
			// so we need to swap singular and translation
			if ( $entry->context == $entry->singular ) {
				$entry->translations = array();
			} else {
				$entry->translations = array( $entry->singular );
			}

			$entry->singular = null;

			foreach ( $originals as $original ) {
				if ( $original->context == $entry->context ) {
					$entry->singular = $original->singular;
					break;
				}
			}

			if ( ! $entry->singular ) {
				error_log(
					sprintf(
						/* translators: 1: Context. 2: Project ID. */
						__( 'Missing context %1$s in project #%2$d', 'glotpress' ),
						$this->sanitize_for_log( $entry->context ),
						$project->id
					)
				);
				continue;
			}

			$new_translations->add_entry( $entry );
		}

		return $new_translations;
	}

	/**
	 * Create a string that represents the value for the "Language:" header for an export file.
	 *
	 * @since 2.1.0
	 *
	 * @param GP_Locale $locale The locale object.
	 *
	 * @return string|false Returns false if the locale object does not have any iso_639 language code, otherwise returns the shortest possible language code string.
	 */
	protected function get_language_code( $locale ) {
		$ret = '';

		if ( $locale->lang_code_iso_639_1 ) {
			$ret = $locale->lang_code_iso_639_1;
		} elseif ( $locale->lang_code_iso_639_2 ) {
			$ret = $locale->lang_code_iso_639_2;
		} elseif ( $locale->lang_code_iso_639_3 ) {
			$ret = $locale->lang_code_iso_639_3;
		}

		if ( '' === $ret ) {
			return false;
		}

		$ret = strtolower( $ret );

		if ( null !== $locale->country_code && 0 !== strcasecmp( $ret, $locale->country_code ) ) {
			$ret .= '_' . strtoupper( $locale->country_code );
		}

		return $ret;
	}
}
