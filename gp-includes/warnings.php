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
	 * @var callable[]
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
	 * @param string[]  $translations An array of translations for an original.
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
	 * List of domains with allowed changes to their own subdomains
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $allowed_domain_changes = array(
		// Allow links to wordpress.org to be changed to a subdomain.
		'wordpress.org'    => '[^.]+\.wordpress\.org',
		// Allow links to wordpress.com to be changed to a subdomain.
		'wordpress.com'    => '[^.]+\.wordpress\.com',
		// Allow links to gravatar.org to be changed to a subdomain.
		'en.gravatar.com'  => '[^.]+\.gravatar\.com',
		// Allow links to wikipedia.org to be changed to a subdomain.
		'en.wikipedia.org' => '[^.]+\.wikipedia\.org',
	);

	/**
	 * List of languages without italics
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @var array
	 */
	public $languages_without_italics = array(
		'ja',
		'ko',
		'zh',
		'zh-hk',
		'zh-cn',
		'zh-sg',
		'zh-tw',
	);

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

		$len_src   = mb_strlen( $original );
		$len_trans = mb_strlen( $translation );
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
	 * @todo Validate if the HTML is in the same order in function of the language. Validate nesting of HTML is same.
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
		$original_parts    = array();
		$translation_parts = array();

		if ( preg_match_all( $tag_re, $original, $m ) ) {
			$original_parts = $m[1];
		}
		if ( preg_match_all( $tag_re, $translation, $m ) ) {
			$translation_parts = $m[1];
		}

		// East asian languages can remove emphasis/italic tags
		if ( count( $original_parts ) > count( $translation_parts ) ) {
			// Remove Italic requirements.
			if ( in_array( $locale->slug, $this->languages_without_italics, true ) ) {
				$original_parts = array_diff( $original_parts, array( '<em>', '</em>', '<i>', '</i>' ) );
			}
		}

		if ( count( $original_parts ) > count( $translation_parts ) ) {
			return sprintf(
				/* translators: %s: HTML tags. */
				__( 'Missing tags from translation. Expected: %s', 'glotpress' ),
				implode( ' ', array_diff( $original_parts, $translation_parts ) )
			);
		}
		if ( count( $original_parts ) < count( $translation_parts ) ) {
			return sprintf(
				/* translators: %s: HTML tags. */
				__( 'Too many tags in translation. Found: %s', 'glotpress' ),
				implode( ' ', array_diff( $translation_parts, $original_parts ) )
			);
		}

		// Check if the translation tags are in correct order
		$valid_html_warning = $this->check_valid_html( $original_parts, $translation_parts );
		if ( true !== $valid_html_warning ) {
			return trim( $valid_html_warning );
		}

		// Sort the tags, from this point out as long as all the tags are present is okay.
		rsort( $original_parts );
		rsort( $translation_parts );

		$changeable_attributes = array(
			// We allow certain attributes to be different in translations.
			'title',
			'aria-label',
			// src and href will be checked separately.
			'src',
			'href',
		);

		$attribute_regex       = '/(\s*(?P<attr>%s))=([\'"])(?P<value>.+)\\3(\s*)/i';
		$attribute_replace     = '$1=$3...$3$5';
		$changeable_attr_regex = sprintf( $attribute_regex, implode( '|', $changeable_attributes ) );

		// Items are sorted, so if all is well, will match up.
		$parts_tags = array_combine( $original_parts, $translation_parts );

		$warnings = array();
		foreach ( $parts_tags as $original_tag => $translation_tag ) {
			if ( $original_tag === $translation_tag ) {
				continue;
			}

			// Remove any attributes that can be expected to differ.
			$original_filtered_tag    = preg_replace( $changeable_attr_regex, $attribute_replace, $original_tag );
			$translation_filtered_tag = preg_replace( $changeable_attr_regex, $attribute_replace, $translation_tag );

			if ( $original_filtered_tag !== $translation_filtered_tag ) {
				$warnings[] = sprintf(
					/* translators: 1: Original HTML tag. 2: Translated HTML tag. */
					__( 'Expected %1$s, got %2$s.', 'glotpress' ),
					$original_tag,
					$translation_tag
				);
			}
		}

		// Now check that the URLs mentioned within href & src tags match.
		$original_links    = '';
		$translation_links = '';

		$original_links    = implode( "\n", $this->get_values_from_href_src( $original_parts ) );
		$translation_links = implode( "\n", $this->get_values_from_href_src( $translation_parts ) );
		// Validate the URLs if present.
		if ( $original_links || $translation_links ) {
			$url_warnings = $this->links_without_url_and_placeholders_are_equal( $original_links, $translation_links );
			if ( true !== $url_warnings ) {
				$warnings = array_merge( $warnings, $url_warnings );
			}

			$url_warnings = $this->warning_mismatching_urls( $original_links, $translation_links );

			if ( true !== $url_warnings ) {
				$warnings[] = $url_warnings;
			}
		}

		if ( empty( $warnings ) ) {
			return true;
		}

		return implode( "\n", $warnings );
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
		$placeholders_re = apply_filters( 'gp_warning_placeholders_re', '(?<!%)%(\d+\$(?:\d+)?)?[bcdefgosuxEFGX%l]' );

		$original_counts    = $this->_placeholders_counts( $original, $placeholders_re );
		$translation_counts = $this->_placeholders_counts( $translation, $placeholders_re );
		$all_placeholders   = array_unique( array_merge( array_keys( $original_counts ), array_keys( $translation_counts ) ) );
		foreach ( $all_placeholders as $placeholder ) {
			$original_count    = gp_array_get( $original_counts, $placeholder, 0 );
			$translation_count = gp_array_get( $translation_counts, $placeholder, 0 );
			if ( $original_count > $translation_count ) {
				return sprintf(
					/* translators: %s: Placeholder. */
					__( 'Missing %s placeholder in translation.', 'glotpress' ),
					$placeholder
				);
			}
			if ( $original_count < $translation_count ) {
				return sprintf(
					/* translators: %s: Placeholder. */
					__( 'Extra %s placeholder in translation.', 'glotpress' ),
					$placeholder
				);
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
	 * Adds a warning for changing plain-text URLs.
	 *
	 * This allows for the scheme to change, and for some domains to change to a subdomain.
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	public function warning_mismatching_urls( $original, $translation ) {
		// Any http/https/schemeless URLs which are not encased in quotation marks
		// nor contain whitespace and end with a valid URL ending char.
		$urls_regex = '@(?<![\'"])((https?://|(?<![:\w])//)[^\s<]+[a-z0-9\-_&=#/])(?![\'"])@i';

		preg_match_all( $urls_regex, $original, $original_urls );
		$original_urls = array_unique( $original_urls[0] );

		preg_match_all( $urls_regex, $translation, $translation_urls );
		$translation_urls = array_unique( $translation_urls[0] );

		$missing_urls = array_diff( $original_urls, $translation_urls );
		$added_urls   = array_diff( $translation_urls, $original_urls );
		if ( ! $missing_urls && ! $added_urls ) {
			return true;
		}

		// Check to see if only the scheme (https <=> http) or a trailing slash was changed, discard if so.
		foreach ( $missing_urls as $key => $missing_url ) {
			$scheme               = parse_url( $missing_url, PHP_URL_SCHEME );
			$alternate_scheme     = ( 'http' == $scheme ? 'https' : 'http' );
			$alternate_scheme_url = preg_replace( "@^$scheme(?=:)@", $alternate_scheme, $missing_url );

			$alt_urls = array(
				// Scheme changes
				$alternate_scheme_url,

				// Slashed/unslashed changes.
				( '/' === substr( $missing_url, -1 ) ? rtrim( $missing_url, '/' ) : "$missing_url/" ),

				// Scheme & Slash changes.
				( '/' === substr( $alternate_scheme_url, -1 ) ? rtrim( $alternate_scheme_url, '/' ) : "$alternate_scheme_url/" ),
			);

			foreach ( $alt_urls as $alt_url ) {
				if ( false !== ( $alternate_index = array_search( $alt_url, $added_urls ) ) ) {
					unset( $missing_urls[ $key ], $added_urls[ $alternate_index ] );
				}
			}
		}

		// Check if just the domain was changed, and if so, if it's to a whitelisted domain
		foreach ( $missing_urls as $key => $missing_url ) {
			$host = parse_url( $missing_url, PHP_URL_HOST );
			if ( ! isset( $this->allowed_domain_changes[ $host ] ) ) {
				continue;
			}
			$allowed_host_regex = $this->allowed_domain_changes[ $host ];

			list( , $missing_url_path ) = explode( $host, $missing_url, 2 );

			$alternate_host_regex = '!^https?://' . $allowed_host_regex . preg_quote( $missing_url_path, '!' ) . '$!i';
			foreach ( $added_urls as $added_index => $added_url ) {
				if ( preg_match( $alternate_host_regex, $added_url, $match ) ) {
					unset( $missing_urls[ $key ], $added_urls[ $added_index ] );
				}
			}
		}

		if ( ! $missing_urls && ! $added_urls ) {
			return true;
		}

		$error = '';
		if ( $missing_urls ) {
			$error .= sprintf(
				/* translators: %s: URLs. */
				__( 'The translation appears to be missing the following URLs: %s', 'glotpress' ),
				implode( ', ', $missing_urls ) . "\n"
			);
		}
		if ( $added_urls ) {
			$error .= sprintf(
				/* translators: %s: URLs. */
				__( 'The translation contains the following unexpected URLs: %s', 'glotpress' ),
				implode( ', ', $added_urls ) . "\n"
			);
		}

		return trim( $error );
	}

	/**
	 * Adds a warning for adding unexpected percent signs in a sprintf-like string.
	 *
	 * This is to catch translations for originals like this:
	 *  - Original: `<a href="%s">100 percent</a>`
	 *  - Submitted translation: `<a href="%s">100%</a>`
	 *  - Proper translation: `<a href="%s">100%%</a>`
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 * @return bool|string
	 */
	public function warning_unexpected_sprintf_token( $original, $translation ) {
		$unexpected_tokens = array();
		$is_sprintf        = preg_match( '!%((\d+\$(?:\d+)?)?[bcdefgosuxl])\b!i', $original );

		// Find any percents that are not valid or escaped.
		if ( $is_sprintf ) {
			// Negative/Positive lookahead not used to allow the warning to include the context around the % sign.
			preg_match_all( '/(?P<context>[^\s%]*)%((\d+\$(?:\d+)?)?(?P<char>.))/i', $translation, $m );
			foreach ( $m['char'] as $i => $char ) {
				// % is included for escaped %%.
				if ( false === strpos( 'bcdefgosux%l', $char ) ) {
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
	 * Registers all methods starting with `warning_` as built-in warnings.
	 *
	 * @param GP_Translation_Warnings $translation_warnings Instance of GP_Translation_Warnings.
	 */
	public function add_all( $translation_warnings ) {
		$warnings = array_filter(
			get_class_methods( get_class( $this ) ),
			function ( $key ) {
				return gp_startswith( $key, 'warning_' );
			}
		);

		$warnings = array_fill_keys( $warnings, $this );

		foreach ( $warnings as $warning => $class ) {
			$translation_warnings->add( str_replace( 'warning_', '', $warning ), array( $class, $warning ) );
		}
	}

	/**
	 * Adds a warning for changing placeholders.
	 *
	 * This only supports placeholders in the format of '###[A-Z_]+###'.
	 *
	 * @todo Check that the number of each type of placeholders are the same in the original and in the translation
	 *
	 * @since 3.0.0
	 * @access public
	 *
	 * @param string $original    The original string.
	 * @param string $translation The translated string.
	 * @return string|true
	 */
	public function warning_mismatching_placeholders( string $original, string $translation ) {
		$placeholder_regex = '@(###[A-Z_]+###)@';

		preg_match_all( $placeholder_regex, $original, $original_placeholders );
		$original_placeholders = array_unique( $original_placeholders[0] );

		preg_match_all( $placeholder_regex, $translation, $translation_placeholders );
		$translation_placeholders = array_unique( $translation_placeholders[0] );

		$missing_placeholders = array_diff( $original_placeholders, $translation_placeholders );
		$added_placeholders   = array_diff( $translation_placeholders, $original_placeholders );
		if ( ! $missing_placeholders && ! $added_placeholders ) {
			return true;
		}

		$error = '';
		if ( $missing_placeholders ) {
			$error .= sprintf(
				/* translators: %s: Placeholders. */
				__( 'The translation appears to be missing the following placeholders: %s', 'glotpress' ),
				implode( ', ', $missing_placeholders ) . "\n"
			);
		}
		if ( $added_placeholders ) {
			$error .= sprintf(
				/* translators: %s: Placeholders. */
				__( 'The translation contains the following unexpected placeholders: %s', 'glotpress' ),
				implode( ', ', $added_placeholders )
			);
		}

		return trim( $error );
	}

	/**
	 * Returns the values from the href and the src
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param array $content
	 * @return array
	 */
	private function get_values_from_href_src( array $content ): array {
		preg_match_all( '/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', implode( ' ', $content ), $href_values );
		preg_match_all( '/<[^>]+src=([\'"])(?<src>.+?)\1[^>]*>/i', implode( ' ', $content ), $src_values );
		return array_merge( $href_values['href'], $src_values['src'] );
	}

	/**
	 * Checks if the HTML tags are in correct order
	 *
	 * Warns about HTML tags translations in incorrect order. For example:
	 * - Original: <a></a>
	 * - Translation: </a><a>
	 *
	 * @param array $original_parts     The original HTML tags
	 * @param array $translation_parts  The translation HTML tags
	 * @return string|true True if check is OK, otherwise warning message.
	 */
	private function check_valid_html( array $original_parts, array $translation_parts ) {
		if ( empty( $original_parts ) ) {
			return true;
		}
		if ( $original_parts === $translation_parts ) {
			return true;
		}

		libxml_clear_errors();
		libxml_use_internal_errors( true );
		$original = new DOMDocument();
		$original->loadHTML( implode( '', $original_parts ) );
		// If the original parts are not well-formed, don't continue the translation check
		$errors = libxml_get_errors();
		if ( ! empty( $errors ) ) {
			return true;
		}

		$translation = new DOMDocument();
		$translation->loadHTML( implode( '', $translation_parts ) );
		$errors = libxml_get_errors();
		if ( ! empty( $errors ) ) {
			$message = array();
			foreach ( $errors as $error ) {
				$message[] = trim( $error->message );
			}
			return sprintf(
				/* translators: %s: HTML tags. */
				__( 'The translation contains incorrect HTML tags: %s', 'glotpress' ),
				implode( ', ', $message )
			);
		}
		return true;
	}

	/**
	 * Checks whether links that are not URL or placeholders are equal or not
	 *
	 * @since 3.0.0
	 * @access private
	 *
	 * @param string $original_links
	 * @param string $translation_links
	 * @return  array|true True if check is OK, otherwise warning message.
	 */
	private function links_without_url_and_placeholders_are_equal( string $original_links, string $translation_links ) {
		$urls_regex        = '@(?<![\'"])((https?://|(?<![:\w])//)[^\s<]+[a-z0-9\-_&=#/])(?![\'"])@i';
		$placeholder_regex = '!%((\d+\$(?:\d+)?)?[bcdefgosux])!i';

		// Remove the URLs
		preg_match_all( $urls_regex, $original_links, $original_urls );
		$original_urls              = array_unique( $original_urls[0] );
		$original_links_without_url = array_diff( explode( "\n", $original_links ), $original_urls );
		preg_match_all( $urls_regex, $translation_links, $translation_urls );
		$translation_urls              = array_unique( $translation_urls[0] );
		$translation_links_without_url = array_diff( explode( "\n", $translation_links ), $translation_urls );

		// Remove the placeholders
		preg_match_all( $placeholder_regex, implode( ' ', $original_links_without_url ), $original_clean_links );
		preg_match_all( $placeholder_regex, implode( ' ', $translation_links_without_url ), $translation_clean_links );
		$original_clean_links    = array_filter( array_diff( $original_links_without_url, $original_clean_links[0] ) );
		$translation_clean_links = array_filter( array_diff( $translation_links_without_url, $translation_clean_links[0] ) );
		$missing_urls            = array_diff( $original_clean_links, $translation_clean_links );
		$added_urls              = array_diff( $translation_clean_links, $original_clean_links );

		if ( ! $missing_urls && ! $added_urls ) {
			return true;
		}

		$error = array();
		if ( $missing_urls ) {
			$error[] = sprintf(
				/* translators: %s: URLs. */
				__( 'The translation appears to be missing the following links: %s', 'glotpress' ),
				implode( ', ', $missing_urls )
			);
		}
		if ( $added_urls ) {
			$error[] = sprintf(
				/* translators: %s: URLs. */
				__( 'The translation contains the following unexpected links: %s', 'glotpress' ),
				implode( ', ', $added_urls )
			);
		}

		return $error;
	}
}
