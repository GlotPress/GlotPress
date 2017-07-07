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
	public function check( $singular, $plural, $translations, $locale, $plurals_type = 'gettext' ) {
		$problems = array();
		foreach ( $translations as $translation_index => $translation ) {
			if ( ! $translation ) {
				continue;
			}

			$skip = array( 'singular' => false, 'plural' => false );
			if ( null !== $plural ) {
				$numbers_for_index = $locale->numbers_for_index( $translation_index );
				if ( 1 === $locale->get_nplurals() ) {
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

/**
 * Class used to register built-in translation warnings.
 *
 * @since 1.0.0
 */
class GP_Builtin_Translation_Warnings {

	/**
	 * Lower bound for length checks.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @var float
	 */
	public $length_lower_bound = 0.2;

	/**
	 * Upper bound for length checks.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @var float
	 */
	public $length_upper_bound = 5.0;

	/**
	 * List of locales which are excluded from length checks.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $length_exclude_languages = array( 'art-xemoji', 'ja', 'ko', 'zh', 'zh-hk', 'zh-cn', 'zh-sg', 'zh-tw' );

	/**
	 * Checks whether lengths of source and translation differ too much.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_length( $original, $translation, $locale ) {
		if ( in_array( $locale->slug, $this->length_exclude_languages, true ) ) {
			return true;
		}

		if ( gp_startswith( $original, 'number_format_' ) ) {
			return true;
		}

		$len_src   = gp_strlen( $original );
		$len_trans = gp_strlen( $translation );
		if (
			! (
				$this->length_lower_bound * $len_src < $len_trans &&
				$len_trans < $this->length_upper_bound * $len_src
			) &&
			(
				! gp_in( '_abbreviation', $original ) &&
				! gp_in( '_initial', $original ) )
		) {
			return __( 'Lengths of source and translation differ too much.', 'glotpress' );
		}

		return true;
	}

	/**
	 * Checks whether HTML tags are missing or have been added.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_tags( $original, $translation, $locale ) {
		$tag_pattern       = '(<[^>]*>)';
		$tag_re            = "/$tag_pattern/Us";
		$original_parts    = preg_split( $tag_re, $original, - 1, PREG_SPLIT_DELIM_CAPTURE );
		$translation_parts = preg_split( $tag_re, $translation, - 1, PREG_SPLIT_DELIM_CAPTURE );

		if ( count( $original_parts ) > count( $translation_parts ) ) {
			return __( 'Missing tags from translation.', 'glotpress' );
		}
		if ( count( $original_parts ) < count( $translation_parts ) ) {
			return __( 'Too many tags in translation.', 'glotpress' );
		}

		// We allow certain attributes to be different in translations.
		$translatable_attributes = array( 'title', 'aria-label' );
		$translatable_attr_regex = array();

		foreach ( $translatable_attributes as $key => $attribute ) {
			// Translations should never need a quote in a translatable attribute.
			$attr_regex_single               = '\s*' . $attribute . '=\'[^\']+\'\s*';
			$translatable_attr_regex[ $key ] = '%' . $attr_regex_single . '|' . str_replace( "'", '"', $attr_regex_single ) . '%';
		}

		$parts_tags = gp_array_zip( $original_parts, $translation_parts );

		if ( ! empty( $parts_tags ) ) {
			foreach ( $parts_tags as $tags ) {
				list( $original_tag, $translation_tag ) = $tags;
				$expected_error_msg = "Expected $original_tag, got $translation_tag.";
				$original_is_tag    = preg_match( "/^$tag_pattern$/", $original_tag );
				$translation_is_tag = preg_match( "/^$tag_pattern$/", $translation_tag );

				if ( $original_is_tag && $translation_is_tag && $original_tag !== $translation_tag ) {
					$original_tag    = preg_replace( $translatable_attr_regex, '', $original_tag );
					$translation_tag = preg_replace( $translatable_attr_regex, '', $translation_tag );
					if ( $original_tag !== $translation_tag ) {
						return $expected_error_msg;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Checks whether PHP placeholders are missing or have been added.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_placeholders( $original, $translation, $locale ) {
		/**
		 * Filter the regular expression that is used to match placeholders in translations.
		 *
		 * @since 1.0.0
		 *
		 * @param string $placeholders_re Regular expression pattern without leading or trailing slashes.
		 */
		$placeholders_re = apply_filters( 'gp_warning_placeholders_re', '%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX]' );

		$original_counts    = $this->_placeholders_counts( $original, $placeholders_re );
		$translation_counts = $this->_placeholders_counts( $translation, $placeholders_re );
		$all_placeholders   = array_unique( array_merge( array_keys( $original_counts ), array_keys( $translation_counts ) ) );
		foreach ( $all_placeholders as $placeholder ) {
			$original_count    = gp_array_get( $original_counts, $placeholder, 0 );
			$translation_count = gp_array_get( $translation_counts, $placeholder, 0 );
			if ( $original_count > $translation_count ) {
				return sprintf( __( 'Missing %s placeholder in translation.', 'glotpress' ), $placeholder );
			}
			if ( $original_count < $translation_count ) {
				return sprintf( __( 'Extra %s placeholder in translation.', 'glotpress' ), $placeholder );
			}
		}

		return true;
	}

	/**
	 * Counts the placeholders in a string.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $string The string to search.
	 * @param string $re     Regular expressions to match placeholders.
	 * @return array An array with counts per placeholder.
	 */
	private function _placeholders_counts( $string, $re ) {
		$counts = array();
		preg_match_all( "/$re/", $string, $matches );
		foreach ( $matches[0] as $match ) {
			$counts[ $match ] = gp_array_get( $counts, $match, 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * Checks whether a translation does begin on newline.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_should_begin_on_newline( $original, $translation, $locale ) {
		if ( gp_startswith( $original, "\n" ) && ! gp_startswith( $translation, "\n" ) ) {
			return __( 'Original and translation should both begin on newline.', 'glotpress' );
		}

		return true;
	}

	/**
	 * Checks whether a translation doesn't begin on newline.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_should_not_begin_on_newline( $original, $translation, $locale ) {
		if ( ! gp_startswith( $original, "\n" ) && gp_startswith( $translation, "\n" ) ) {
			return __( 'Translation should not begin on newline.', 'glotpress' );
		}

		return true;
	}

	/**
	 * Checks whether a translation does end on newline.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_should_end_on_newline( $original, $translation, $locale ) {
		if ( gp_endswith( $original, "\n" ) && ! gp_endswith( $translation, "\n" ) ) {
			return __( 'Original and translation should both end on newline.', 'glotpress' );
		}

		return true;
	}

	/**
	 * Checks whether a translation doesn't end on newline.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string    $original    The source string.
	 * @param string    $translation The translation.
	 * @param GP_Locale $locale      The locale of the translation.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_should_not_end_on_newline( $original, $translation, $locale ) {
		if ( ! gp_endswith( $original, "\n" ) && gp_endswith( $translation, "\n" ) ) {
			return __( 'Translation should not end on newline.', 'glotpress' );
		}

		return true;
	}

	/**
	 * Registers all methods starting with `warning_` as built-in warnings.
	 *
	 * @param GP_Translation_Warnings $translation_warnings Instance of GP_Translation_Warnings.
	 */
	public function add_all( $translation_warnings ) {
		$warnings = array_filter( get_class_methods( get_class( $this ) ), function ( $key ) {
			return gp_startswith( $key, 'warning_' );
		} );

		foreach ( $warnings as $warning ) {
			$translation_warnings->add( str_replace( 'warning_', '', $warning ), array( $this, $warning ) );
		}
	}
}
