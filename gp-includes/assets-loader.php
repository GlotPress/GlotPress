<?php
/**
 * Defines default styles and scripts.
 *
 * @package GlotPress
 * @since 1.0.0
 */

/**
 * Register the GlotPress styles and load the base style sheet.
 */
function gp_styles_default() {
	$url = gp_plugin_url( 'assets/css' );

	// Register our base style.
	wp_register_style( 'gp-base', $url . '/style.css', array(), '20150717' );
}

add_action( 'init', 'gp_styles_default' );

/**
 * Register the GlotPress scripts.
 */
function gp_register_scripts() {
	$url = gp_plugin_url( 'assets/js' );

	// Register our standard scripts.
	wp_register_script( 'tablesorter', $url . '/jquery.tablesorter.min.js', array( 'jquery' ), '1.10.4' );
	wp_register_script( 'gp-common', $url . '/common.js', array( 'jquery' ), '20150430' );
	wp_register_script( 'gp-editor', $url . '/editor.js', array( 'gp-common', 'jquery-ui-tooltip' ), '20160329' );
	wp_register_script( 'gp-glossary', $url . '/glossary.js', array( 'gp-common', 'gp-editor' ), '20160329' );
	wp_register_script( 'gp-translations-page', $url . '/translations-page.js', array( 'gp-common', 'gp-editor' ), '20150430' );
	wp_register_script( 'gp-mass-create-sets-page', $url . '/mass-create-sets-page.js', array( 'gp-common', 'gp-editor' ), '20150430' );
}

add_action( 'init', 'gp_register_scripts' );

/**
 * Enqueue one or more styles.
 *
 * @param string|array $handles A single style handle to enqueue or an array or style handles to enqueue.
 */
function gp_enqueue_style( $handles ) {
	/*
	 * Use a global variable to store which styles we have enqueued so we can limit the
	 * output of styles to only those that GlotPress has enqueued later in gp_print_styles().
	 */
	global $gp_enqueued_styles;

	// Check to see if $handles is an array, if not, then we can make it one to simplify the next loop.
	if ( ! is_array( $handles ) ) {
		$handles = array( $handles );
	}

	// Loop through each handle we've been asked to enqueue.
	foreach ( $handles as $handle ) {
		// Store the handle name in the global array.
		$gp_enqueued_styles[] = $handle;

		// Actually enqueue the handle via WordPress.
		wp_enqueue_style( $handle );
	}
}

/**
 * Enqueue one or more script.
 *
 * @param string|array $handles A single script handle to enqueue or an array or enqueue handles to enqueue.
 */
function gp_enqueue_script( $handles ) {
	/*
	 * Use a global variable to store which scripts we have enqueued so we can limit the
	 * output of scripts to only those that GlotPress has enqueued later in gp_print_scripts().
	 */
	global $gp_enqueued_scripts;

	// Check to see if $handles is an array, if not, then we can make it one to simplify the next loop.
	if ( ! is_array( $handles ) ) {
		$handles = array( $handles );
	}

	// Loop through each handle we've been asked to enqueue.
	foreach ( $handles as $handle ) {
		// Store the handle name in the global array.
		$gp_enqueued_scripts[] = $handle;

		// Actually enqueue the handle via WordPress.
		wp_enqueue_script( $handle );
	}
}

/**
 * Print the styles that have been enqueued.
 */
function gp_print_styles() {
	global $gp_enqueued_styles;

	// Only output the styles that GlotPress has registered, otherwise we'd be sending any style that the WordPress theme or plugins may have enqueued.
	wp_print_styles( $gp_enqueued_styles );
}

/**
 * Print the scripts that have been enqueued.
 */
function gp_print_scripts() {
	global $gp_enqueued_scripts;

	// Only output the scripts that GlotPress has registered, otherwise we'd be sending any scripts that the WordPress theme or plugins may have enqueued.
	wp_print_scripts( $gp_enqueued_scripts );
}
