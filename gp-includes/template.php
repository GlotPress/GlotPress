<?php
function gp_tmpl_load( $template, $args = array(), $template_path = null ) {
	$args = gp_tmpl_filter_args( $args );
	do_action_ref_array( 'pre_tmpl_load', array( $template, &$args ) );
	require_once GP_TMPL_PATH . 'helper-functions.php';
	$locations = array( GP_TMPL_PATH );
	if ( !is_null( $template_path ) ) {
		array_unshift( $locations, untrailingslashit( $template_path ) . '/' );
	}
	if ( isset( $args['http_status'] ) )
		status_header( $args['http_status'] );
	foreach( $locations as $location ) {
	 	$file = $location . "$template.php";	
		if ( is_readable( $file ) ) {
			extract( $args, EXTR_SKIP );
			include $file;
			break;
		}
	}
	do_action_ref_array( 'post_tmpl_load', array( $template, &$args ) );
}

function gp_tmpl_get_output() {
	$args = func_get_args();
	ob_start();
	call_user_func_array( 'gp_tmpl_load', $args );
	$contents = ob_get_contents();
	ob_end_clean();
	return $contents;
}

function gp_tmpl_header( $args = array( ) ) {
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
	gp_tmpl_load( '404', $args + array('title' => __('Not Found'), 'http_status' => 404 ) );
	exit();
}

function gp_title( $title = null ) {
	if ( !is_null( $title ) )
		add_filter( 'gp_title', lambda( '$x', '$title', compact( 'title' ) ), 5 );
	else
		return apply_filters( 'gp_title', '' );
}

function gp_breadcrumb( $breadcrumb = null, $args = array() ) {
	$defaults = array(
		/* translators: separates links in the navigation breadcrumb */
		'separator' => '<span class="separator">'._x('&rarr;', 'breadcrumb').'</span>',
		'breadcrumb-template' => '<span class="breadcrumb">{separator}{breadcrumb}</span>',
	);
	$args = array_merge( $defaults, $args );
	if ( !is_null( $breadcrumb ) ) {
		$breadcrumb = gp_array_flatten( $breadcrumb );
		$breadcrumb_string = implode( $args['separator'], array_filter( $breadcrumb ) );
		$whole_breadcrumb = str_replace( '{separator}', $args['separator'], $args['breadcrumb-template'] );
		$whole_breadcrumb = str_replace( '{breadcrumb}', $breadcrumb_string, $whole_breadcrumb );
		add_filter( 'gp_breadcrumb', lambda( '$x', '$whole_breadcrumb', compact( 'whole_breadcrumb' ) ), 5 );
	} else {
		return apply_filters( 'gp_breadcrumb', '' );
	}
}

function gp_project_links_from_root( $leaf_project ) {
	$links = array();
	$path_from_root = array_reverse( $leaf_project->path_to_root() );
	$links[] = empty( $path_from_root)? 'Projects' : gp_link_get( gp_url( '/projects' ), 'Projects' );
	foreach( $path_from_root as $project ) {
		$links[] = gp_link_project_get( $project, esc_html( $project->name ) );
	}
	return $links;
}

function gp_breadcrumb_project( $project ) {
	return gp_breadcrumb( gp_project_links_from_root( $project ) );
}

function gp_js_focus_on( $html_id ) {
	return '<script type="text/javascript">document.getElementById("'.$html_id.'").focus();</script>';
}

function gp_select( $name_and_id, $options, $selected_key, $attrs = array() ) {
	$attributes = gp_html_attributes( $attrs );
	$attributes = $attributes? " $attributes" : '';
	$res = "<select name='" . esc_attr( $name_and_id ) . "' id='" . esc_attr( $name_and_id ) . "' $attributes>\n";
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
		if ( $page - $surrounding > 1 ) $prev_dots = '<span class="dots">&hellip;</span>';
	}
	if ( $page < $pages ) {
		$next_pages = array();
		foreach( range( $page + 1, min( $pages, $page + $surrounding ) ) as $next_page ) {
			$next_pages[] = gp_link_get( add_query_arg( array( 'page' => $next_page ) ), $next_page );
		}
		$next_pages = implode( ' ', $next_pages );
		if ( $page + $surrounding < $pages ) $next_dots = '<span class="dots">&hellip;</span>';
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

function gp_html_attributes( $attrs ) {
	$attrs = wp_parse_args( $attrs );
	$strings = array();
	foreach( $attrs as $key => $value ) {
		$strings[] = $key.'="'.esc_attr( $value ).'"';
	}
	return implode( ' ', $strings );
}

function gp_attrs_add_class( $attrs, $class_name ) {
	$attrs['class'] = isset( $attrs['class'] )? $attrs['class'] . ' ' . $class_name : $class_name;
	return $attrs;
}

function gp_locales_dropdown( $name_and_id, $selected_slug = null, $attrs = array() ) {
	$locales = GP_Locales::locales();
	$values = array_map( create_function( '$l', 'return $l->slug;'), $locales );
	$labels = array_map( create_function( '$l', 'return $l->slug." &mdash; ". $l->english_name;'), $locales );
	sort( $values );
	sort( $labels );
	return gp_select( $name_and_id, array_merge( array( '' => __('&mdash; Locale &mdash;') ), array_combine( $values, $labels ) ), $selected_slug, $attrs );
}

function gp_projects_dropdown( $name_and_id, $selected_project_id = null, $attrs = array() ) {
	$projects = GP::$project->all();
	// TODO: mark which nodes are editable by the current user
	$tree = array();
	$top = array();
	foreach( $projects as $p ) {
		$tree[$p->id]['self'] = $p;
		if ( $p->parent_project_id ) {
			$tree[$p->parent_project_id]['children'][] = $p->id;
		} else {
			$top[] = $p->id;
		}
	}
	$options = array( '' => __('&mdash; No parent &mdash;') );
	$stack = array();
	foreach( $top as $top_id ) {
		$stack = array( $top_id );
		while ( !empty( $stack ) ) {
			$id = array_pop( $stack );
			$tree[$id]['level'] = gp_array_get( $tree[$id], 'level', 0 );
			$options[$id] = str_repeat( '-', $tree[$id]['level'] ) . $tree[$id]['self']->name;
			foreach( gp_array_get( $tree[$id], 'children', array() ) as $child_id ) {
				$stack[] = $child_id;
				$tree[$child_id]['level'] = $tree[$id]['level'] + 1;
			}
		}
	}
	return gp_select( $name_and_id, $options, $selected_project_id, $attrs );
}

function gp_array_of_things_to_json( $array ) {
	return json_encode( array_map( lambda( '$thing', '$thing->fields();' ), $array ) );
}

function gp_array_of_array_of_things_to_json( $array ) {
	$map_to_fields = create_function( '$array', 'return array_map( lambda( \'$thing\', \'$thing->fields();\' ), $array );' );
	return json_encode( array_map( $map_to_fields, $array ) );
}

function gp_preferred_sans_serif_style_tag( $locale ) {
	if ( $locale->preferred_sans_serif_font_family ) {
		echo <<<HTML
	<style type="text/css">
		.foreign-text {
			font-family: "$locale->preferred_sans_serif_font_family", inherit;
		}
	</style>

HTML;
	}
}

function gp_html_excerpt( $str, $count, $ellipsis = '&hellip;') {
	$excerpt = trim( wp_html_excerpt( $str, $count ) );
	if ( $str != $excerpt ) {
		$excerpt .= $ellipsis;
	}
	return $excerpt;
}

function gp_checked( $checked ) {
	if ( $checked ) {
		echo 'checked="checked"';
	}
}