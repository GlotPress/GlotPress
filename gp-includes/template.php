<?php
function gp_tmpl_load( $template, $args = array(), $template_path = null ) {
	$args = gp_tmpl_filter_args( $args );
	do_action_ref_array( 'gp_pre_tmpl_load', array( $template, &$args ) );
	require_once GP_TMPL_PATH . 'helper-functions.php';
	$locations = array( GP_TMPL_PATH );
	if ( !is_null( $template_path ) ) {
		array_unshift( $locations, untrailingslashit( $template_path ) . '/' );
	}
	$locations = apply_filters( 'gp_tmpl_load_locations', $locations, $template, $args, $template_path );
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
	do_action_ref_array( 'gp_post_tmpl_load', array( $template, &$args ) );
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

function gp_nav_menu( $location = 'main' ) {
	$html  = '';
	$items = gp_nav_menu_items( $location );

	foreach ( $items as $link => $title ) {
		$html .= '<a href="' . $link . '">' . $title . '</a>';
	}

	return $html;
}

function gp_nav_menu_items( $location = 'main' ) {
	$items = array();

	if ( 'main' === $location ) {
		$items[ gp_url( '/projects' ) ]  = __( 'Projects', 'glotpress' );
		$items[ gp_url( '/languages' ) ] = __( 'Locales', 'glotpress' );
	}
	elseif ( 'side' === $location ) {
		if ( is_user_logged_in() ) {
			$items[ gp_url( '/profile' ) ] = __( 'Profile', 'glotpress' );
			$items[ esc_url( wp_logout_url( gp_url_current() ) ) ]  = __( 'Log out', 'glotpress' );
		}
		else {
			$items[ esc_url( wp_login_url( gp_url_current() ) ) ] = __( 'Log in', 'glotpress' );
		}
	}

	return apply_filters( 'gp_nav_menu_items', $items, $location );
}

function gp_tmpl_filter_args( $args ) {
	$clean_args = array();
	foreach( $args as $k => $v )
		if ( $k[0] != '_' && $k != 'GLOBALS' && !gp_startswith( $k, 'HTTP' ) && !gp_startswith( $k, 'PHP' ) )
			$clean_args[$k] = $v;
	return $clean_args;
}

function gp_tmpl_404( $args = array()) {
	gp_tmpl_load( '404', $args + array('title' => __('Not Found', 'glotpress' ), 'http_status' => 404 ) );
	exit();
}

function gp_title( $title = null ) {
	if ( ! is_null( $title ) ) {
		add_filter( 'gp_title', function() use ( $title ) {
			return $title;
		}, 5 );
	} else {
		return apply_filters( 'gp_title', '' );
	}
}

function gp_breadcrumb( $breadcrumb = null, $args = array() ) {
	if ( $breadcrumb ) {
		$breadcrumb = gp_array_flatten( $breadcrumb );

		add_filter( 'gp_breadcrumb_items', function( $breadcrumbs ) use ( $breadcrumb ) {
			return array_merge( $breadcrumbs, $breadcrumb );
		}, 1 );
	} else {
		$breadcrumbs = apply_filters( 'gp_breadcrumb_items', array() );

		if ( $breadcrumbs ) {
			$defaults = array(
				/* translators: separates links in the navigation breadcrumb */
				'before'              => '<li>',
				'after'               => '</li>',
				'breadcrumb-template' => '<ul class="breadcrumb">{breadcrumb}</ul>',
			);
			$args = array_merge( $defaults, $args );

			$whole_breadcrumb = '';

			foreach ( $breadcrumbs as $breadcrumb ) {
				$whole_breadcrumb .= $args['before'] . $breadcrumb . $args['after'];
			}

			$whole_breadcrumb  = str_replace( '{breadcrumb}', $whole_breadcrumb, $args['breadcrumb-template'] );

			return apply_filters( 'gp_breadcrumb', $whole_breadcrumb );
		}
	}
}

function gp_project_names_from_root( $leaf_project ) {
	$names = array();
	$path_from_root = array_reverse( $leaf_project->path_to_root() );

	foreach ( $path_from_root as $project ) {
		$names[] = esc_html($project->name);
	}

	$project_path = implode( " | ", $names );

	return $project_path;
}

function gp_project_links_from_root( $leaf_project ) {
	$links = array();
	$path_from_root = array_reverse( $leaf_project->path_to_root() );
	$links[] = empty( $path_from_root)? __( 'Projects', 'glotpress' ) : gp_link_get( gp_url( '/projects' ), __( 'Projects', 'glotpress' ) );
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
	$first = $prev_dots = $prev_pages = $next_pages = $next_dots = $last = '';
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

/**
 * Returns HTML markup for a select element for all locales of a project.
 *
 * @since 1.0.0
 *
 * @param int    $project_id    ID of a project.
 * @param string $name_and_id   Name and ID of the select element.
 * @param string $selected_slug Slug of the current selected locale.
 * @param array  $attrs         Extra attributes.
 * @return string HTML markup for a select element.
 */
function gp_locales_by_project_dropdown( $project_id, $name_and_id, $selected_slug = null, $attrs = array() ) {
	$locales = GP_Locales::locales();
	if ( null != $project_id  ) {
		$sets = GP::$translation_set->by_project_id( $project_id );

		$temp_locales = array();

		foreach( $sets as $set ) {
			$temp_locales[ $set->locale ] = $locales[ $set->locale ];
		}

		if ( count( $temp_locales ) > 0 ) {
			$locales = $temp_locales;
		}
	}
	ksort( $locales );

	$options = array( '' => __( '&mdash; Locale &mdash;', 'glotpress' ) );
	foreach ( $locales as $key => $locale ) {
		$options[ $key ] = sprintf( '%s &mdash; %s', $locale->slug, $locale->english_name );
	}

	return gp_select( $name_and_id, $options, $selected_slug, $attrs );
}

/**
 * Returns HTML markup for a select element for all locales.
 *
 * @since 1.0.0
 *
 * @param string $name_and_id   Name and ID of the select element.
 * @param string $selected_slug Slug of the current selected locale.
 * @param array  $attrs         Extra attributes.
 * @return string HTML markup for a select element.
 */
function gp_locales_dropdown( $name_and_id, $selected_slug = null, $attrs = array() ) {
	return gp_locales_by_project_dropdown( null, $name_and_id, $selected_slug, $attrs );
}

function gp_projects_dropdown( $name_and_id, $selected_project_id = null, $attrs = array(), $exclude = array() ) {
	if ( ! is_array( $exclude ) ) {
		$exclude = array( $exclude );
	}

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

	$options = array( '' => __( '&mdash; No parent &mdash;', 'glotpress' ) );

	foreach( $top as $top_id ) {
		$stack = array( $top_id );

		while ( !empty( $stack ) ) {
			$id = array_pop( $stack );

			if ( in_array( $id, $exclude ) ) {
				continue;
			}

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
	return wp_json_encode( array_map( function( $thing ) { return $thing->fields(); }, $array ) );
}

function gp_array_of_array_of_things_to_json( $array ) {
	$map_to_fields = function( $array ) {
		return array_map( function( $thing ) {
			return $thing->fields();
		}, $array );
	};

	return wp_json_encode( array_map( $map_to_fields, $array ) );
}

function things_to_fields( $data ) {
	if( is_array( $data ) ) {
		foreach( $data as $item_id => $item ) {
			$data[ $item_id ] = things_to_fields( $item );
		}
	}
	else if ( $data instanceof GP_Thing ) {
		$data = $data->fields();
	}

	return $data;
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

function gp_project_actions( $project, $translation_sets ) {
	$actions = array(
		gp_link_get( gp_url_project( $project, 'import-originals' ), __( 'Import originals', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, array( '-permissions' ) ), __( 'Permissions', 'glotpress') ),
		gp_link_get( gp_url_project( '', '-new', array('parent_project_id' => $project->id) ), __( 'New Sub-Project', 'glotpress' ) ),
		gp_link_get( gp_url( '/sets/-new', array( 'project_id' => $project->id ) ), __( 'New Translation Set', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, array( '-mass-create-sets' ) ), __( 'Mass-create Translation Sets', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, '-branch'), __( 'Branch Project', 'glotpress' ) ),
		gp_link_with_ays_get( gp_url_project( $project, '-delete'), __( 'Delete Project', 'glotpress' ), array( 'ays-text' => __( 'Do you really want to delete this project?', 'glotpress' ) ) )
	);

	$actions = apply_filters( 'gp_project_actions', $actions, $project );

	echo '<ul>';

	foreach( $actions as $action ) {
		echo '<li>' . $action . '</li>';
	}

	if ( $translation_sets ) {
		echo '<li>' . gp_project_options_form( $project ) . '</li>';
	}

	echo '</ul>';
}

function gp_project_options_form( $project ) {
	return '
			<a href="#" class="personal-options" id="personal-options-toggle"> ' . __( 'Personal project options &darr;', 'glotpress' ) . '</a>
			<div class="personal-options">
				<form action="' . gp_url_project( $project, '-personal' ) . '" method="post">
				<dl>
					<dt><label for="source-url-template">' . __( 'Source file URL', 'glotpress' ) . '</label></dt>
					<dd>
						<input type="text" value="' . esc_html( $project->source_url_template() ) . '" name="source-url-template" id="source-url-template" />
						<small>' . __( 'URL to a source file in the project. You can use <code>%file%</code> and <code>%line%</code>. Ex. <code>https://trac.example.org/browser/%file%#L%line%</code>', 'glotpress' ) .'</small>
					</dd>
				</dl>
				<p>
					<input type="submit" name="submit" value="' . esc_attr__( 'Save &rarr;', 'glotpress' ) . '" id="save" />
					<a class="ternary" href="#" onclick="jQuery(\'#personal-options-toggle\').click();return false;">' . __( 'Cancel', 'glotpress' ) . '</a>
				</p>
				</form>
			</div>';
}

function gp_entry_actions( $seperator = ' &bull; ' ) {
	$actions = array(
		'<a href="#" class="copy" tabindex="-1">' . __( 'Copy from original', 'glotpress' ) . '</a>'
	);

	$actions = apply_filters( 'gp_entry_actions', $actions );

	echo implode( $seperator, $actions );
	/*
	<a href="#" class="copy" tabindex="-1"><?php _e( 'Copy from original', 'glotpress' ); ?></a> &bull;
	<a href="#" class="gtranslate" tabindex="-1"><?php _e( 'Translation from Google', 'glotpress' ); ?></a>
	*/
}

function gp_sort_options() {
	$default = array( 'by' => 'gp_sort_by_options', 'order' => 'gp_sort_order_options' );
	
	$default = apply_filters( 'gp_sort_options', $default );
	
	return $default;
}

function gp_default_sort_options() {
	$default = array(
		'by'  => 'priority',
		'how' => 'desc'
	);

	$default = apply_filters( 'gp_default_sort_options', $default );
	
	return $default;
}

function gp_sort_by_options( $value = null ) {
	$default = array(
		'original_date_added' 	 => __( 'Date added (original)', 'glotpress' ),
		'translation_date_added' => __( 'Date added (translation)', 'glotpress' ),
		'original'				 => __( 'Original string', 'glotpress' ),
		'translation' 			 => __( 'Translation', 'glotpress' ),
		'priority' 				 => __( 'Priority', 'glotpress' ),
		'references' 			 => __( 'Filename in source', 'glotpress' ),
		'random' 				 => __( 'Random', 'glotpress' ),
	);
	
	$default = apply_filters( 'gp_sort_by_options', $default );
	
	return $default;
}

function gp_sort_order_options( $value = null ) {
	$default = array(
		'asc'  => __( 'Ascending', 'glotpress' ),
		'desc' => __( 'Descending', 'glotpress' ),
	);
	
	$default = apply_filters( 'gp_sort_order_options', $default );
	
	return $default;
}

function gp_validate_sort_options( $options, $default = null ) {
	if ( null == $default ) {
		$default = gp_default_sort_options();	
	}
	
	if ( ! is_array( $options ) ) { 
		return $default;
	}
	
	$options_list = gp_sort_options();
	
	foreach ( $options_list as $key => $option_function ) {
		if ( array_key_exists( $key, $options ) ) {
			$option_values = call_user_func( $option_function, $options[ $key ] );
			
			if ( array_key_exists( $options[ $key ], $option_values ) ) {
				$validated[ $key ] = $option_values[ $options[ $key ] ];
			} else {
				$validated[ $key ] = $default[ $key ];
			}
		}
	}
	
	$validated = apply_filters( 'gp_validate_sort_options', $validated, $options, $default );
	
	return $validated;
}

function gp_default_per_page() {
	$default = 15;
	
	$default = apply_filters( 'gp_default_per_page', $default );
	
	return $default;
}

function gp_filter_options() {
	$default = array( 
		'term' 	  	 => 'gp_filter_term_options', 
		'user_login' => 'gp_filter_user_options', 
		'status'  	 => 'gp_filter_status_options', 
		'context' 	 => 'gp_filter_context_options', 
		'comment' 	 => 'gp_filter_comment_options' 
	);
	
	$default = apply_filters( 'gp_filter_options', $default );
	
	return $default;
}

function gp_default_filter_options() {
	$default = array(
		'term' 	  	 => '', 
		'user_login' => '', 
		'status'  	 => 'current_or_waiting_or_fuzzy_or_untranslated', 
		'context' 	 => '', 
		'comment' 	 => '' 
	);

	$default = apply_filters( 'gp_default_filter_options', $default );
	
	return $default;
}

function gp_validate_filter_options( $options, $default = null ) {
	if ( null == $default ) {
		$default = gp_default_filter_options();	
	}
	
	if ( ! is_array( $options ) ) { 
		return $default;
	}
	
	$options_list = gp_filter_options();

	foreach ( $options_list as $key => $option_function ) {
		if ( array_key_exists( $key, $options ) ) {
			$option_values = call_user_func( $option_function, $options[ $key ] );
			
			if ( array_key_exists( $options[ $key ], $option_values ) ) {
				$validated[ $key ] = $option_values[ $options[ $key ] ];
			} else {
				$validated[ $key ] = $default[ $key ];
			}
		}
	}

	$validated = apply_filters( 'gp_validate_sort_options', $validated, $options, $default );
	
	return $validated;
}

function gp_filter_term_options( $value = null ) {
	$default = array( 'term' => $value );
	
	$default = apply_filters( 'gp_filter_term_options', $default );
	
	return $default;
}

function gp_filter_user_options( $value = null ) {
	$default = array( 'user_login' => $value );
	
	if ( false === get_user_by( 'id', $value ) ) {
		$default[ 'user_login' ] = '';
		gp_notice_set( __( 'Filter Error: Invalid user ID supplied!', 'glotpress' ), 'error' );
	}
	
	$default = apply_filters( 'gp_filter_user_options', $default );
	
	return $default;
}

function gp_filter_status_options( $value = null ) {
	$default = array(
		'current_or_waiting_or_fuzzy_or_untranslated' => __( 'Current/waiting/fuzzy + untranslated (All)', 'glotpress' ),
		'current' 									  => __( 'Current only', 'glotpress' ),
		'old' 										  => __( 'Approved, but obsoleted by another string', 'glotpress' ),
		'waiting' 									  => __( 'Waiting approval', 'glotpress' ),
		'rejected' 									  => __( 'Rejected', 'glotpress' ),
		'untranslated' 								  => __( 'Without current translation', 'glotpress' ),
		'either' 									  => __( 'Any', 'glotpress' ),
	);

	$default = apply_filters( 'gp_filter_status_options', $default );
	
	return $default;
}

function gp_filter_context_options( $value = null ) {
	$default = array( 'context' => $value );
	
	$default = apply_filters( 'gp_filter_context_options', $default );
	
	return $default;
}

function gp_filter_comment_options( $value = null ) {
	$default = array( 'comment' => $value );
	
	$default = apply_filters( 'gp_filter_comment_options', $default );
	
	return $default;
}
