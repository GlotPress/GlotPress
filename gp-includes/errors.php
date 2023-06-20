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
	 * Constructor.
	 *
	 * @since 4.0.0
	 * @access public
	 */
	public function __construct() {
		$this->callbacks[] = array( $this, 'error_unexpected_sprintf_token' );
	}

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
					$singular_test = $callback( $singular, $translation, $locale );
					if ( true !== $singular_test ) {
						$problems[ $translation_index ][ $callback_id ] = $singular_test;
					}
				}
				if ( null !== $plural && ! $skip['plural'] ) {
					$plural_test = $callback( $plural, $translation, $locale );
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
	 * @since 4.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 *
	 * @return bool|string
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
}
