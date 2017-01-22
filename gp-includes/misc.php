<?php

/**
 * Retrieves a value from $_POST
 *
 * @param string $key name of post value
 * @param mixed $default value to return if $_POST[$key] doesn't exist. Default is ''
 * @return mixed $_POST[$key] if exists or $default
 */
function gp_post( $key, $default = '' ) {
	return wp_unslash( gp_array_get( $_POST, $key, $default ) );
}

/**
 * Retrieves a value from $_GET
 *
 * @param string $key name of get value
 * @param mixed $default value to return if $_GET[$key] doesn't exist. Default is ''
 * @return mixed $_GET[$key] if exists or $default
 */
function gp_get( $key, $default = '' ) {
	return gp_urldecode_deep( wp_unslash( gp_array_get( $_GET, $key, $default ) ) );
}

/**
 * Prints a nonce hidden field for route actions.
 *
 * @since 2.0.0
 *
 * @see wp_nonce_field()
 *
 * @param int|string $action Action name.
 * @param bool       $echo   Optional. Whether to display or return hidden form field. Default true.
 * @return string Nonce field HTML markup.
 */
function gp_route_nonce_field( $action, $echo = true ) {
	return wp_nonce_field( $action, '_gp_route_nonce', true, $echo );
}

/**
 * Retrieves a URL with a nonce added to URL query for route actions.
 *
 * @since 2.0.0
 *
 * @see wp_nonce_url()
 *
 * @param string     $url    URL to add nonce action.
 * @param int|string $action Action name.
 * @return string Escaped URL with nonce action added.
 */
function gp_route_nonce_url( $url, $action ) {
	return wp_nonce_url( $url, $action, $name = '_gp_route_nonce' );
}

/**
 * Retrieves a value from $array
 *
 * @param array $array
 * @param string $key name of array value
 * @param mixed $default value to return if $array[$key] doesn't exist. Default is ''
 * @return mixed $array[$key] if exists or $default
 */
function gp_array_get( $array, $key, $default = '' ) {
	return isset( $array[$key] )? $array[$key] : $default;
}

function gp_const_get( $name, $default = '' ) {
	return defined( $name )? constant( $name ) : $default;
}

function gp_const_set( $name, $value ) {
	if ( defined( $name) ) {
		return false;
	}
	define( $name, $value );
	return true;
}


function gp_member_get( $object, $key, $default = '' ) {
	return isset( $object->$key )? $object->$key : $default;
}

/**
 * Makes from an array of arrays a flat array.
 *
 * @param array $array the arra to flatten
 * @return array flattenned array
 */
function gp_array_flatten( $array ) {
	$res = array();
	foreach( $array as $value ) {
		$res = array_merge( $res, is_array( $value )? gp_array_flatten( $value ) : array( $value ) );
	}
	return $res;
}

/**
 * Passes the message set through the next redirect.
 *
 * Works best for edit requests, which want to pass error message or notice back to the listing page.
 *
 * @param string $message The message to be passed.
 * @param string $key     Optional. Key for the message. You can pass several messages under different keys.
 *                        A key has one message. The default is 'notice'.
 */
function gp_notice_set( $message, $key = 'notice' ) {
	$cookie_path = '/' . ltrim( gp_url_path(), '/' ); // Make sure that the cookie path is never empty.
	gp_set_cookie( '_gp_notice_' . $key, $message, 0, $cookie_path );
}

/**
 * Retrieves a notice message, set by {@link gp_notice()}
 *
 * @param string $key Optional. Message key. The default is 'notice'
 */
function gp_notice( $key = 'notice' ) {
	// Sanitize fields
	$allowed_tags = array(
		'a'       => array( 'href' => true ),
		'abbr'    => array(),
		'acronym' => array(),
		'b'       => array(),
		'br'      => array(),
		'button'  => array( 'disabled' => true, 'name' => true, 'type' => true, 'value' => true ),
		'em'      => array(),
		'i'       => array(),
		'img'     => array( 'src' => true, 'width' => true, 'height' => true ),
		'p'       => array(),
		'pre'     => array(),
		's'       => array(),
		'strike'  => array(),
		'strong'  => array(),
		'sub'     => array(),
		'sup'     => array(),
		'u'       => array(),
	);

	// Adds class, id, style, title, role attributes to all of the above allowed tags.
	$allowed_tags = array_map( '_wp_add_global_attributes', $allowed_tags );

	return wp_kses( gp_array_get( GP::$redirect_notices, $key ), $allowed_tags );
}

function gp_populate_notices() {
	GP::$redirect_notices = array();
	$prefix = '_gp_notice_';
	$cookie_path = '/' . ltrim( gp_url_path(), '/' ); // Make sure that the cookie path is never empty.
	foreach ($_COOKIE as $key => $value ) {
		if ( gp_startswith( $key, $prefix ) && $suffix = substr( $key, strlen( $prefix ) )) {
			GP::$redirect_notices[$suffix] = wp_unslash( $value );
			gp_set_cookie( $key, '', 0, $cookie_path );
		}
	}
}

/**
 * Returns an array of arrays, where the i-th array contains the i-th element from
 * each of the argument arrays. The returned array is truncated in length to the length
 * of the shortest argument array.
 *
 * The function works only with numerical arrays.
 */
function gp_array_zip() {
	$args = func_get_args();
	if ( !is_array( $args ) ) {
		return false;
	}
	if ( empty( $args ) ) {
		return array();
	}
	$res = array();
	foreach ( $args as &$array ) {
		if ( !is_array( $array) ) {
			return false;
		}
		reset( $array );
	}
	$all_have_more = true;
	while (true) {
		$this_round = array();
		foreach ( $args as &$array ) {
			$all_have_more = ( list( , $value ) = each( $array ) );
			if ( !$all_have_more ) {
				break;
			}
			$this_round[] = $value;
		}
		if ( $all_have_more ) {
			$res[] = $this_round;
		} else {
			break;
		}
	}
	return $res;
}

function gp_array_any( $callback, $array, $arg = null ) {
	foreach( $array as $item ) {
		if( is_array( $callback ) ) {
			if (  $callback[0]->{$callback[1]}( $item, $arg ) ) {
				return true;
			}
		} else {
			if ( $callback( $item, $arg ) ) {
				return true;
			}
		}
	}
	return false;
}

function gp_array_all( $callback, $array ) {
	foreach( $array as $item ) {
		if ( !$callback( $item ) ) {
			return false;
		}
	}
	return true;
}

function gp_error_log_dump( $value ) {
	if ( is_array( $value ) || is_object( $value ) ) {
		$value = print_r( $value, true );
	}
	error_log( $value );
}

function gp_object_has_var( $object, $var_name ) {
	return in_array( $var_name, array_keys( get_object_vars( $object ) ) );
}

/**
 * Has this translation been updated since the passed timestamp?
 *
 * @param GP_Translation_Set $translation_set Translation to check
 * @param int $timestamp Optional; unix timestamp to compare against. Defaults to HTTP_IF_MODIFIED_SINCE if set.
 * @return bool
 */
function gp_has_translation_been_updated( $translation_set, $timestamp = 0 ) {

	// If $timestamp isn't set, try to default to the HTTP_IF_MODIFIED_SINCE header.
	if ( ! $timestamp && isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
		$timestamp = gp_gmt_strtotime( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) );

	// If nothing to compare against, then always assume there's an update available
	if ( ! $timestamp )
		return true;

	return gp_gmt_strtotime( GP::$translation->last_modified( $translation_set ) ) > $timestamp;
}


/**
 * Delete translation set counts cache.
 *
 * @global bool $_wp_suspend_cache_invalidation
 *
 * @param int $id Translation set ID.
 */
function gp_clean_translation_set_cache( $id ) {
	global $_wp_suspend_cache_invalidation;

	if ( ! empty( $_wp_suspend_cache_invalidation ) ) {
		return;
	}

	wp_cache_delete( $id, 'translation_set_status_breakdown' );
	wp_cache_delete( $id, 'translation_set_last_modified' );
}

/**
 * Delete counts cache for all translation sets of a project
 *
 * @param int $project_id project ID
 */
function gp_clean_translation_sets_cache( $project_id ) {
	$translation_sets = GP::$translation_set->by_project_id( $project_id );

	if ( ! $translation_sets )
		return;

	foreach ( $translation_sets as $set ) {
		gp_clean_translation_set_cache( $set->id );
	}
}


/**
 * Checks if the passed value is empty.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_empty( $value ) {
	return empty( $value );
}

/**
 * Checks if the passed value is an empty string.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_empty_string( $value ) {
	return '' === $value;
}

/**
 * Checks if the passed value isn't an empty string.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_not_empty_string( $value ) {
	return '' !== $value;
}

/**
 * Checks if the passed value is a positive integer.
 *
 * @param int $value The value you want to check.
 * @return bool
 */
function gp_is_positive_int( $value ) {
	return (int) $value > 0;
}

/**
 * Checks if the passed value is an integer.
 *
 * @param int|string $value The value you want to check.
 * @return bool
 */
function gp_is_int( $value ) {
	return (bool) preg_match( '/^-?\d+$/', $value );
}

/**
 * Checks if the passed value is null.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_null( $value ) {
	return null === $value;
}

/**
 * Checks if the passed value is not null.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_not_null( $value ) {
	return null !== $value;
}

/**
 * Checks if the passed value is between the start and end value or is the same.
 *
 * @param string $value The value you want to check.
 * @param string $value The lower value you want to check against.
 * @param string $value The upper value you want to check against.
 * @return bool
 */
function gp_is_between( $value, $start, $end ) {
	return $value >= $start && $value <= $end;
}

/**
 * Checks if the passed value is between the start and end value.
 *
 * @param string $value The value you want to check.
 * @return bool
 */
function gp_is_between_exclusive( $value, $start, $end ) {
	return $value > $start && $value < $end;
}


/**
 * Acts the same as core PHP setcookie() but its arguments are run through the gp_set_cookie filter.
 *
 * If the filter returns false, setcookie() isn't called.
 */
function gp_set_cookie() {
	$args = func_get_args();

	/**
	 * Filter whether GlotPress should set a cookie.
	 *
	 * If the filter returns false, a cookie will not be set.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     The cookie that is about to be set.
	 *
	 *     @type string $name    The name of the cookie.
	 *     @type string $value   The value of the cookie.
	 *     @type int    $expires The time the cookie expires.
	 *     @type string $path    The path on the server in which the cookie will be available on.
	 * }
	 */
	$args = apply_filters( 'gp_set_cookie', $args );
	if ( $args === false ) return;
	call_user_func_array( 'setcookie', $args );
}

/**
 * Converts a string represented time/date to a utime int, adding a GMT offset if not found.
 *
 * @since 1.0.0
 *
 * @param string $string The string representation of the time to convert.
 * @return int
 */
function gp_gmt_strtotime( $string ) {
	if ( is_numeric($string) )
		return $string;
	if ( !is_string($string) )
		return -1;

	if ( stristr($string, 'utc') || stristr($string, 'gmt') || stristr($string, '+0000') )
		return strtotime($string);

	if ( -1 == $time = strtotime($string . ' +0000') )
		return strtotime($string);

	return $time;
}

/**
 * Determines the format to use based on the selected format type or by auto detection based on the file name.
 *
 * Used during import of translations and originals.
 *
 * @param string $selected_format The format that the user selected on the import page.
 * @param string $filename The filname that was uploaded by the user.
 * @return object|null A GP_Format child object or null if not found.
 */
function gp_get_import_file_format( $selected_format, $filename ) {
	$format = gp_array_get( GP::$formats, $selected_format, null );

	if ( ! $format ) {
		$matched_ext_len = 0;

		foreach( GP::$formats as $format_entry ) {
			$format_extensions = $format_entry->get_file_extensions();

			foreach( $format_extensions as $extension ) {
				$current_ext_len = strlen( $extension );

				if ( gp_endswith( $filename, $extension ) && $current_ext_len > $matched_ext_len ) {
					$format = $format_entry;
					$matched_ext_len = $current_ext_len;
				}
			}
		}
	}

	return $format;
}

/**
 * Displays the GlotPress administrator option in the user profile screen for WordPress administrators.
 *
 * @since 2.0.0
 *
 * @param WP_User $user The WP_User object to display the profile for.
 */
function gp_wp_profile_options( $user ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

?>
	<h2 id="glotpress"><?php _e( 'GlotPress', 'glotpress' ); ?></h2>

	<table class="form-table">
		<tr id="gp-admin">
			<th scope="row"><?php _e( 'Administrator', 'glotpress' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php _e( 'GlotPress Administrator', 'glotpress' ); ?></span></legend>
					<label for="gp_administrator">
						<input name="gp_administrator" type="checkbox" id="gp_administrator" value="1"<?php checked( GP::$permission->user_can( $user, 'admin' ) ); ?> />
						<?php _e( 'Grant this user administrative privileges in GlotPress.', 'glotpress' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
	</table>
<?php
}

/**
 * Saves the settings for the GlotPress administrator option in the user profile screen for WordPress administrators.
 *
 * @since 2.0.0
 *
 * @param int $user_id The WordPress user id to save the setting for.
 */
function gp_wp_profile_options_update( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$is_user_gp_admin = GP::$permission->user_can( $user_id, 'admin' );

	if ( array_key_exists( 'gp_administrator', $_POST ) && ! $is_user_gp_admin ) {
		GP::$administrator_permission->create( array( 'user_id' => $user_id, 'action' => 'admin', 'object_type' => null ) );
	}

	if ( ! array_key_exists( 'gp_administrator', $_POST ) && $is_user_gp_admin ) {
		$current_perm = GP::$administrator_permission->find_one( array( 'user_id' => $user_id, 'action' => 'admin' ) );
		$current_perm->delete();
	}
}

/**
 * Returns a multi-dimensional array with the sort by types, descriptions and SQL query for each.
 *
 * @since 2.1.0
 *
 * @return array An array of sort by field types.
 */
function gp_get_sort_by_fields() {
	$sort_fields = array(
		'original_date_added' => array(
			'title'       => __( 'Date added (original)', 'glotpress' ),
			'sql_sort_by' => 'o.date_added %s',
		),
		'translation_date_added' => array(
			'title'       => __( 'Date added (translation)', 'glotpress' ),
			'sql_sort_by' => 't.date_added %s',
		),
		'original' => array(
			'title'       => __( 'Original string', 'glotpress' ),
			'sql_sort_by' => 'o.singular %s',
		),
		'translation' => array(
			'title'       => __( 'Translation', 'glotpress' ),
			'sql_sort_by' => 't.translation_0 %s',
		),
		'priority' => array(
			'title'       => __( 'Priority', 'glotpress' ),
			'sql_sort_by' => 'o.priority %s, o.date_added DESC',
		),
		'references' => array(
			'title'       => __( 'Filename in source', 'glotpress' ),
			'sql_sort_by' => 'o.references',
		),
		'random' => array(
			'title'       => __( 'Random', 'glotpress' ),
			'sql_sort_by' => 'o.priority DESC, RAND()',
		),
	);

	/**
	 * Filter the sort by list to allow plugins to add or remove sort by types.
	 *
	 * Plugins can add, remove or resort the sort by types array which is used to create
	 * the sort by drop down in the translations page.
	 *
	 * @since 2.1.0
	 *
	 * @param array $sort_fields {
	 *     A list of sort by types.
	 *
	 *     @type array $sort_type An array with two keys, 'title' is a translated description of the key and 'sql_sort_by' which is a partial SQL SORT BY clause to use.
	 * }
	 */
	return apply_filters( 'gp_sort_by_fields', $sort_fields );
}
