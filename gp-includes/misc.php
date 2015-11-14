<?php

/**
 * Retrieves a value from $_POST
 *
 * @param string $key name of post value
 * @param mixed $default value to return if $_POST[$key] doesn't exist. Default is ''
 * @return mixed $_POST[$key] if exists or $default
 */
function gp_post( $key, $default = '' ) {
	return gp_array_get( $_POST, $key, $default );
}

/**
 * Retrieves a value from $_GET
 *
 * @param string $key name of get value
 * @param mixed $default value to return if $_GET[$key] doesn't exist. Default is ''
 * @return mixed $_GET[$key] if exists or $default
 */
function gp_get( $key, $default = '' ) {
	return gp_urldecode_deep( gp_array_get( $_GET, $key, $default ) );
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
 * @param string $message The message to be passed
 * @param string $key Optional. Key for the message. You can pass several messages under different keys.
 * A key has one message. The default is 'notice'.
 */
function gp_notice_set( $message, $key = 'notice' ) {
	gp_set_cookie( '_gp_notice_'.$key, $message, 0, gp_url_path() );
}

/**
 * Retrieves a notice message, set by {@link gp_notice()}
 *
 * @param string $key Optional. Message key. The default is 'notice'
 */
function gp_notice( $key = 'notice' ) {
	return gp_array_get( GP::$redirect_notices, $key );
}

function gp_populate_notices() {
	GP::$redirect_notices = array();
	$prefix = '_gp_notice_';
	foreach ($_COOKIE as $key => $value ) {
		if ( gp_startswith( $key, $prefix ) && $suffix = substr( $key, strlen( $prefix ) )) {
			GP::$redirect_notices[$suffix] = $value;
			gp_set_cookie( $key, '', 0, gp_url_path() );
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
			$all_have_more = ( list( $key, $value ) = each( $array ) );
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

function gp_array_any( $callback, $array ) {
	foreach( $array as $item ) {
		if ( $callback( $item ) ) {
			return true;
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
		$timestamp = gp_gmt_strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );

	// If nothing to compare against, then always assume there's an update available
	if ( ! $timestamp )
		return true;

	return gp_gmt_strtotime( GP::$translation->last_modified( $translation_set ) ) > $timestamp;
}


/**
 * Delete translation set counts cache
 *
 * @param int $id translation set ID
 */
function gp_clean_translation_set_cache( $id ) {
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
	return is_null( $value );
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
	$args = apply_filters( 'gp_set_cookie', $args );
	if ( $args === false ) return;
	call_user_func_array( 'setcookie', $args );
}

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

function gp_wp_profile( $user ) {
	// If the user cannot edit their profile, then don't show the settings.
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
?>
	<h3 id="gp-profile"><?php _e('GlotPress Profile'); ?></h3>
<?php		
	
	include( GP_PATH . './gp-templates/profile-view.php' );
}

function gp_wp_profile_update( $user_id ) {
	// If the user cannot edit their profile, then don't save the settings
	if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
	
	$gp_route_profile = new GP_Route_Profile;
	
	$gp_route_profile->profile_post( $user_id );
	
	return true;
}

