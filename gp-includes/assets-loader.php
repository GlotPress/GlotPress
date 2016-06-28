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
	$url = gp_plugin_url( 'assets/js' );

	$scripts->add( 'tablesorter', $url . '/jquery.tablesorter.min.js', array( 'jquery' ), '1.10.4' );

	$scripts->add( 'gp-common', $url . '/common.js', array( 'jquery' ), '20150430' );
	$scripts->add( 'gp-editor', $url . '/editor.js', array( 'gp-common', 'jquery-ui-tooltip' ), '20160329' );
	$scripts->add( 'gp-glossary', $url . '/glossary.js', array( 'gp-common' ), '20160329' );
	$scripts->add( 'gp-translations-page', $url . '/translations-page.js', array( 'gp-common' ), '20150430' );
	$scripts->add( 'mass-create-sets-page', $url . '/mass-create-sets-page.js', array( 'gp-common' ), '20150430' );
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
	global $gp_enqueued_styles, $gp_enqueued_scripts;

	if ( ! empty( $gp_enqueued_scripts ) ) {
		foreach ( $gp_enqueued_scripts as $handle ) {
			wp_enqueue_script( $handle );
		}
	}

	if ( ! empty( $gp_enqueued_styles ) ) {
		foreach ( $gp_enqueued_styles as $handle ) {
			wp_enqueue_style( $handle );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'gp_enqueue_scripts' );

function gp_enqueue_style( $handle ) {
	global $gp_enqueued_styles;

	$gp_enqueued_styles[] = $handle;
}

function gp_enqueue_script( $handle ) {
	global $gp_enqueued_scripts;

	$gp_enqueued_scripts[] = $handle;
}

function gp_print_styles() {
	global $gp_enqueued_styles;
	wp_print_styles( $gp_enqueued_styles );
}

function gp_print_scripts() {
	global $gp_enqueued_scripts;
	wp_print_scripts( $gp_enqueued_scripts );
}

/**
 * Calls the i18n JS variables via wp_localize_script()
 *
 * We wrap this in a function and call it via an action for the correct load priorities.
 *
 * @since 2.1.0
 */
function gp_localize_script(){
	wp_localize_script( 'gp-translations-page', '$gp_translations_options', array( 'sort' => __( 'Sort', 'glotpress' ), 'filter' => __( 'Filter', 'glotpress' ) ) );

	// localizer adds var in front of the variable name, so we can't use $gp.editor.options
	$editor_options = compact('can_approve', 'can_write', 'url', 'discard_warning_url', 'set_priority_url', 'set_status_url');

	wp_localize_script( 'gp-editor', '$gp_editor_options', $editor_options );
}