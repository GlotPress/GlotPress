<?php
/**
 * Meta and options functions
 */


/* Internal */

function gp_sanitize_meta_key( $key ) {
	return preg_replace( '|[^a-z0-9_]|i', '', $key );
}


/**
 * Retrieves and returns a meta value from the database
 *
 * @param string      $object_type The object type.
 * @param int         $object_id   ID of the object metadata is for.
 * @param string|null $meta_key    Optional. Metadata key. Default null.
 *
 * @return mixed|false Metadata or false.
 */
function gp_get_meta( $object_type, $object_id, $meta_key = null ) {
	global $wpdb;
	$meta_key = gp_sanitize_meta_key( $meta_key );

	if ( ! $object_type ) {
		return false;
	}

	if ( ! is_numeric( $object_id ) || empty( $object_id ) ) {
		return false;
	}
	$object_id = (int) $object_id;

	$object_meta = wp_cache_get( $object_id, $object_type );

	if ( false === $object_meta ) {
		$db_object_meta = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_key`, `meta_value` FROM `$wpdb->gp_meta` WHERE `object_type` = %s AND `object_id` = %d", $object_type, $object_id ) );

		$object_meta = array();
		foreach ( $db_object_meta as $meta ) {
			$object_meta[ $meta->meta_key ] = maybe_unserialize( $meta->meta_value );
		}

		wp_cache_add( $object_id, $object_meta, $object_type );
	}

	if ( $meta_key && isset( $object_meta[ $meta_key ] ) ) {
		return $object_meta[ $meta_key ];
	} elseif ( ! $meta_key ) {
		return $object_meta;
	} else {
		return false;
	}
}

/**
 * Adds and updates meta data in the database
 *
 * @internal
 */
function gp_update_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false ) {
	global $wpdb;

	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global ) {
		return false;
	}
	$cache_object_id = $object_id = (int) $object_id;
	switch ( $type ) {
		case 'option':
			$object_type = 'gp_option';
			break;
		case 'user' :
			return update_user_meta( $object_id, $meta_key, $meta_value );
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

	$cur = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$wpdb->gp_meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key ) );
	if ( !$cur ) {
		$wpdb->insert( $wpdb->gp_meta, array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key, 'meta_value' => $_meta_value ) );
	} elseif ( $cur->meta_value != $meta_value ) {
		$wpdb->update( $wpdb->gp_meta, array( 'meta_value' => $_meta_value), array( 'object_type' => $object_type, 'object_id' => $object_id, 'meta_key' => $meta_key ) );
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
	global $wpdb;
	if ( !is_numeric( $object_id ) || empty( $object_id ) && !$global ) {
		return false;
	}
	$cache_object_id = $object_id = (int) $object_id;
	switch ( $type ) {
		case 'option':
			$object_type = 'gp_option';
			break;
		case 'user':
			return delete_user_meta( $object_id, $meta_key, $meta_value );
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
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `$wpdb->gp_meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s", $object_type, $object_id, $meta_key );
	} else {
		$meta_sql = $wpdb->prepare( "SELECT `meta_id` FROM `$wpdb->gp_meta` WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s AND `meta_value` = %s", $object_type, $object_id, $meta_key, $meta_value );
	}

	if ( !$meta_id = $wpdb->get_var( $meta_sql ) ) {
		return false;
	}

	$wpdb->query( $wpdb->prepare( "DELETE FROM `$wpdb->gp_meta` WHERE `meta_id` = %d", $meta_id ) );

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
	global $wpdb;
	if ( 'user' === $type ) {
		return $object;
	}

	if ( is_array( $object ) && $object ) {
		$trans = array();
		foreach ( array_keys( $object ) as $i ) {
			$trans[$object[$i]->$object_id_column] =& $object[$i];
		}
		$ids = join( ',', array_map( 'intval', array_keys( $trans ) ) );
		if ( $metas = $wpdb->get_results( "SELECT `object_id`, `meta_key`, `meta_value` FROM `$wpdb->gp_meta` WHERE `object_type` = '$object_type' AND `object_id` IN ($ids) /* gp_append_meta */" ) ) {
			usort( $metas, '_gp_append_meta_sort' );
			foreach ( $metas as $meta ) {
				$trans[$meta->object_id]->{$meta->meta_key} = maybe_unserialize( $meta->meta_value );
				if ( strpos($meta->meta_key, $wpdb->prefix) === 0 ) {
					$trans[$meta->object_id]->{substr($meta->meta_key, strlen($wpdb->prefix))} = maybe_unserialize( $meta->meta_value );
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
		if ( $metas = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_key`, `meta_value` FROM `$wpdb->gp_meta` WHERE `object_type` = '$object_type' AND `object_id` = %d /* gp_append_meta */", $object->$object_id_column ) ) ) {
			usort( $metas, '_gp_append_meta_sort' );
			foreach ( $metas as $meta ) {
				$object->{$meta->meta_key} = maybe_unserialize( $meta->meta_value );
				if ( strpos( $meta->meta_key, $wpdb->prefix ) === 0 ) {
					$object->{substr( $meta->meta_key, strlen( $wpdb->prefix ) )} = $object->{$meta->meta_key};
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
 * Sorts meta keys by length to ensure $appended_object->{$wpdb->prefix} key overwrites $appended_object->key as desired
 *
 * @internal
 */
function _gp_append_meta_sort( $a, $b )
{
	return strlen( $a->meta_key ) - strlen( $b->meta_key );
}

