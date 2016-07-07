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
 * Here we abstract WordPress core's enqueuing functions because...
 * 1. We don't want to print scripts and styles that are meant for the WordPress theme
 * 2. GlotPress enqueues scripts and styles from its template files and if we do that
 *    with wp_enqueue_script() and wp_enqueue_style() WordPress complains that
 *    those functions should only be called inside of wp_enqueue_scripts()
 */

function gp_register_scripts() {
	$url = gp_plugin_url( 'assets/js' );

	wp_register_script( 'tablesorter', $url . '/jquery.tablesorter.min.js', array( 'jquery' ), '1.10.4' );
	wp_register_script( 'gp-common', $url . '/common.js', array( 'jquery' ), '20150430' );
	wp_register_script( 'gp-editor', $url . '/editor.js', array( 'gp-common', 'jquery-ui-tooltip' ), '20160329' );
	wp_register_script( 'gp-glossary', $url . '/glossary.js', array( 'gp-common' ), '20160329' );
	wp_register_script( 'gp-translations-page', $url . '/translations-page.js', array( 'gp-common' ), '20150430' );
	wp_register_script( 'mass-create-sets-page', $url . '/mass-create-sets-page.js', array( 'gp-common' ), '20150430' );
}

add_action( 'init', 'gp_register_scripts' );

function gp_enqueue_style( $handle ) {
	global $gp_enqueued_styles;

	$gp_enqueued_styles[] = $handle;
	wp_enqueue_style( $handle );
}

function gp_enqueue_script( $handle ) {
	wp_enqueue_script( $handle );
}

function gp_print_styles() {
	global $gp_enqueued_styles;

	wp_print_styles( $gp_enqueued_styles );
}

function gp_print_scripts() {
	wp_print_scripts();
}
