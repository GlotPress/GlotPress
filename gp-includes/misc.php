<?php 

/**
 * Retrieves a value from $_POST
 */
function gp_post( $key, $default = '' ) {
	return gp_array_get( $_POST, $key, $default );
}

function gp_get( $key, $default = '' ) {
	return gp_array_get( $_GET, $key, $default );
}

function gp_array_get( $array, $key, $default = '' ) {
	return isset( $array[$key] )? $array[$key] : $default;
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
	setcookie( '_gp_notice_'.$key, $message );
}

/**
 * Retrieves a notice message, set by {@link gp_notice()}
 * 
 * @param string $key Optional. Message key. The default is 'notice'
 */
function gp_notice( $key = 'notice' ) {
	global $gp_redirect_notices;
	return gp_array_get( $gp_redirect_notices, $key );
}

function gp_populate_notices() {
	global $gp_redirect_notices;
	$gp_redirect_notices = array();
	$prefix = '_gp_notice_';
	foreach ($_COOKIE as $key => $value ) {
		if ( gp_startswith( $key, $prefix ) && $suffix = substr( $key, strlen( $prefix ) )) {
			$gp_redirect_notices[$suffix] = $value;
		}
		setcookie( $key, '' );
	}
}

/**
 * Redirects to another page.
 * 
 * @param string $location The path to redirect to
 * @param int $status Status code to use
 * @return bool False if $location is not set
 */
function wp_redirect($location, $status = 302) {
	// TODO: add server-guessing code from bb-load.php in a function in gp-includes/system.php
    global $is_IIS;

    $location = apply_filters('wp_redirect', $location, $status);
    $status = apply_filters('wp_redirect_status', $status, $location);

    if ( !$location ) // allows the wp_redirect filter to cancel a redirect
        return false;

    $location = wp_sanitize_redirect($location);

    if ( $is_IIS ) {
        header("Refresh: 0;url=$location");
    } else {
        if ( php_sapi_name() != 'cgi-fcgi' )
            status_header($status); // This causes problems on IIS and some FastCGI setups
        header("Location: $location");
    }
}

if ( !function_exists('wp_sanitize_redirect') ) : // [WP6134]
/**
 * sanitizes a URL for use in a redirect
 * @return string redirect-sanitized URL
 */
function wp_sanitize_redirect($location) {
    $location = preg_replace('|[^a-z0-9-~+_.?#=&;,/:%]|i', '', $location);
    $location = wp_kses_no_null($location);

    // remove %0d and %0a from location
    $strip = array('%0d', '%0a');
    $found = true;
    while($found) {
        $found = false;
        foreach($strip as $val) {
            while(strpos($location, $val) !== false) {
                $found = true;
                $location = str_replace($val, '', $location);
            }
        }
    }
    return $location;
}
endif;

function gp_parity_factory( ) {
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


function _gp_get_key( $key, $default_key = false ) {
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
	$secret_key = _gp_get_key( 'GP_SECRET_KEY' );

	switch ($scheme) {
		case 'auth':
			$secret_key = _gp_get_key( 'GP_AUTH_KEY', $secret_key );
			$salt = _gp_get_salt( array( 'GP_AUTH_SALT', 'GP_SECRET_SALT' ) );
			break;

		case 'secure_auth':
			$secret_key = _gp_get_key( 'GP_SECURE_AUTH_KEY', $secret_key );
			$salt = _gp_get_salt( 'GP_SECURE_AUTH_SALT' );
			break;

		case 'logged_in':
			$secret_key = _gp_get_key( 'GP_LOGGED_IN_KEY', $secret_key );
			$salt = _gp_get_salt( 'GP_LOGGED_IN_SALT' );
			break;

		case 'nonce':
			$secret_key = _gp_get_key( 'GP_NONCE_KEY', $secret_key );
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
