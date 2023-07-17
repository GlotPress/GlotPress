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
		$is_sprintf        = preg_match( '!%((\d+\$(?:\d+)?)?[bcdefgosuxl])\b!i', $original );

		// Find any percents that are not valid or escaped.
		if ( $is_sprintf ) {
			// Negative/Positive lookahead not used to allow the error to include the context around the % sign.
			preg_match_all( '/(?P<context>[^\s%]*)%((\d+\$(?:\d+)?)?(?P<char>.|$))/i', $translation, $m );
			foreach ( $m['char'] as $i => $char ) {
				// % is included for escaped %%.
				if ( '' === $char || false === strpos( 'bcdefgosux%l.', $char ) ) {
					$unexpected_tokens[] = $m[0][ $i ];
				}
			}
		}
		if ( $unexpected_tokens ) {
			return sprintf(
			/* translators: %s: Placeholders. */
				esc_html__( 'The translation contains the following unexpected placeholders: %s', 'glotpress' ),
				implode( ', ', $unexpected_tokens )
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
