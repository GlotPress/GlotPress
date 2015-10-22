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
	return gp_array_get( $_GET, $key, $default );
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
	backpress_set_cookie( '_gp_notice_'.$key, $message, 0, gp_url_path() );
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
			backpress_set_cookie( $key, '', 0, gp_url_path() );
		}
	}
}

/**
 * Sets headers, which redirect to another page.
 *
 * @param string $location The path to redirect to
 * @param int $status Status code to use
 * @return bool False if $location is not set
 */
function gp_redirect( $location, $status = 302 ) {
	// TODO: add server-guessing code from bb-load.php in a function in gp-includes/system.php
    global $is_IIS;

    $location = apply_filters( 'gp_redirect', $location, $status );
    $status = apply_filters( 'gp_redirect_status', $status, $location );

    if ( !$location ) // allows the gp_redirect filter to cancel a redirect
        return false;

    if ( $is_IIS ) {
        header( "Refresh: 0;url=$location" );
    } else {
        if ( php_sapi_name() != 'cgi-fcgi' )
            status_header( $status ); // This causes problems on IIS and some FastCGI setups
        header( "Location: $location" );
    }
}

/**
 * Builds SQL LIMIT/OFFSET clause for the given page
 *
 * @param integer $page The page number. The first page is 1.
 * @param integer $per_page How many items are there in a page
 */
function gp_limit_for_page( $page, $per_page ) {
	$page = $page? $page - 1 : 0;
	return sprintf( 'LIMIT %d OFFSET %d', $per_page, $per_page * $page );
}


function _gp_get_secret_key( $key, $default_key = false ) {
	if ( !$default_key ) {
		global $gp_default_secret_key;
		$default_key = $gp_default_secret_key;
	}

	if ( defined( $key ) && '' != constant( $key ) && $default_key != constant( $key ) ) {
		return constant( $key );
	}

	return $default_key;
}

function _gp_get_salt( $constants, $option = false ) {
	if ( !is_array( $constants ) ) {
		$constants = array( $constants );
	}

	foreach ($constants as $constant ) {
		if ( defined( $constant ) ) {
			return constant( $constant );
		}
	}

	if ( !$option ) {
		$option = strtolower( $constants[0] );
	}
	$salt = gp_get_option( $option );
	if ( empty( $salt ) ) {
		$salt = gp_generate_password();
		gp_update_option( $option, $salt );
	}
	return $salt;
}

if ( !function_exists( 'gp_salt' ) ) :
function gp_salt($scheme = 'auth') {
	$secret_key = _gp_get_secret_key( 'GP_SECRET_KEY' );

	switch ($scheme) {
		case 'auth':
			$secret_key = _gp_get_secret_key( 'GP_AUTH_KEY', $secret_key );
			$salt = _gp_get_salt( array( 'GP_AUTH_SALT', 'GP_SECRET_SALT' ) );
			break;

		case 'secure_auth':
			$secret_key = _gp_get_secret_key( 'GP_SECURE_AUTH_KEY', $secret_key );
			$salt = _gp_get_salt( 'GP_SECURE_AUTH_SALT' );
			break;

		case 'logged_in':
			$secret_key = _gp_get_secret_key( 'GP_LOGGED_IN_KEY', $secret_key );
			$salt = _gp_get_salt( 'GP_LOGGED_IN_SALT' );
			break;

		case 'nonce':
			$secret_key = _gp_get_secret_key( 'GP_NONCE_KEY', $secret_key );
			$salt = _gp_get_salt( 'GP_NONCE_SALT' );
			break;

		default:
			// ensure each auth scheme has its own unique salt
			$salt = hash_hmac( 'md5', $scheme, $secret_key );
			break;
	}

	return apply_filters( 'gp_salt', $secret_key . $salt, $scheme );
}
endif;

if ( !function_exists( 'gp_hash' ) ) :
function gp_hash( $data, $scheme = 'auth' ) {
	$salt = gp_salt( $scheme );

	return hash_hmac( 'md5', $data, $salt );
}
endif;

if ( !function_exists( 'gp_generate_password' ) ) :
/**
 * Generates a random password drawn from the defined set of characters
 * @return string the password
 */
function gp_generate_password( $length = 12, $special_chars = true ) {
	return WP_Pass::generate_password( $length, $special_chars );
}
endif;

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
		$timestamp = backpress_gmt_strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );

	// If nothing to compare against, then always assume there's an update available
	if ( ! $timestamp )
		return true;

	return backpress_gmt_strtotime( GP::$translation->last_modified( $translation_set ) ) > $timestamp;
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
 * Shows the time past since the given time
 *
 * @param int $time Unix time stamp you want to compare against.
 */
function gp_time_since( $time ) {
	$time = time() - $time; // to get the time since that moment

	$tokens = array (
		31536000 => 'year',
		2592000 => 'month',
		604800 => 'week',
		86400 => 'day',
		3600 => 'hour',
		60 => 'minute',
		1 => 'second'
	);

	foreach ( $tokens as $unit => $text ) {
		if ( $time < $unit ) {
			continue;
		}

		$numberOfUnits = floor( $time / $unit );

		return $numberOfUnits . ' ' . $text . ( ( $numberOfUnits > 1 ) ? 's' : '' );
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
 * Acts the same as core PHP setcookie() but its arguments are run through the backpress_set_cookie filter.
 *
 * If the filter returns false, setcookie() isn't called.
 */
function backpress_set_cookie() {
	$args = func_get_args();

	/**
     * Whether GlotPress should set a cookie. If filter returns false, a cookie will not be set.
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
	$args = apply_filters( 'backpress_set_cookie', $args );
	if ( $args === false ) return;
	call_user_func_array( 'setcookie', $args );
}

function backpress_gmt_strtotime( $string ) {
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
 * Encode a variable into JSON, with some sanity checks.
 *
 * @since 1.0.0
 *
 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
 *                       greater than 0. Default 512.
 * @return string|false The JSON encoded string, or false if it cannot be encoded.
 */
function gp_json_encode( $data, $options = 0, $depth = 512 ) {
	/*
	 * json_encode() has had extra params added over the years.
	 * $options was added in 5.3, and $depth in 5.5.
	 * We need to make sure we call it with the correct arguments.
	 */
	if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
		$args = array( $data, $options, $depth );
	} else {
		$args = array( $data, $options );
	}

	$json = call_user_func_array( 'json_encode', $args );

	// If json_encode() was successful, no need to do more sanity checking.
	// ... unless we're in an old version of PHP, and json_encode() returned
	// a string containing 'null'. Then we need to do more sanity checking.
	if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) )  {
		return $json;
	}

	try {
		$args[0] = _gp_json_sanity_check( $data, $depth );
	} catch ( Exception $e ) {
		return false;
	}

	return call_user_func_array( 'json_encode', $args );
}

/**
 * Perform sanity checks on data that shall be encoded to JSON.
 *
 * @ignore
 * @since 1.0.0
 * @access private
 *
 * @see gp_json_encode()
 *
 * @param mixed $data  Variable (usually an array or object) to encode as JSON.
 * @param int   $depth Maximum depth to walk through $data. Must be greater than 0.
 * @return mixed The sanitized data that shall be encoded to JSON.
 */
function _gp_json_sanity_check( $data, $depth ) {
	if ( $depth < 0 ) {
		throw new Exception( 'Reached depth limit' );
	}

	if ( is_array( $data ) ) {
		$output = array();
		foreach ( $data as $id => $el ) {
			// Don't forget to sanitize the ID!
			if ( is_string( $id ) ) {
				$clean_id = _gp_json_convert_string( $id );
			} else {
				$clean_id = $id;
			}

			// Check the element type, so that we're only recursing if we really have to.
			if ( is_array( $el ) || is_object( $el ) ) {
				$output[ $clean_id ] = _gp_json_sanity_check( $el, $depth - 1 );
			} elseif ( is_string( $el ) ) {
				$output[ $clean_id ] = _gp_json_convert_string( $el );
			} else {
				$output[ $clean_id ] = $el;
			}
		}
	} elseif ( is_object( $data ) ) {
		$output = new stdClass;
		foreach ( $data as $id => $el ) {
			if ( is_string( $id ) ) {
				$clean_id = _gp_json_convert_string( $id );
			} else {
				$clean_id = $id;
			}

			if ( is_array( $el ) || is_object( $el ) ) {
				$output->$clean_id = _gp_json_sanity_check( $el, $depth - 1 );
			} elseif ( is_string( $el ) ) {
				$output->$clean_id = _gp_json_convert_string( $el );
			} else {
				$output->$clean_id = $el;
			}
		}
	} elseif ( is_string( $data ) ) {
		return _gp_json_convert_string( $data );
	} else {
		return $data;
	}

	return $output;
}

/**
 * Convert a string to UTF-8, so that it can be safely encoded to JSON.
 *
 * @ignore
 * @since 1.0.0
 * @access private
 *
 * @see _gp_json_sanity_check()
 *
 * @staticvar bool $use_mb
 *
 * @param string $string The string which is to be converted.
 * @return string The checked string.
*/
function _gp_json_convert_string( $string ) {
	static $use_mb = null;
	if ( is_null( $use_mb ) ) {
		$use_mb = function_exists( 'mb_convert_encoding' );
	}

	if ( $use_mb ) {
		$encoding = mb_detect_encoding( $string, mb_detect_order(), true );
		if ( $encoding ) {
			return mb_convert_encoding( $string, 'UTF-8', $encoding );
		} else {
			return mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );
		}
	} else {
		return wp_check_invalid_utf8( $string, true );
	}
}
