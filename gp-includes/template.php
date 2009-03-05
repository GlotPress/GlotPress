<?php
function gp_tmpl_load( $template, $args = array() ) {	
	$all_locales = GP_Locales::locales();
	$args = gp_tmpl_filter_args( $args );
 	$file = GP_TMPL_PATH . "$template.php";
	if ( is_readable( $file ) ) {
		extract( $args, EXTR_SKIP );
		include $file;
	}
}

function gp_tmpl_header( $args = array( ) ) {
	if ( isset( $args['http_status'] ) )
		status_header( $args['http_status'] );
	gp_tmpl_load( 'header', $args );
}

function gp_tmpl_footer( $args = array( ) ) {
	gp_tmpl_load( 'footer', $args );
}


function gp_head() {
	do_action( 'gp_head' );
}

function gp_footer() {
	do_action( 'gp_footer' );
}

function gp_content() {
	do_action( 'gp_content' );
}

function gp_tmpl_filter_args( $args ) {
	$clean_args = array();
	foreach( $args as $k => $v )
		if ( $k[0] != '_' && $k != 'GLOBALS' && !gp_startswith( $k, 'HTTP' ) && !gp_startswith( $k, 'PHP' ) )
			$clean_args[$k] = $v;
	return $clean_args;
}

function gp_tmpl_404( $args = array()) {
	gp_tmpl_load( '404', array('title' => __('Not Found'), 'http_status' => 404 ) + $args );
	exit();
}

function gp_h( $s ) {
	return wp_specialchars( $s );
}

function gp_attr( $s ) {
	return attribute_escape( $s );
}

function gp_title( $title = null ) {
	if ( !is_null( $title ) )
		add_filter( 'gp_title', create_function( '$x', 'return '.var_export($title, true).';'), 5 );
	else
		return apply_filters( 'gp_title', '' );
}

function gp_breadcrumb( $breadcrumb = null ) {
	if ( !is_null( $breadcrumb ) ) {
		/* translators: separates links in the navigation breadcrumb */
		$breadcrumb_string = implode( ' '._x('&gt;', 'breadcrumb').' ', $breadcrumb );
		add_filter( 'gp_breadcrumb', create_function( '$x', 'return '.var_export($breadcrumb_string, true).';'), 5 );
	} else {
		return apply_filters( 'gp_breadcrumb', '' );
	}
}