<?php
function gp_tmpl_load( $template, $args = array() ) {
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

function gp_title( $title = null ) {
	if ( !is_null( $title ) )
		add_filter( 'gp_title', create_function( '$x', 'return '.var_export($title, true).';'), 5 );
	else
		return apply_filters( 'gp_title', '' );
}

function gp_breadcrumb( $breadcrumb = null ) {
	if ( !is_null( $breadcrumb ) ) {
		$separator = '<span class="separator">'._x('&rarr;', 'breadcrumb').'</span>';
		$breadcrumb_string = '<span class="breadcrumb">'.$separator;
		/* translators: separates links in the navigation breadcrumb */
		$breadcrumb_string .= implode( $separator, $breadcrumb );
		$breadcrumb_string .= '</span>';
		add_filter( 'gp_breadcrumb', create_function( '$x', 'return '.var_export($breadcrumb_string, true).';'), 5 );
	} else {
		return apply_filters( 'gp_breadcrumb', '' );
	}
}

function gp_js_focus_on( $html_id ) {
	return '<script type="text/javascript">document.getElementById("'.$html_id.'").focus();</script>';
}

function gp_select( $name_and_id, $options, $selected_key ) {
	$res = "<select name='" . esc_attr( $name_and_id ) . "' id='" . esc_attr( $name_and_id ) . "'>\n";
	foreach( $options as $value => $label ) {
		$selected = $value == $selected_key? " selected='selected'" : '';
		$res .= "\t<option value='".esc_attr( $value )."' $selected>" . esc_html( $label ) . "</option>\n";
	}
	$res .= "</select>\n";
	return $res;
}