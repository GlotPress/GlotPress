<?php
/**
 * Functions, which make work with strings easier
 */

function gp_startswith( $haystack, $needle ) {
	return 0 === strpos( $haystack, $needle );
}

function gp_endswith( $haystack, $needle ) {
	return $needle === substr( $haystack, -strlen( $needle ));
}

function gp_in( $needle, $haystack ) {
	return false !== strpos( $haystack, $needle );
}

/**
 * Compat function to mimic mb_strtolower().
 *
 * Falls back to `strtolower()` if `mb_strtolower()` doesn't exists.
 *
 * @since 1.0.0
 *
 * @param string      $str      The string being lowercased.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return int String length of `$str`.
 */
function gp_strtolower( $str, $encoding = null ) {
	if ( function_exists( 'mb_strtolower' ) ) {
		if ( isset( $encoding ) ) {
			return mb_strtolower( $str, $encoding );
		} else {
			return mb_strtolower( $str ); // Uses mb_internal_encoding().
		}
	}

	return strtolower( $str );
}

/**
 * Compat function to mimic mb_strlen().
 *
 * Without a `function_exists()` check because WordPress includes
 * a compat function for `mb_strlen()`.
 *
 * @since 1.0.0
 *
 * @see _mb_strlen()
 *
 * @param string      $str      The string to retrieve the character length from.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return int String length of `$str`.
 */
function gp_strlen( $str, $encoding = null ) {
	if ( isset( $encoding ) ) {
		return mb_strlen( $str, $encoding );
	} else {
		return mb_strlen( $str ); // Uses mb_internal_encoding().
	}
}

/**
 * Compat function to mimic mb_stripos().
 *
 * Falls back to `stripos()` if `mb_stripos()` doesn't exists.
 *
 * @since 1.0.0
 *
 * @param string      $haystack The string from which to get the position of the first occurrence of needle.
 * @param string      $needle   The string to find in haystack.
 * @param int         $offset   The position in haystack to start searching.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return int|false The numeric position of the first occurrence of needle in the haystack string,
 *                   or false if needle is not found.
 */
function gp_stripos( $haystack, $needle, $offset = 0, $encoding = null ) {
	if ( function_exists( 'mb_stripos' ) ) {
		if ( isset( $encoding ) ) {
			return mb_stripos( $haystack, $needle, $offset, $encoding );
		} else {
			return mb_stripos( $haystack, $needle, $offset ); // Uses mb_internal_encoding().
		}
	}

	return stripos( $haystack, $needle, $offset );
}

/**
 * Compat function to mimic mb_substr().
 *
 * Without a `function_exists()` check because WordPress includes
 * a compat function for `mb_substr()`.
 *
 * @since 1.0.0
 *
 * @see _mb_substr()
 *
 * @param string      $str      The string to extract the substring from.
 * @param int         $start    Position to being extraction from in `$str`.
 * @param int|null    $length   Optional. Maximum number of characters to extract from `$str`.
 *                              Default null.
 * @param string|null $encoding Optional. Character encoding to use. Default null.
 * @return string Extracted substring.
 */
function gp_substr( $str, $start, $length, $encoding = null ) {
	if ( isset( $encoding ) ) {
		return mb_substr( $str, $start, $length, $encoding );
	} else {
		return mb_substr( $str, $start, $length ); // Uses mb_internal_encoding().
	}
}

/**
 * Escaping for HTML attributes.
 *
 * Similar to esc_attr(), but double encode entities.
 *
 * @since 1.0.0
 *
 * @param string $text The text prior to being escaped.
 * @return string The text after it has been escaped.
 */
function gp_esc_attr_with_entities( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = htmlspecialchars( $safe_text, ENT_QUOTES, false, true );

	/**
	 * Filter a string cleaned and escaped for output in an HTML attribute.
	 *
	 * Text passed to gp_esc_attr_with_entities() is stripped of invalid or
	 * special characters before output. Unlike esc_attr() it double encodes
	 * entities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $safe_text The text after it has been escaped.
	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'gp_attribute_escape', $safe_text, $text );
}

/**
 * Escapes translations for HTML blocks.
 *
 * Similar to esc_html(), but double encode entities.
 *
 * @since 1.0.0
 *
 * @param string $text The text prior to being escaped.
 * @return string The text after it has been escaped.
 */
function esc_translation( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	return htmlspecialchars( $safe_text, ENT_NOQUOTES, false, true );
}

function gp_string_similarity( $str1, $str2 ) {

	$length1 = gp_strlen( $str1 );
	$length2 = gp_strlen( $str2 );

	$len = min( $length1, $length2);
	if ( $len > 5000 ) {
		//Arbitrary limit on character length for speed purpose.
		$distance = $len;
	} else {
		$distance = gp_levenshtein( $str1, $str2, $length1, $length2 );
	}

	$similarity = 1 - ( $distance * 0.9 / $len );

	return $similarity;
}

/*
	PHP native implementation of levensthein is limited to 255 bytes, so let's extend that
	Source: https://github.com/wikimedia/mediawiki-extensions-Translate/blob/master/ttmserver/TTMServer.php#L90

*/
function gp_levenshtein( $str1, $str2, $length1, $length2 ) {

	if ( $length1 == 0 ) {
		return $length2;
	}

	if ( $length2 == 0 ) {
		return $length1;
	}

	if ( $str1 === $str2 ) {
		return 0;
	}

	$bytelength1 = strlen( $str1 );
	$bytelength2 = strlen( $str2 );

	if ( $bytelength1 === $length1 && $bytelength1 <= 255
	     && $bytelength2 === $length2 && $bytelength2 <= 255 ) {
		return levenshtein( $str1, $str2 );
	}

	$prevRow = range( 0, $length2 );
	for ( $i = 0; $i < $length1; $i++ ) {
		$currentRow = array();
		$currentRow[0] = $i + 1;
		$c1 = gp_substr( $str1, $i, 1 );
		for ( $j = 0; $j < $length2; $j++ ) {
			$c2 = gp_substr( $str2, $j, 1 );
			$insertions = $prevRow[$j + 1] + 1;
			$deletions = $currentRow[$j] + 1;
			$substitutions = $prevRow[$j] + ( ( $c1 != $c2 ) ? 1 : 0 );
			$currentRow[] = min( $insertions, $deletions, $substitutions );
		}
		$prevRow = $currentRow;
	}

	return $prevRow[$length2];
}
