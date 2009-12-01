<?php
function gp_tmpl_load( $template, $args = array() ) {
	$args = gp_tmpl_filter_args( $args );
	do_action_ref_array( 'pre_tmpl_load', array( $template, &$args ) );
	require_once GP_TMPL_PATH . 'helper-functions.php';
 	$file = GP_TMPL_PATH . "$template.php";
	if ( is_readable( $file ) ) {
		extract( $args, EXTR_SKIP );
		include $file;
	}
	do_action_ref_array( 'post_tmpl_load', array( $template, &$args ) );
}

function gp_tmpl_get_output( $template, $args = array() ) {
	ob_start();
	gp_tmpl_load( $template, $args );
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
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
		/* translators: separates links in the navigation breadcrumb */		
		$separator = '<span class="separator">'._x('&rarr;', 'breadcrumb').'</span>';
		$breadcrumb_string = '<span class="breadcrumb">'.$separator;
		$breadcrumb_string .= implode( $separator, array_filter( $breadcrumb ) );
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

function gp_radio_buttons( $name, $radio_buttons, $checked_key ) {
	$res = '';
	foreach( $radio_buttons as $value => $label ) {
		$checked = $value == $checked_key? " checked='checked'" : '';
		// TODO: something more flexible than <br />
		$res .= "\t<input type='radio' name='$name' value='".esc_attr( $value )."' $checked id='{$name}[{$value}]'/>&nbsp;";
		$res .= "<label for='{$name}[{$value}]'>".esc_html( $label )."</label><br />\n";
	}
	return $res;
}

function gp_pagination( $page, $per_page, $objects ) {
	$surrounding = 2;
	$prev = $first = $prev_dots = $prev_pages = $current = $next_pages = $next_dots = $last = $next = '';
	$page = intval( $page )? intval( $page ) : 1;
	$pages = ceil( $objects / $per_page );
	if ( $page > $pages ) return '';
	if ( $page > 1 )
		$prev = gp_link_get( add_query_arg( array( 'page' => $page - 1 ) ), '&larr;', array('class' => 'previous') );
	else
		$prev = '<span class="previous disabled">&larr;</span>';
	if ( $page < $pages )
		$next = gp_link_get( add_query_arg( array( 'page' => $page + 1)), '&rarr;', array('class' => 'next') );
	else
		$next = '<span class="next disabled">&rarr;</span>';
	$current = '<span class="current">'.$page.'</span>';
	if ( $page > 1 ) {
		$prev_pages = array();
		foreach( range( max( 1, $page - $surrounding ), $page - 1 ) as $prev_page ) {
			$prev_pages[] = gp_link_get( add_query_arg( array( 'page' => $prev_page ) ), $prev_page );
		}
		$prev_pages = implode( ' ', $prev_pages );
		if ( $page - $surrounding > 1 ) $prev_dots = '<span class="dots">&hellip</span>';
	}
	if ( $page < $pages ) {
		$next_pages = array();
		foreach( range( $page + 1, min( $pages, $page + $surrounding ) ) as $next_page ) {
			$next_pages[] = gp_link_get( add_query_arg( array( 'page' => $next_page ) ), $next_page );
		}
		$next_pages = implode( ' ', $next_pages );
		if ( $page + $surrounding < $pages ) $next_dots = '<span class="dots">&hellip</span>';
	}
	if ( $prev_dots ) $first = gp_link_get( add_query_arg( array( 'page' => 1 ) ), 1 );
	if ( $next_dots ) $last = gp_link_get( add_query_arg( array( 'page' => $pages ) ), $pages );
 	$html = <<<HTML
	<div class="paging">
		$prev
		$first
		$prev_dots
		$prev_pages
		$current
		$next_pages
		$next_dots
		$last
		$next
	</div>
HTML;
	return apply_filters( 'gp_pagination', $html, $page, $per_page, $objects );
}