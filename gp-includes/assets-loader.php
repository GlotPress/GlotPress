<?php
/**
 * Defines default styles and scripts
 */

function gp_styles_default( &$styles ) {
	$url = gp_url_public_root() . 'css';

	$styles->add( 'base', $url . '/style.css', array(), '20141019' );
	$styles->add( 'install', $url . '/install.css', array('base'), '20140902' );
}

add_action( 'wp_default_styles', 'gp_styles_default' );

function gp_scripts_default( &$scripts ) {
	$url = gp_url_public_root() . 'js';

	$bump = '20150430';

	$scripts->add( 'tablesorter', $url . '/jquery.tablesorter.min.js', array('jquery'), '1.10.4' );

	$scripts->add( 'gp-common', $url . '/common.js', array( 'jquery' ), $bump );
	$scripts->add( 'gp-editor', $url . '/editor.js', array( 'gp-common', 'jquery-ui-tooltip' ), $bump );
	$scripts->add( 'gp-glossary', $url . '/glossary.js', array( 'gp-common' ), $bump );
	$scripts->add( 'translations-page', $url . '/translations-page.js', array( 'gp-common' ), $bump );
	$scripts->add( 'mass-create-sets-page', $url . '/mass-create-sets-page.js', array( 'gp-common' ), $bump );
}

add_action( 'wp_default_scripts', 'gp_scripts_default' );
