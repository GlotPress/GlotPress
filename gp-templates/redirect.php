<?php
/**
 * Templates: Redirect
 *
 * @package GlotPress
 * @subpackage Templates
 */

printf(
	/* translators: %s: URL. */
	__( 'Redirecting to: %s...', 'glotpress' ),
	esc_html( $url )
);
