<?php

function gp_urldecode_deep($value) {
	$value = is_array( $value ) ? array_map( 'gp_urldecode_deep', $value ) : urldecode( $value );
	return $value;
}

/**
 * Makes all key/value pairs in $vars global variables
 */
function gp_set_globals( $vars ) {
	foreach( $vars as $name => $value ) {
		$GLOBALS[ $name ] = $value;
	}
}

/**
 * Initializes rewrite rules and provides the 'gp_init' action.
 *
 * @since 1.0.0
 */
function gp_init() {
	gp_rewrite_rules();

	/**
	 * Fires after GlotPress has finished loading but before any headers are sent.
	 *
	 * @since 1.0.0
	 */
	do_action( 'gp_init' );
}

/**
 * Deletes user's permissions when they are deleted from WordPress
 * via WP's 'deleted_user' action.
 *
 * @since 1.0.0
 */
function gp_delete_user_permissions( $user_id ) {
	$permissions = GP::$permission->find_many( array( 'user_id' => $user_id ) );

	foreach( $permissions as $permission ) {
		$permission->delete();
	}
}
