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

	$styles->add( 'base', '/style.css', array(), '20150717' );
	$styles->add( 'install', '/install.css', array('base'), '20140902' );
}

add_action( 'wp_default_styles', 'gp_styles_default' );

function gp_scripts_default( &$scripts ) {
	$scripts->base_url = gp_url_base( 'js' );
	$scripts->default_version = gp_get_option( 'version' );

	$bump = '20150430';

	$scripts->add( 'jquery', '/jquery/jquery.js', array(), '1.11' );
	$scripts->add( 'jquery-ui', '/jquery/jquery-ui.js', array('jquery'), '1.10.4' );
	$scripts->add( 'jquery-ui-autocomplete', null, array('jquery-ui'), '1.10.4' );
	$scripts->add( 'jquery-ui-selectable', null, array('jquery-ui'), '1.10.4' );
	$scripts->add( 'jquery-ui-tabs', null, array('jquery-ui'), '1.10.4' );
	$scripts->add( 'tablesorter', '/jquery.tablesorter.min.js', array('jquery'), '1.10.4' );

	$scripts->add( 'common', '/common.js', array( 'jquery' ), $bump );
	$scripts->add( 'editor', '/editor.js', array( 'common' ), $bump );
	$scripts->add( 'glossary', '/glossary.js', array( 'common' ), $bump );
	$scripts->add( 'translations-page', '/translations-page.js', array( 'common' ), $bump );
	$scripts->add( 'mass-create-sets-page', '/mass-create-sets-page.js', array( 'common' ), $bump );
}

add_action( 'wp_default_scripts', 'gp_scripts_default' );
