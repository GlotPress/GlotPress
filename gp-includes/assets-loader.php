<?php
/**
 * Defines default styles and scripts
 */

function gp_styles_default( &$styles ) {
	$styles->base_url = gp_url_base( 'css' );
	$styles->default_version = gp_get_option( 'version' );
	// TODO: get text direction for current locale
	//$styles->text_direction = 'rtl' == get_bloginfo( 'text_direction' ) ? 'rtl' : 'ltr';
	$styles->text_direction = 'ltr';
	
	$styles->add( 'base', '/style.css', array(), '20120410' );
}

add_action( 'wp_default_styles', 'gp_styles_default' );

function gp_scripts_default( &$scripts ) {
	$scripts->base_url = gp_url_base( 'js' );
	$scripts->default_version = gp_get_option( 'version' );

	$bump = '20120410';

	$scripts->add( 'jquery', '/jquery/jquery.js', array(), '1.7.2' );
	$scripts->add( 'jquery-ui', '/jquery/jquery-ui.js', array('jquery'), '1.8.18' );
	$scripts->add( 'jquery-ui-autocomplete', null, array('jquery-ui'), '1.8.18' );
	$scripts->add( 'jquery-ui-selectable', null, array('jquery-ui'), '1.8.18' );
	$scripts->add( 'jquery-ui-tabs', null, array('jquery-ui'), '1.8.18' );
	$scripts->add( 'common', '/common.js', array( 'jquery' ), $bump );
	$scripts->add( 'editor', '/editor.js', array( 'common', 'google-js-api' ), $bump );
	$scripts->add( 'translations-page', '/translations-page.js', array( 'common' ), $bump );
	$scripts->add( 'mass-create-sets-page', '/mass-create-sets-page.js', array( 'common' ), $bump );
	$scripts->add( 'google-js-api', 'http://www.google.com/jsapi', array(), '' );
}

add_action( 'wp_default_scripts', 'gp_scripts_default' );
