<?php
/**
 * Functions, which make work with strings easier
 */

function gp_startswith( $haystack, $needle ) {
	return 0 === strpos( $haystack, $needle );
}

function gp_endswith( $haystack, $needle ) {
	return substr( $haystack, -strlen( $needle ) ) === $needle;
}

function gp_in( $needle, $haystack ) {
	return false !== strpos( $haystack, $needle );
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

	$length1 = mb_strlen( $str1 );
	$length2 = mb_strlen( $str2 );

	$len = min( $length1, $length2 );
	if ( $len > 5000 ) {
		// Arbitrary limit on character length for speed purpose.
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

	if ( 0 == $length1 ) {
		return $length2;
	}

	if ( 0 == $length2 ) {
		return $length1;
	}

	if ( $str1 === $str2 ) {
		return 0;
	}

	if ( $length1 <= 255 && $length2 <= 255 ) {
		$bytelength1 = strlen( $str1 );
		$bytelength2 = strlen( $str2 );

		if ( $bytelength1 === $length1 && $bytelength2 === $length2 ) {
			return levenshtein( $str1, $str2 );
		}
	}

	$chars1 = mb_str_split( $str1 );
	$chars2 = mb_str_split( $str2 );

	$prevRow = range( 0, $length2 );
	foreach ( $chars1 as $i => $c1 ) {
		$currentRow    = array();
		$currentRow[0] = $i + 1;
		foreach ( $chars2 as $j => $c2 ) {
			$insertions    = $prevRow[ $j + 1 ] + 1;
			$deletions     = $currentRow[ $j ] + 1;
			$substitutions = $prevRow[ $j ] + ( ( $c1 != $c2 ) ? 1 : 0 );
			$currentRow[]  = min( $insertions, $deletions, $substitutions );
		}
		$prevRow = $currentRow;
	}

	return $prevRow[ $length2 ];
}

/**
 * Sanitizes a string for use as a slug, replacing whitespace and a few other characters with dashes.
 *
 * Limits the output to alphanumeric characters, underscore (_), periods (.) and dash (-).
 * Whitespace becomes a dash.
 *
 * @since 2.1.0
 *
 * @param string $slug The string to be sanitized for use as a slug.
 *
 * @return string The sanitized title.
 */
function gp_sanitize_slug( $slug ) {
	$slug = remove_accents( $slug );

	$slug = strip_tags( $slug );

	// Preserve escaped octets.
	$slug = preg_replace( '|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $slug );

	// Remove percent signs that are not part of an octet.
	$slug = str_replace( '%', '', $slug );

	// Restore octets.
	$slug = preg_replace( '|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $slug );

	if ( seems_utf8( $slug ) ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			$slug = mb_strtolower( $slug, 'UTF-8' );
		}
		$slug = utf8_uri_encode( $slug, 200 );
	}

	$slug = strtolower( $slug );

	// Convert nbsp, ndash and mdash to hyphens.
	$slug = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $slug );

	// Convert nbsp, ndash and mdash HTML entities to hyphens.
	$slug = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $slug );

	// Strip these characters entirely.
	$slug = str_replace(
		array(
			// Iexcl and iquest.
			'%c2%a1',
			'%c2%bf',
			// Angle quotes.
			'%c2%ab',
			'%c2%bb',
			'%e2%80%b9',
			'%e2%80%ba',
			// Curly quotes.
			'%e2%80%98',
			'%e2%80%99',
			'%e2%80%9c',
			'%e2%80%9d',
			'%e2%80%9a',
			'%e2%80%9b',
			'%e2%80%9e',
			'%e2%80%9f',
			// Copy, reg, deg, hellip and trade.
			'%c2%a9',
			'%c2%ae',
			'%c2%b0',
			'%e2%80%a6',
			'%e2%84%a2',
			// Acute accents.
			'%c2%b4',
			'%cb%8a',
			'%cc%81',
			'%cd%81',
			// Grave accent, macron, caron.
			'%cc%80',
			'%cc%84',
			'%cc%8c',
		),
		'',
		$slug
	);

	// Convert times to x.
	$slug = str_replace( '%c3%97', 'x', $slug );

	// Kill entities.
	$slug = preg_replace( '/&.+?;/', '', $slug );

	$slug = preg_replace( '/[^%a-z\.0-9 _-]/', '', $slug );
	$slug = preg_replace( '/\s+/', '-', $slug );
	$slug = preg_replace( '|-+|', '-', $slug );
	$slug = trim( $slug, '-' );

	return $slug;
}
