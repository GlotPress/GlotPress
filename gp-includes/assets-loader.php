<?php
/**
 * Defines default styles and scripts
 */

/**
 * Register the GlotPress styles
 *
 * @param WP_Styles $styles
 */
function gp_styles_default( &$styles ) {
	$url = gp_plugin_url( 'assets/css' );

	$styles->add( 'base', $url . '/style.css', array(), '20150717' );
}

add_action( 'wp_default_styles', 'gp_styles_default' );

/**
 * Register the GlotPress scripts
 *
 * @param WP_Scripts $scripts
 */
function gp_scripts_default( &$scripts ) {
	global $gp_registered_scripts;

	$url = gp_plugin_url( 'assets/js' );

	$gp_registered_scripts['tablesorter'] = array( $url . '/jquery.tablesorter.min.js', array( 'jquery' ), '1.10.4' );
	$gp_registered_scripts['gp-common'] = array( $url . '/common.js', array( 'jquery' ), '20150430' );
	$gp_registered_scripts['gp-editor'] = array( $url . '/editor.js', array( 'gp-common', 'jquery-ui-tooltip' ), '20160329' );
	$gp_registered_scripts['gp-glossary'] = array( $url . '/glossary.js', array( 'gp-common' ), '20160329' );
	$gp_registered_scripts['gp-translations-page'] = array( $url . '/translations-page.js', array( 'gp-common' ), '20150430' );
	$gp_registered_scripts['mass-create-sets-page'] = array( $url . '/mass-create-sets-page.js', array( 'gp-common' ), '20150430' );
}

add_action( 'wp_default_scripts', 'gp_scripts_default' );

/**
 * Here we abstract WordPress core's enqueuing functions because...
 * 1. We don't want to print scripts and styles that are meant for the WordPress theme
 * 2. GlotPress enqueues scripts and styles from its template files and if we do that
 *    with wp_enqueue_script() and wp_enqueue_style() WordPress complains that
 *    those functions should only be called inside of wp_enqueue_scripts()
 */

function gp_enqueue_scripts() {
}

add_action( 'wp_enqueue_scripts', 'gp_enqueue_scripts' );

function gp_enqueue_style( $handle ) {
	global $gp_enqueued_styles;

	$gp_enqueued_styles[] = $handle;
	wp_enqueue_style( $handle );
}

function gp_enqueue_script( $handle ) {
	global $gp_enqueued_scripts, $gp_registered_scripts;

	$gp_enqueued_scripts[] = $handle;

	if ( is_array( $handle ) && array_key_exists( $handle, $gp_registered_scripts ) ) {
		wp_register_script( $handle, $gp_registered_scripts[ $handle ][0], $gp_registered_scripts[ $handle ][1], $gp_registered_scripts[ $handle ][2] );
	}

	wp_enqueue_script( $handle );
}

function gp_print_styles() {
	global $gp_enqueued_styles;

	wp_print_styles( $gp_enqueued_styles );
}

function gp_print_scripts() {
	global $gp_enqueued_scripts;

	wp_print_scripts( $gp_enqueued_scripts );
}
