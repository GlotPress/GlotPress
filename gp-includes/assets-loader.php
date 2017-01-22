<?php
/**
 * Defines default styles and scripts.
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Registers the GlotPress styles and loads the base style sheet.
 */
function gp_register_default_styles() {
	$url = gp_plugin_url( 'assets/css' );

	$suffix = SCRIPT_DEBUG ? '.css' : '.min.css';

	// Register our base style.
	wp_register_style( 'gp-base', $url . '/style' . $suffix, array(), '20170110' );

	// Register the table sorter theme style.
	wp_register_style( 'tablesorter-theme', $url . '/tablesorter.theme.glotpress' . $suffix, array(), '20161011' );
}

add_action( 'init', 'gp_register_default_styles' );

/**
 * Register the GlotPress scripts.
 */
function gp_register_default_scripts() {
	$url = gp_plugin_url( 'assets/js' );

	$suffix = SCRIPT_DEBUG ? '.js' : '.min.js';

	// Register our standard scripts.
	wp_register_script( 'tablesorter', $url . '/vendor/jquery.tablesorter' . $suffix, array( 'jquery' ), '2.17.8' );
	wp_register_script( 'gp-common', $url . '/common' . $suffix, array( 'jquery' ), '20150430' );
	wp_register_script( 'gp-editor', $url . '/editor' . $suffix, array( 'gp-common', 'jquery-ui-tooltip' ), '20170110' );
	wp_register_script( 'gp-glossary', $url . '/glossary' . $suffix, array( 'gp-editor' ), '20160329' );
	wp_register_script( 'gp-translations-page', $url . '/translations-page' . $suffix, array( 'gp-editor' ), '20150430' );
	wp_register_script( 'gp-mass-create-sets-page', $url . '/mass-create-sets-page' . $suffix, array( 'gp-editor' ), '20150430' );
}

add_action( 'init', 'gp_register_default_scripts' );

/**
 * Enqueue one or more styles.
 *
 * @since 2.2.0
 *
 * @param string|array $handles A single style handle to enqueue or an array or style handles to enqueue.
 */
function gp_enqueue_styles( $handles ) {
	// Make sure $handles is an array to simplify the next loop.
	$handles = (array) $handles;

	// Loop through each handle we've been asked to enqueue.
	foreach ( $handles as $handle ) {
		gp_enqueue_style( $handle );
	}
}

/**
 * Enqueue one or more styles.
 *
 * @since 1.0.0
 *
 * @param string $handle A single style handle to enqueue.
 */
function gp_enqueue_style( $handle ) {
	if ( ! in_array( $handle, GP::$styles, true ) ) {
		// Store the handle name in the global array.
		GP::$styles[] = $handle;

		// Actually enqueue the handle via WordPress.
		wp_enqueue_style( $handle );
	}
}

/**
 * Enqueue one or more scripts.
 *
 * @since 2.2.0
 *
 * @param string|array $handles A single script handle to enqueue or an array of enqueue handles to enqueue.
 */
function gp_enqueue_scripts( $handles ) {
	// Make sure $handles is an array to simplify the next loop.
	$handles = (array) $handles;

	// Loop through each handle we've been asked to enqueue.
	foreach ( $handles as $handle ) {
		gp_enqueue_script( $handle );
	}
}

/**
 * Enqueue one or more scripts.
 *
 * @since 1.0.0
 *
 * @param string $handle A single script handle to enqueue.
 */
function gp_enqueue_script( $handle ) {
	if ( ! in_array( $handle, GP::$scripts, true ) ) {
		// Store the handle name in the global array.
		GP::$scripts[] = $handle;

		// Actually enqueue the handle via WordPress.
		wp_enqueue_script( $handle );
	}
}

/**
 * Print the styles that have been enqueued.
 *
 * Only output the styles that GlotPress has registered, otherwise we'd be sending any style that the WordPress theme or plugins may have enqueued.
 *
 * @since 2.2.0
 */
function gp_print_styles() {
	wp_print_styles( GP::$styles );
}

/**
 * Print the scripts that have been enqueued.
 *
 * Only output the scripts that GlotPress has registered, otherwise we'd be sending any scripts that the WordPress theme or plugins may have enqueued.
 *
 * @since 2.2.0
 */
function gp_print_scripts() {
	wp_print_scripts( GP::$scripts );
}
