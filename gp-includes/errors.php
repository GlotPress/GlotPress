<?php
/**
 * Translation errors API
 *
 * @package GlotPress
 * @since 4.0.0
 */

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
	 * @param GP_Original $gp_original     The original object.
	 * @param string[]    $translations The translations.
	 * @param GP_Locale   $locale       The locale.
	 * @return array|null Null if no issues have been found, otherwise an array
	 *                    with warnings.
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
	/*
	 * Executes all the callbacks and returns an array of problems.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param GP_Original $original     The original string.
	 * @param string[]    $translations The translations.
	 * @param GP_Locale   $locale       The locale.
	 *
	 * @return array|null
	 */
	public function check( $original, $translations, $locale ) {
		$singular = $original->singular;
		$plural   = $original->plural;
		$comment  = $original->comment;
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
					$singular_test = $callback( $singular, $translation, $original, $locale );
					if ( true !== $singular_test ) {
						$problems[ $translation_index ][ $callback_id ] = $singular_test;
					}
				}
				if ( null !== $plural && ! $skip['plural'] ) {
					$plural_test = $callback( $plural, $translation, $original, $locale );
					if ( true !== $plural_test ) {
						$problems[ $translation_index ][ $callback_id ] = $plural_test;
					}
				}
			}
		}

		return empty( $problems ) ? null : $problems;
	}

	/**
	 * Adds an error for adding unexpected percent signs in a sprintf-like string.
	 *
	 * This is to catch translations for originals like this:
	 *  - Original: `<a href="%s">100 percent</a>`
	 *  - Submitted translation: `<a href="%s">100%</a>`
	 *  - Proper translation: `<a href="%s">100%%</a>`
	 *
	 * @param string $original The original string.
	 * @param string $translation The translated string.
	 *
	 * @return bool|string
	 * @since 4.0.0
	 * @access public
	 */
	public function error_unexpected_sprintf_token( $original, $translation ) {
		$unexpected_tokens = array();
		$is_sprintf        = preg_match( '!%((\d+\$(?:\d+)?)?[bcdefgosuxl])\b!i', $original );

		// Find any percents that are not valid or escaped.
		if ( $is_sprintf ) {
			// Negative/Positive lookahead not used to allow the warning to include the context around the % sign.
			preg_match_all( '/(?P<context>[^\s%]*)%((\d+\$(?:\d+)?)?(?P<char>.))/i', $translation, $m );
			foreach ( $m['char'] as $i => $char ) {
				// % is included for escaped %%.
				if ( false === strpos( 'bcdefgosux%l.', $char ) ) {
					$unexpected_tokens[] = $m[0][ $i ];
				}
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
	 * Adds an error for unexpected timezone strings.
	 *
	 * @param string      $original    The original string.
	 * @param string      $translation The translated string.
	 * @param GP_Original $gp_original The GP_original object.
	 * @param GP_Locale   $locale      The locale.
	 *
	 * @return string|true
	 * @since 4.0.0
	 * @access public
	 */
	public function error_unexpected_timezone( $original, $translation, $gp_original, $locale ) {
		if ( is_null( $gp_original->comment ) ) {
			return true;
		}
		if ( ! str_contains( $gp_original->comment, 'default_GMT_offset_or_timezone_string' ) ) {
			return true;
		}
		// Must be either a valid offset (-12 to 14).
		if ( is_numeric( $translation ) && round( $translation ) == intval( $translation ) && $translation >= - 12 && $translation <= 14 ) {
			// Countries with half-hour offsets or similar need to use a timezone string.
			return true;
		}
		// Or a valid timezone string (America/New_York).
		if ( in_array( $translation, timezone_identifiers_list() ) ) {
			return true;
		}

		return esc_html( 'Must be either a valid offset (-12 to 14) or a valid timezone string (America/New_York).', 'glotpress' );
	}

	public function error_unexpected_start_of_week_number( $original, $translation, $gp_original, $locale ) {
		if ( is_null( $gp_original->comment ) ) {
			return true;
		}
		if ( ! str_contains( $gp_original->comment, 'start_of_week_number' ) ) {
			return true;
		}
		if ( is_int( $translation ) && $translation >= 0 && $translation <= 1 ) {
			return true;
		}

		return esc_html( 'Must be an integer number between 0 and 1.', 'glotpress' );
	}

	/**
	 * Registers all methods starting with `error_` as built-in warnings.
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
