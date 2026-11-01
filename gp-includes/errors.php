<?php
/**
 * Translation errors API
 *
 * @package GlotPress
 * @since 4.0.0
 */

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/**
 * Class used to handle translation errors.
 *
 * @since 4.0.0
 */
class GP_Translation_Errors {
	/**
	 * List of callbacks.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @var callable[]
	 */
	public $callbacks = array();

	/**
	 * Adds a callback for a new error.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string   $id       Unique ID of the callback.
	 * @param callable $callback The callback.
	 */
	public function add( $id, $callback ) {
		$this->callbacks[ $id ] = $callback;
	}

	/**
	 * Removes an existing callback for an error.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $id Unique ID of the callback.
	 */
	public function remove( $id ) {
		unset( $this->callbacks[ $id ] );
	}

	/**
	 * Checks whether a callback exists for an ID.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $id Unique ID of the callback.
	 * @return bool True if exists, false if not.
	 */
	public function has( $id ) {
		return isset( $this->callbacks[ $id ] );
	}

	/**
	 * Checks translations for any error.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param GP_Original $gp_original  The original object.
	 * @param string[]    $translations The translations.
	 * @param GP_Locale   $locale       The locale.
	 * @return array|null Null if no issues have been found, otherwise an array
	 *                    with errors.
	 */
	public function check( $gp_original, $translations, $locale ) {
		$singular = $gp_original->singular;
		$plural   = $gp_original->plural;
		$comment  = $gp_original->comment;
		$problems = array();
		foreach ( $translations as $translation_index => $translation ) {
			if ( ! $translation ) {
				continue;
			}

			$skip = array(
				'singular' => false,
				'plural'   => false,
			);
			if ( null !== $plural ) {
				$numbers_for_index = $locale->numbers_for_index( $translation_index );
				if ( 1 === $locale->nplurals ) {
					$skip['singular'] = true;
				} elseif ( in_array( 1, $numbers_for_index, true ) ) {
					$skip['plural'] = true;
				} else {
					$skip['singular'] = true;
				}
			}

			foreach ( $this->callbacks as $callback_id => $callback ) {
				if ( ! $skip['singular'] ) {
					$singular_test = $callback( $singular, $translation, $gp_original, $locale );
					if ( true !== $singular_test ) {
						$problems[ $translation_index ][ $callback_id ] = $singular_test;
					}
				}
				if ( null !== $plural && ! $skip['plural'] ) {
					$plural_test = $callback( $plural, $translation, $gp_original, $locale );
					if ( true !== $plural_test ) {
						$problems[ $translation_index ][ $callback_id ] = $plural_test;
					}
				}
			}
		}
		return empty( $problems ) ? null : $problems;
	}
}

/**
 * Class used to handle translation errors.
 *
 * @since 4.0.0
 */
class GP_Builtin_Translation_Errors {
	/**
	 * Matches a sprintf() placeholder or an escaped percent sign.
	 *
	 * The first group holds the argument number of a positional placeholder.
	 * Specifiers are case-sensitive because sprintf() throws a ValueError on
	 * unknown ones like `%S`, while `%X` and `%E` are valid.
	 *
	 * @since 4.2.0
	 *
	 * @var string
	 */
	const SPRINTF_PLACEHOLDER_REGEX = '!%(?:(\d+\$(?:\d+)?)?(?:[0-9]+|\*)?(?:\.[0-9]+|\*)?(?:l?[bcdeEfFgGhHosuxX]|%))!';

	/**
	 * Adds an error for adding unexpected percent signs in a sprintf-like string.
	 *
	 * This is to catch translations for originals like this:
	 *  - Original: `<a href="%s">100 percent</a>`
	 *  - Submitted translation: `<a href="%s">100%</a>`
	 *  - Proper translation: `<a href="%s">100%%</a>`
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 * @return bool|string
	 */
	public function error_unexpected_sprintf_token( $original, $translation ) {
		$unexpected_tokens = array();

		$original_without_placeholder = preg_replace( self::SPRINTF_PLACEHOLDER_REGEX, '', $original );

		$is_sprintf = $original_without_placeholder !== $original && false === strpos( $original_without_placeholder, '%' );

		// Find any percents that are not valid or escaped.
		if ( $is_sprintf ) {
			$translation_without_placeholder = preg_replace( self::SPRINTF_PLACEHOLDER_REGEX, '', $translation );

			$p = strpos( $translation_without_placeholder, '%' );
			while ( false !== $p ) {
				$unexpected_tokens[] = trim( substr( $translation_without_placeholder, max( 0, $p - 2 ), 4 ) ) . ' (unescaped %, use %% instead)';

				$p = strpos( $translation_without_placeholder, '%', $p + 1 );
			}
		}
		if ( $unexpected_tokens ) {
			return sprintf(
				/* translators: %s: Placeholders. */
				__( 'The translation contains the following unexpected placeholders: %s', 'glotpress' ),
				implode( ', ', $unexpected_tokens )
			);
		}

		return true;
	}

	/**
	 * Adds an error for a translation that needs more sprintf() arguments than the original.
	 *
	 * The code passes the arguments of the original, so a translation that
	 * references one more makes sprintf() throw an ArgumentCountError on PHP 8.
	 * Fewer placeholders or a different type are harmless and stay allowed.
	 *
	 * Plural forms are checked against both forms of the original, since the
	 * code passes the same arguments to each of them and a locale can use the
	 * number in a form that the English singular spells out, like "One item".
	 *
	 * @since 4.2.0
	 * @access public
	 *
	 * @param string           $original    The original string.
	 * @param string           $translation The translated string.
	 * @param GP_Original|null $gp_original Optional. The original object.
	 * @return string|true True if check is OK, otherwise error message.
	 */
	public function error_extra_sprintf_placeholders( $original, $translation, $gp_original = null ) {
		$originals = array( $original );
		if ( $gp_original && null !== $gp_original->plural ) {
			$originals = array( $gp_original->singular, $gp_original->plural );
		}

		$is_sprintf          = false;
		$available_arguments = 0;
		foreach ( $originals as $text ) {
			$text_without_placeholders = preg_replace( self::SPRINTF_PLACEHOLDER_REGEX, '', $text );
			if ( false !== strpos( $text_without_placeholders, '%' ) ) {
				return true;
			}

			$is_sprintf = $is_sprintf || $text_without_placeholders !== $text;
			foreach ( $this->get_sprintf_placeholders( $text ) as list( , $argument ) ) {
				$available_arguments = max( $available_arguments, $argument );
			}
		}

		if ( ! $is_sprintf ) {
			return true;
		}

		$extra_placeholders = array();
		foreach ( $this->get_sprintf_placeholders( $translation ) as list( $placeholder, $argument ) ) {
			if ( $argument > $available_arguments ) {
				$extra_placeholders[] = $placeholder;
			}
		}

		if ( ! $extra_placeholders ) {
			return true;
		}

		return sprintf(
			/* translators: %s: Placeholders. */
			__( 'The translation contains more placeholders than the original: %s', 'glotpress' ),
			implode( ', ', array_unique( $extra_placeholders ) )
		);
	}

	/**
	 * Lists the sprintf() placeholders of a string with the argument each one reads.
	 *
	 * Mirrors sprintf(): a placeholder without an argument number reads the
	 * next argument, and a positional one does not move that counter.
	 *
	 * @since 4.2.0
	 *
	 * @param string $text The string to parse.
	 * @return array<int, array{0: string, 1: int}> Pairs of placeholder and 1-based argument number.
	 */
	private function get_sprintf_placeholders( $text ) {
		preg_match_all( self::SPRINTF_PLACEHOLDER_REGEX, $text, $matches, PREG_SET_ORDER );

		$placeholders  = array();
		$next_argument = 0;
		foreach ( $matches as $match ) {
			if ( '%' === substr( $match[0], -1 ) ) {
				continue;
			}

			$placeholders[] = array( $match[0], empty( $match[1] ) ? ++$next_argument : (int) $match[1] );
		}

		return $placeholders;
	}

	/**
	 * Adds an error for a translation containing forbidden control characters.
	 *
	 * These characters break exported files. 0x04 is the context separator
	 * of the gettext formats, so msgfmt rejects a catalog that contains it
	 * in a string, and XML 1.0 cannot represent any C0 control character
	 * apart from tab, line feed and carriage return, which makes the
	 * Android and .NET exports invalid documents. Tab, line feed and
	 * carriage return themselves are legitimate and stay allowed.
	 *
	 * @since 4.2.0
	 * @access public
	 *
	 * @param string $original    The source string.
	 * @param string $translation The translation.
	 * @return string|true True if check is OK, otherwise error message.
	 */
	public function error_control_characters( $original, $translation ) {
		if ( preg_match( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $translation, $match ) ) {
			return sprintf(
				/* translators: %s: Code point of the control character, e.g. U+0004. */
				__( 'The translation contains the control character %s, which would break exported files.', 'glotpress' ),
				sprintf( 'U+%04X', ord( $match[0] ) )
			);
		}

		return true;
	}

	/**
	 * Registers all methods starting with `error_` as built-in errors.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param GP_Translation_Errors $translation_errors Instance of GP_Translation_Errors.
	 */
	public function add_all( $translation_errors ) {
		$errors = array_filter(
			get_class_methods( get_class( $this ) ),
			function ( $key ) {
				return gp_startswith( $key, 'error_' );
			}
		);

		$errors = array_fill_keys( $errors, $this );

		foreach ( $errors as $error => $class ) {
			$translation_errors->add( str_replace( 'error_', '', $error ), array( $class, $error ) );
		}
	}
}
