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
 * Returns a function, which returns the string "odd" or the string "even" alternatively.
 */
function gp_parity_factory() {
	return create_function( '', 'static $parity = "odd"; if ($parity == "even") $parity = "odd"; else $parity = "even"; return $parity;');
}

/**
 * Builds SQL LIMIT/OFFSET clause for the given page
 * 
 * @param integer $page The page number. The first page is 1.
 * @param integer $per_page How many items are there in a page
 */
function gp_limit_for_page( $page, $per_page ) {
	global $gpdb;
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

	if ( !defined( 'GP_INSTALLING' ) || !GP_INSTALLING ) {
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

	return '';
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

	return apply_filters( 'salt', $secret_key . $salt, $scheme );
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