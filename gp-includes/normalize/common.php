<?php
/**
 * Common functions for normalizing strings.
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

/**
 * Look in string to find invariants.
 *
 * @param string $str the string to be analyzed.
 *
 * @return array
 */
function glotpress_extract_invariants( string $str ): array {
	// Extract URLs.
	preg_match_all( '/https?:\/\/[^\s"»]+/u', $str, $matches1 );

	// Extract html tags.
	preg_match_all( '/<[^>]+>/u', $str, $matches2 );

	// Extract fprintf placeholders.
	preg_match_all( '/%\d*\$[sdf]/u', $str, $matches3 );

	return array_merge( $matches1[0], $matches2[0], $matches3[0] );
}

/**
 * Replace invariants by placeholders.
 *
 * @param string $str the string where we want placeholders to save invariants from automatic fix.
 * @param array  $invariants the invariants we saved for later use.
 *
 * @return string
 */
function glotpress_insert_placeholder_invariants( string $str, array $invariants ): string {
	foreach ( $invariants as $invariant ) {
		$str = str_replace( $invariant, 'INVARIANT' . md5( $invariant ), $str );
	}

	return $str;
}

/**
 * Replace invariants in their original place.
 *
 * @param string $str the string where we want to replace placeholders by invariants.
 * @param array  $invariants the invariants we want to put back in string.
 *
 * @return string
 */
function glotpress_replace_placeholder_invariants( string $str, array $invariants ): string {
	foreach ( $invariants as $invariant ) {
		$str = str_replace( 'INVARIANT' . md5( $invariant ), $invariant, $str );
	}

	return $str;
}
