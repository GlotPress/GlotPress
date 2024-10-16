<?php
/**
 * Normalization for french locale, we need to follow this rules:
 * https://fr.wordpress.org/team/handbook/polyglots/les-regles-typographiques-utilisees-pour-la-traduction-de-wp-en-francais/
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 1.0.0
 */

// Need some functions to work with URL and html tags in text.
require_once GP_PATH . GP_INC . 'normalize/common.php';

/**
 * Normalize a string for the French locale.
 *
 * @param string $str the string to be normalized.
 *
 * @return string
 */
function glotpress_normalize_for_locale( string $str ): string {
	$nbsp  = html_entity_decode( '&nbsp;' );
	$space = '[ \t' . $nbsp . ']';

	// First extract invariants.
	$invariants = glotpress_extract_invariants( $str );

	// Replace invariants by a placeholder.
	$str = glotpress_insert_placeholder_invariants( $str, $invariants );

	// "etc." with a single dot after.
	$str = preg_replace( '/ *etc[. ]*/u', ' etc. ', $str );

	// Simple char remplacement.
	$str = str_replace( '\'', '‘', $str );
	$str = str_replace( '’', '‘', $str );
	$str = str_replace( '...', '…', $str );

	// Quotes.
	$str = preg_replace( '/"([^"]*)"/u', '«\1»', $str );

	// Space before, non-breaking space after.
	$str = preg_replace( '/' . $space . '*«' . $space . '*/u', ' «' . $nbsp, $str );

	// No space before, one space after.
	$str = preg_replace( '/' . $space . '*([.,…)\]])' . $space . '*/u', '\1 ', $str );

	// No space before, one space after but only for non-numbers.
	$str = preg_replace( '/([^\s\d])' . $space . '*,' . $space . '*([^\s\d])/u', '\1, \2', $str );
	$str = preg_replace( '/(\d)' . $space . '*,' . $space . '*([^\s\d\n])/u', '\1, \2', $str );
	$str = preg_replace( '/([^\s\d])' . $space . '*,' . $space . '*(\d)/u', '\1, \2', $str );
	$str = preg_replace( '/(\d)' . $space . '+,' . $space . '*(\d)/u', '\1, \2', $str );
	$str = preg_replace( '/(\d)' . $space . '*,' . $space . '+(\d)/u', '\1, \2', $str );

	// One space before, no space after.
	$str = preg_replace( '/' . $space . '*([([])' . $space . '*/u', ' \1', $str );

	// Space before and after for numbers.
	$str = preg_replace( '/([^\s\d])' . $space . '*-' . $space . '+([^\s\d])/u', '\1' . $nbsp . '- \2', $str );
	$str = preg_replace( '/([^\s\d])' . $space . '+-' . $space . '*([^\s\d])/u', '\1' . $nbsp . '- \2', $str );
	$str = preg_replace( '/(\d)' . $space . '*-' . $space . '*(\d)/u', '\1' . $nbsp . '- \2', $str );

	// Non-breaking space before, space after (cannot handle minus easily).
	$str = preg_replace( '/' . $space . '*([»:;?!%€$£=<>~+])' . $space . '*/u', $nbsp . '\1 ', $str );

	// Cleanup: remove starting and trailing spaces.
	$str = preg_replace( '/^' . $space . '+/mu', '', $str );
	$str = preg_replace( '/' . $space . '+$/mu', '', $str );

	// Do not allow multiples spaces in a row (multiple spaces/tabs = 1 space, nbsp + space = nbsp).
	$str = preg_replace( '/[ \t]+/mu', ' ', $str );
	$str = preg_replace( '/' . $space . '*' . $nbsp . $space . '*/mu', $nbsp, $str );

	// Insert invariants back in text.
	return glotpress_replace_placeholder_invariants( $str, $invariants );
}
