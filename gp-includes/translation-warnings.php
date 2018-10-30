<?php
/**
 * Translation warnings API
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Class used to handle translation warnings.
 *
 * @since 1.0.0
 */
class GP_Translation_Warnings {

	/**
	 * List of callbacks.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $callbacks = array();

	/**
	 * Adds a callback for a new warning.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string   $id       Unique ID of the callback.
	 * @param callable $callback The callback.
	 */
	public function add( $id, $callback ) {
		$this->callbacks[ $id ] = $callback;
	}

	/**
	 * Removes an existing callback for a warning.
	 *
	 * @since 1.0.0
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
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $id Unique ID of the callback.
	 * @return bool True if exists, false if not.
	 */
	public function has( $id ) {
		return isset( $this->callbacks[ $id ] );
	}

	/**
	 * Checks translations for any issues/warnings.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $singular     The singular form of an original string.
	 * @param string    $plural       The plural form of an original string.
	 * @param array     $translations An array of translations for an original.
	 * @param GP_Locale $locale       The locale of the translations.
	 * @return array|null Null if no issues have been found, otherwise an array
	 *                    with warnings.
	 */
	public function check( $singular, $plural, $translations, $locale ) {
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
					$singular_test = call_user_func( $callback, $singular, $translation, $locale );
					if ( true !== $singular_test ) {
						$problems[ $translation_index ][ $callback_id ] = $singular_test;
					}
				}
				if ( ! is_null( $plural ) && ! $skip['plural'] ) {
					$plural_test = call_user_func( $callback, $plural, $translation, $locale );
					if ( true !== $plural_test ) {
						$problems[ $translation_index ][ $callback_id ] = $plural_test;
					}
				}
			}
		}

		return empty( $problems ) ? null : $problems;
	}
}
