<?php
function gp_tmpl_load( $template, $args = array(), $template_path = null ) {
	$args = gp_tmpl_filter_args( $args );

	/**
	 * Fires before a template is loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The template name.
	 * @param array  $args     Arguments passed to the template. Passed by reference.
	 */
	do_action_ref_array( 'gp_pre_tmpl_load', array( $template, &$args ) );
	require_once GP_TMPL_PATH . 'helper-functions.php';
	$locations = array( GP_TMPL_PATH );
	if ( !is_null( $template_path ) ) {
		array_unshift( $locations, untrailingslashit( $template_path ) . '/' );
	}

	/**
	 * Filter the locations to load template files from.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $locations     File paths of template locations.
	 * @param string      $template      The template name.
	 * @param array       $args          Arguments passed to the template.
	 * @param string|null $template_path Priority template location, if any.
	 */
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

	/**
	 * Fires after a template was loaded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The template name.
	 * @param array  $args     Arguments passed to the template. Passed by reference.
	 */
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
	/**
	 * Fires inside the head element on the header template.
	 *
	 * @since 1.0.0
	 */
	do_action( 'gp_head' );
}

function gp_footer() {
	/**
	 * Fires at the end of the page, on the footer template.
	 *
	 * @since 1.0.0
	 */
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
			$user = wp_get_current_user();
			$items[ gp_url_profile( $user->user_nicename ) ] = __( 'Profile', 'glotpress' );
			$items[ gp_url( '/settings' ) ] = __( 'Settings', 'glotpress' );
			$items[ esc_url( wp_logout_url( gp_url_current() ) ) ]  = __( 'Log out', 'glotpress' );
		}
		else {
			$items[ esc_url( wp_login_url( gp_url_current() ) ) ] = __( 'Log in', 'glotpress' );
		}
	}

	/**
	 * Filter the list of navigation menu items.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $items    Menu items. URL as the key, menu label as the value.
	 * @param string $location Location of the menu.
	 */
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

		/**
		 * Filter the title of a page.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title The title of a page.
		 */
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

		/**
		 * Filter the list of breadcrumb navigation items.
		 *
		 * @since 1.0.0
		 *
		 * @param array $breadcrumb_items Breadcrumb items as HTML string.
		 */
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

			/**
			 * Filter the breadcrumb HTML output.
			 *
			 * @since 1.0.0
			 *
			 * @param string $whole_breadcrumb Breadcrumb HTML.
			 */
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
	if ( 0 === $leaf_project->id ) {
		return array();
	}
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
	return '<script type="text/javascript">document.getElementById(\'' . esc_js( $html_id ) . '\').focus();</script>';
}

function gp_select( $name_and_id, $options, $selected_key, $attrs = array() ) {
	$attributes = gp_html_attributes( $attrs );
	$attributes = $attributes ? " $attributes" : '';
	$res = "<select name='" . esc_attr( $name_and_id ) . "' id='" . esc_attr( $name_and_id ) . "' $attributes>\n";
	$labels = [
		'hidden' => _x( 'hidden', 'Priority', 'glotpress' ),
		'low'    => _x( 'low', 'Priority', 'glotpress' ),
		'normal' => _x( 'normal', 'Priority', 'glotpress' ),
		'high'   => _x( 'high', 'Priority', 'glotpress' ),
	];
	foreach( $options as $value => $label ) {
		if ( isset( $labels[ $label ] ) ) {
			$label = $labels[ $label ];
		}
		$selected = selected( $value, $selected_key, false );
		$res .= "\t<option value='" . esc_attr( $value ) . "'$selected>" . esc_html( $label ) . "</option>\n";
	}
	$res .= "</select>\n";
	return $res;
}

function gp_radio_buttons( $name, $radio_buttons, $checked_key ) {
	$res = '';
	foreach( $radio_buttons as $value => $label ) {
		$checked = checked( $value, $checked_key, false );
		// TODO: something more flexible than <br />
		$res .= "\t<input type='radio' id='" . esc_attr( "{$name}[{$value}]" ) . "' name='" . esc_attr( $name ) . "' value='" . esc_attr( $value ) . "'$checked/>&nbsp;";
		$res .= "<label for='" . esc_attr( "{$name}[{$value}]" ) . "'>" . esc_html( $label ) . "</label><br />\n";
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

	/**
	 * Filter the pagination HTML output.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The pagination HTML.
	 * @param int    $page     Current page number.
	 * @param int    $per_page Objects per page.
	 * @param int    $objects  Total number of objects to page.
	 */
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
 *
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
 *
 * @return string HTML markup for a select element.
 */
function gp_locales_dropdown( $name_and_id, $selected_slug = null, $attrs = array() ) {
	return gp_locales_by_project_dropdown( null, $name_and_id, $selected_slug, $attrs );
}

/**
 * Returns HTML markup for a select element for projects.
 *
 * @since 1.0.0
 *
 * @param string $name_and_id         Name and ID of the select element.
 * @param string $selected_project_id The project id to mark as the currently selected.
 * @param array  $attrs               Extra attributes.
 * @param array  $exclude             An array of locales to exclude from the list.
 * @param array  $exclude_no_parent   Exclude the "No Parent" option from the list of locales.
 *
 * @return string HTML markup for a select element.
 */
function gp_projects_dropdown( $name_and_id, $selected_project_id = null, $attrs = array(), $exclude = array(), $exclude_no_parent = false ) {
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

	if ( ! $exclude_no_parent ) {
		$options = array( '' => __( '&mdash; No parent &mdash;', 'glotpress' ) );
	} else {
		$options = array();
	}

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

/**
 * Returns HTML markup for a select element for plural types.
 *
 * @since 2.4.0
 *
 * @param string $name_and_id          Name and ID of the select element.
 * @param string $selected_plural_type The plural type to mark as the currently selected.
 * @param array  $attrs                Extra attributes.
 *
 * @return string HTML markup for a select element.
 */
function gp_plural_types_dropdown( $name_and_id, $selected_plural_type = null, $attrs = array() ) {
	$options = array(
		'gettext' => __( 'GetText', 'glotpress' ),
		'cldr' => __( 'CLDR', 'glotpress' ),
	);

	return gp_select( $name_and_id, $options, $selected_plural_type, $attrs );
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
		gp_link_get( gp_url_project( $project, 'import-originals' ), __( 'Import Originals', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, array( '-permissions' ) ), __( 'Permissions', 'glotpress') ),
		gp_link_get( gp_url_project( '', '-new', array('parent_project_id' => $project->id) ), __( 'New Sub-Project', 'glotpress' ) ),
		gp_link_get( gp_url( '/sets/-new', array( 'project_id' => $project->id ) ), __( 'New Translation Set', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, array( '-mass-create-sets' ) ), __( 'Mass-create Translation Sets', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, '-branch' ), __( 'Branch project', 'glotpress' ) ),
		gp_link_get( gp_url_project( $project, '-delete' ), __( 'Delete project', 'glotpress' ) ),
	);

	/**
	 * Project action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $actions Links as HTML strings.
	 * @param GP_Project $project The project.
	 */
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
			<a href="#" class="personal-options" id="personal-options-toggle"> ' . __( 'Personal project options', 'glotpress' ) . ' &darr;</a>
			<div class="personal-options">
				<form action="' . gp_url_project( $project, '-personal' ) . '" method="post">
				<dl>
					<dt><label for="source-url-template">' . __( 'Source file URL', 'glotpress' ) . '</label></dt>
					<dd>
						<input type="text" value="' . esc_html( $project->source_url_template() ) . '" name="source-url-template" id="source-url-template" />
						<small>' . sprintf(
							/* translators: 1: %file%, 2: %line%, 3: https://trac.example.org/browser/%file%#L%line% */
							__( 'URL to a source file in the project. You can use %1$s and %2$s. Ex. %3$s', 'glotpress' ),
							'<code>%file%</code>',
							'<code>%line%</code>',
							'<code>https://trac.example.org/browser/%file%#L%line%</code>'
						) . '</small>
					</dd>
				</dl>
				<p>
					<input type="submit" name="submit" value="' . esc_attr__( 'Save &rarr;', 'glotpress' ) . '" id="save" />
					<a class="ternary" href="#" onclick="jQuery(\'#personal-options-toggle\').click();return false;">' . __( 'Cancel', 'glotpress' ) . '</a>
				</p>
				' . gp_route_nonce_field( 'set-personal-options_' . $project->id, false ) . '
				</form>
			</div>';
}

function gp_entry_actions( $seperator = ' &bull; ' ) {
	$actions = array(
		'<button class="copy" tabindex="-1" title="' . __( 'Copy the original string to the translation area (overwrites existing text).', 'glotpress' ) . '">' . __( 'Copy from original', 'glotpress' ) . '</button> ' .
		'<button class="inserttab" tabindex="-1" title="' . __( 'Insert tab (\t) at the current cursor position.', 'glotpress' ) . '">' . __( 'Insert tab', 'glotpress' ) . '</button> ' .
		'<button class="insertnl" tabindex="-1" title="' . __( 'Insert newline (\n) at the current cursor position.', 'glotpress' ) . '">' . __( 'Insert newline', 'glotpress' ) . '</button>',
	);

	/**
	 * Filters entry action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array $actions Links as HTML strings.
	 */
	$actions = apply_filters( 'gp_entry_actions', $actions );


	echo implode( $seperator, $actions );
	/*
	<a href="#" class="copy" tabindex="-1"><?php _e( 'Copy from original', 'glotpress' ); ?></a> &bull;
	<a href="#" class="gtranslate" tabindex="-1"><?php _e( 'Translation from Google', 'glotpress' ); ?></a>
	*/
}

/**
 * Generates a list of classes to be added to the translation row, based on translation entry properties.
 *
 * @since 2.2.0
 *
 * @param Translation_Entry $translation The translation entry object for the row.
 *
 * @return array
 */
function gp_get_translation_row_classes( $translation ) {
	$classes = array();
	$classes[] = $translation->translation_status ? 'status-' . $translation->translation_status : 'untranslated';
	$classes[] = 'priority-' . gp_array_get( GP::$original->get_static( 'priorities' ), $translation->priority );
	$classes[] = $translation->warnings ? 'has-warnings' : 'no-warnings';

	/**
	 * Filters the list of CSS classes for a translation row
	 *
	 * @since 2.2.0
	 *
	 * @param array             $classes     An array of translation row classes.
	 * @param Translation_Entry $translation The translation entry object.
	 */
	$classes = apply_filters( 'gp_translation_row_classes', $classes, $translation );

	return $classes;
}

/**
 * Outputs space separated list of classes for the translation row, based on translation entry properties.
 *
 * @since 2.2.0
 *
 * @param Translation_Entry $translation The translation entry object for the row.
 *
 * @return void
 */
function gp_translation_row_classes( $translation ) {
	$classes = gp_get_translation_row_classes( $translation );
	echo esc_attr( implode( ' ', $classes ) );
}
