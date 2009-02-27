<?php
/**
 * Defines default styles and scripts
 */

function gp_styles_default( &$styles ) {
	$styles->base_url = gp_get_option( 'url' ) . '/css';
    $styles->default_version = gp_get_option( 'version' );
	// TODO: get text direction for current locale
    //$styles->text_direction = 'rtl' == get_bloginfo( 'text_direction' ) ? 'rtl' : 'ltr';
	$styles->text_direction = 'ltr';
	
	$styles->add( 'base', '/base.css', array() );
}

add_action( 'wp_default_styles', 'gp_styles_default' );