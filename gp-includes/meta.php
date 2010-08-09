<?php
/**
 * Meta and options functions
 */


/* Internal */

function gp_sanitize_meta_key( $key ) {
	return preg_replace( '|[^a-z0-9_]|i', '', $key );
}

/**
 * Adds and updates meta data in the database
 *
 * @internal
 */
function gp_update_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false ) {
	global $gpdb;
	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global ) {
		return false;
	}
	$cache_object_id = $object_id = (int) $object_id;
	switch ( $type ) {
		case 'option':
			$object_type = 'gp_option';
			break;
		case 'user' :
			global $wp_users_object;
			$id = $object_id;
			$return = $wp_users_object->update_meta( compact( 'id', 'meta_key', 'meta_value' ) );
			if ( is_wp_error( $return ) ) {
				return false;
			}
			return $return;
			break;
		default :
			$object_type = $type;
			break;
	}

	$meta_key = gp_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value', 'type' );
	$meta_tuple = apply_filters( 'gp_update_meta', $meta_tuple );
	extract( $meta_tuple, EXTR_OVERWRITE );

	$meta_value = $_meta_value = maybe_serialize( $meta_value );
	$meta_value = maybe_unserialize( $meta_value );

	$cur = $gpdb->get_row( $gpdb->prepare( "SELECT * FROM `$gpdb->meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key ) );
	if ( !$cur ) {
		$gpdb->insert( $gpdb->meta, array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key, 'meta_value' => $_meta_value ) );
	} elseif ( $cur->meta_value != $meta_value ) {
		$gpdb->update( $gpdb->meta, array( 'meta_value' => $_meta_value), array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key ) );
	}

	if ( $object_type === 'gp_option' ) {
		$cache_object_id = $meta_key;
		wp_cache_delete( $cache_object_id, 'gp_option_not_set' );
	}
	wp_cache_delete( $cache_object_id, $object_type );

	if ( !$cur ) {
		return true;
	}
}

/**
 * Deletes meta data from the database
 *
 * @internal
 */
function gp_delete_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false )
{
	global $gpdb;
	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global ) {
		return false;
	}
	$cache_object_id = $object_id = (int) $object_id;
	switch ( $type ) {
		case 'option':
			$object_type = 'gp_option';
			break;
		case 'user':
			global $wp_users_object;
			$id = $object_id;
			return $wp_users_object->update_meta( compact( 'id', 'meta_key', 'meta_value' ) );
			break;
		default:
			$object_type = $type;
			break;
	}

	$meta_key = gp_sanitize_meta_key( $meta_key );

	$meta_tuple = compact( 'object_type', 'object_id', 'meta_key', 'meta_value', 'type' );
	$meta_tuple = apply_filters( 'gp_delete_meta', $meta_tuple );
	extract( $meta_tuple, EXTR_OVERWRITE );

	$meta_value = maybe_serialize( $meta_value );

	if ( empty( $meta_value ) ) {
		$meta_sql = $gpdb->prepare( "SELECT `meta_id` FROM `$gpdb->meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key );
	} else {
		$meta_sql = $gpdb->prepare( "SELECT `meta_id` FROM `$gpdb->meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s AND `meta_value` = %s", $object_type, $object_id, $meta_key, $meta_value );
	}

	if ( !$meta_id = $gpdb->get_var( $meta_sql ) ) {
		return false;
	}

	$gpdb->query( $gpdb->prepare( "DELETE FROM `$gpdb->meta` WHERE `meta_id` = %d", $meta_id ) );

	if ( $object_type == 'gp_option' ) {
		$cache_object_id = $meta_key;
		wp_cache_delete( $cache_object_id, 'gp_option_not_set' );
	}
	wp_cache_delete( $cache_object_id, $object_type );
	return true;
}

/**
 * Adds an objects meta data to the object
 *
 * This is the only function that should add to user / topic - NOT gpdb::prepared
 *
 * @internal
 */
function gp_append_meta( $object, $type )
{
	global $gpdb;
	switch ( $type ) {
		case 'user':
			global $wp_users_object;
			return $wp_users_object->append_meta( $object );
			break;
	}

	if ( is_array( $object ) && $object ) {
		$trans = array();
		foreach ( array_keys( $object ) as $i ) {
			$trans[$object[$i]->$object_id_column] =& $object[$i];
		}
		$ids = join( ',', array_map( 'intval', array_keys( $trans ) ) );
		if ( $metas = $gpdb->get_results( "SELECT `object_id`, `meta_key`, `meta_value` FROM `$gpdb->meta` WHERE `object_type` = '$object_type' AND `object_id` IN ($ids) /* gp_append_meta */" ) ) {
			usort( $metas, '_gp_append_meta_sort' );
			foreach ( $metas as $meta ) {
				$trans[$meta->object_id]->{$meta->meta_key} = maybe_unserialize( $meta->meta_value );
				if ( strpos($meta->meta_key, $gpdb->prefix) === 0 ) {
					$trans[$meta->object_id]->{substr($meta->meta_key, strlen($gpdb->prefix))} = maybe_unserialize( $meta->meta_value );
				}
			}
		}
		foreach ( array_keys( $trans ) as $i ) {
			wp_cache_add( $i, $trans[$i], $object_type );
			if ( $slug ) {
				wp_cache_add( $trans[$i]->$slug, $i, 'gp_' . $slug );
			}
		}
		return $object;
	} elseif ( $object ) {
		if ( $metas = $gpdb->get_results( $gpdb->prepare( "SELECT `meta_key`, `meta_value` FROM `$gpdb->meta` WHERE `object_type` = '$object_type' AND `object_id` = %d /* gp_append_meta */", $object->$object_id_column ) ) ) {
			usort( $metas, '_gp_append_meta_sort' );
			foreach ( $metas as $meta ) {
				$object->{$meta->meta_key} = maybe_unserialize( $meta->meta_value );
				if ( strpos( $meta->meta_key, $gpdb->prefix ) === 0 ) {
					$object->{substr( $meta->meta_key, strlen( $gpdb->prefix ) )} = $object->{$meta->meta_key};
				}
			}
		}
		if ( $object->$object_id_column ) {
			wp_cache_set( $object->$object_id_column, $object, $object_type );
			if ( $slug ) {
				wp_cache_add( $object->$slug, $object->$object_id_column, 'gp_' . $slug );
			}
		}
		return $object;
	}
}

/**
 * Sorts meta keys by length to ensure $appended_object->{$gpdb->prefix} key overwrites $appended_object->key as desired
 *
 * @internal
 */
function _gp_append_meta_sort( $a, $b )
{
	return strlen( $a->meta_key ) - strlen( $b->meta_key );
}



/* Options */

/**
 * Echoes the requested bbPress option by calling gp_get_option()
 *
 * @param string The option to be echoed
 * @return void
 */
function gp_option( $option )
{
	echo apply_filters( 'gp_option_' . $option, gp_get_option( $option ) );
}

/**
 * Returns the requested bbPress option from the meta table or the $bb object
 *
 * @param string The option to be echoed
 * @return mixed The value of the option
 */
function gp_get_option( $option ) {
	global $bb;

	switch ( $option ) {
		case 'language':
			$r = str_replace( '_', '-', get_locale() );
			break;
		case 'text_direction':
			global $gp_locale;
			$r = $gp_locale->text_direction;
			break;
		case 'version':
			return '0.1'; // Don't filter
			break;
		case 'gp_db_version' :
			return '569'; // Don't filter
			break;
		case 'html_type':
			$r = 'text/html';
			break;
		case 'charset':
			$r = 'UTF-8';
			break;
		case 'gp_table_prefix':
		case 'table_prefix':
			global $gpdb;
			return $gpdb->prefix; // Don't filter;
			break;
		case 'url':
			$option = 'uri';
		default:
			$r = gp_get_option_from_db( $option );
			break;
	}

	return apply_filters( 'gp_get_option_' . $option, $r, $option );
}

/**
 * Retrieves and returns the requested bbPress option from the meta table
 *
 * @param string The option to be echoed
 * @return void
 */
function gp_get_option_from_db( $option ) {
	global $gpdb;
	$option = gp_sanitize_meta_key( $option );

	if ( wp_cache_get( $option, 'gp_option_not_set' ) ) {
		$r = null;
	} elseif ( false !== $_r = wp_cache_get( $option, 'gp_option' ) ) {
		$r = $_r;
	} else {
		if ( defined( 'GP_INSTALLING' ) && GP_INSTALLING ) {
			$gpdb->suppress_errors();
		}
		$row = $gpdb->get_row( $gpdb->prepare( "SELECT `meta_value` FROM `$gpdb->meta` WHERE `object_type` = 'gp_option' AND `meta_key` = %s", $option ) );
		if ( defined( 'GP_INSTALLING' ) && GP_INSTALLING ) {
			$gpdb->suppress_errors( false );
		}

		if ( is_object( $row ) ) {
			$r = maybe_unserialize( $row->meta_value );
		} else {
			$r = null;
		}
	}

	if ( $r === null ) {
		wp_cache_set( $option, true, 'gp_option_not_set' );
	} else {
		wp_cache_set( $option, $r, 'gp_option' );
	}

	return apply_filters( 'gp_get_option_from_db_' . $option, $r, $option );
}

// Don't use the return value; use the API. Only returns options stored in DB.
function gp_cache_all_options()
{
	global $gpdb;
	$results = $gpdb->get_results( "SELECT `meta_key`, `meta_value` FROM `$gpdb->meta` WHERE `object_type` = 'gp_option'" );

	if ( !$results || !is_array( $results ) || !count( $results ) ) {
		return false;
	} else {
		foreach ( $results as $options ) {
			wp_cache_set( $options->meta_key, maybe_unserialize( $options->meta_value ), 'gp_option' );
		}
	}

	// TODO: leave only the GlotPress options here
	$base_options = array(
		'gp_db_version',
		'name',
		'description',
		'uri_ssl',
		'from_email',
		'gp_auth_salt',
		'gp_secure_auth_salt',
		'gp_logged_in_salt',
		'gp_nonce_salt',
		'page_topics',
		'edit_lock',
		'gp_active_theme',
		'active_plugins',
		'mod_rewrite',
		'datetime_format',
		'date_format',
		'avatars_show',
		'avatars_default',
		'avatars_rating',
		'wp_table_prefix',
		'user_gpdb_name',
		'user_gpdb_user',
		'user_gpdb_password',
		'user_gpdb_host',
		'user_gpdb_charset',
		'user_gpdb_collate',
		'custom_user_table',
		'custom_user_meta_table',
		'wp_siteurl',
		'wp_home',
		'cookiedomain',
		'usercookie',
		'passcookie',
		'authcookie',
		'cookiepath',
		'sitecookiepath',
		'secure_auth_cookie',
		'logged_in_cookie',
		'admin_cookie_path',
		'core_plugins_cookie_path',
		'user_plugins_cookie_path',
		'wp_admin_cookie_path',
		'wp_plugins_cookie_path',
		'enable_xmlrpc',
		'enable_pingback',
		'throttle_time',
		'gp_xmlrpc_allow_user_switching',
		'bp_bbpress_cron'
	);

	foreach ( $base_options as $base_option ) {
		if ( false === wp_cache_get( $base_option, 'gp_option' ) ) {
			wp_cache_set( $base_option, true, 'gp_option_not_set' );
		}
	}

	return true;
}

// Can store anything but NULL.
function gp_update_option( $option, $value ) {
	return gp_update_meta( 0, $option, $value, 'option', true );
}

function gp_delete_option( $option, $value = '' ) {
	return gp_delete_meta( 0, $option, $value, 'option', true );
}