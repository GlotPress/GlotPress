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
	global $gp_redirect_notices;
	$gp_redirect_notices[$key] = $message;
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
	// TODO: keep notices in a cookie -- query args are both ugly and a security hole
	global $gp_redirect_notices;
	$gp_redirect_notices = array();
	$prefix = '_gp_notice_';
	foreach ($_GET as $key => $value ) {
		if ( gp_startswith( $key, $prefix ) && $suffix = substr( $key, strlen( $prefix ) )) {			
			$gp_redirect_notices[$suffix] = $value;
		}
	}
}


function gp_redirect( $location, $status = 302 ) {
	global $gp_redirect_notices;
	foreach( $gp_redirect_notices as $key => $message ) {
		$location = add_query_arg( "_gp_notice_$key", rawurlencode( $message ), $location );
	}
	$res = wp_redirect( $location, $status );
	if ( false === $res) return $res;
	exit;
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