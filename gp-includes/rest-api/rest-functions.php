<?php
/**
 * Rest API helper functions.
 *
 * @package GlotPress\RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Safely encode a project path for REST API links.
 *
 * Preserves slashes between path segments, but encodes unsafe characters in each segment.
 * 
 * @since 5.0.0
 *
 * @param string $path The project path (may contain slashes).
 * @return string Encoded path safe for URLs
 */
function gp_rest_encode_project_path( string $path ): string {
	$segments = explode( '/', $path );
	$encoded_segments = array_map( 'rawurlencode', $segments );
	return implode( '/', $encoded_segments );
}

/**
 * Retrieves a project from request.
 *
 * @since 5.0.0
 *
 * @param WP_REST_Request $request Request object.
 * @return GP_Project|WP_Error Project object on success, or WP_Error object on failure.
 */
function gp_rest_get_project( $request ) {
	static $cache = array();
	
	$project_key = $request->get_url_params()['project'] ?? $request->get_body_params()['project'] ?? $request['project'] ?? null;
	
	// Return cached result if available.
	if ( isset( $cache[ $project_key ] ) ) {
		return $cache[ $project_key ];
	}
	
	// Perform lookup.
	$project = $project_key ? GP::$project->by_path_or_id( $project_key ) : false;
	
	if ( ! $project || ! $project instanceof GP_Project ) {
		$error = new WP_Error( 
			'gp_rest_project_invalid_id', 
			__( 'No project found with that ID.', 'glotpress' ), 
			array( 'status' => 404 ) 
		);
		
		// Cache the error too (avoid repeated lookups for invalid IDs).
		$cache[ $project_key ] = $error;
		return $error;
	}
	
	// Cache the successful result.
	$cache[ $project_key ] = $project;
	return $project;
}