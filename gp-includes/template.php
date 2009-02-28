<?php
function gp_tmpl_load( $template, $args = array() ) {
	$args = gp_tmpl_filter_args( $args );
 	$file = GP_TMPL_PATH . "$template.php";
	if ( is_readable( $file ) ) {
		extract( $args, EXTR_SKIP );
		include $file;
	}
}

function gp_tmpl_header( $args = array() ) {
	gp_tmpl_load( 'header', $args );
}

function gp_tmpl_page( $content_template, $args = array( ) ) {
	$args = gp_tmpl_filter_args( $args );
	if ( isset( $args['http_status'] ) )
		status_header( $args['http_status'] );
	gp_tmpl_load( 'layout', compact( 'content_template' ) + $args );
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

function gp_tmpl_404( $args ) {
	gp_tmpl_page( '404', array('title' => __('Not Found'), 'http_status' => 404 ) + $args );
	exit();
}