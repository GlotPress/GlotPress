<?php
/**
 * Functions for retrieving and manipulating metadata of various GlotPress object types.
 *
 * @package GlotPress
 * @subpackage Meta
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

	/**
	 * Filter the meta data before it gets updated.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta_tuple Key value pairs of database columns and their values according
	 *                          to update meta values from the database.
	 */
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
function gp_delete_meta( $object_id = 0, $meta_key, $meta_value, $type, $global = false ) {
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

	/**
	 * Filter the meta data before it gets deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta_tuple Key value pairs of database columns and their values according to delete meta values from the database.
	 */
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
