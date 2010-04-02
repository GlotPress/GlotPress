<?php

/**
 * Filter for can_user, which tries if the user
 * has permissions on project parents
 */
function gp_recurse_project_permissions( $verdict, $args ) {	
	if ( $verdict || $args['object_type'] != 'project' || !$args['object_id'] || !$args['user'] ) return $verdict;
	$project = GP::$project->get( $args['object_id'] );
	if ( $project->parent_project_id ) {
		return $args['user']->can( $args['action'], 'project', $project->parent_project_id );
	}
	return false;
}

add_filter( 'can_user', 'gp_recurse_project_permissions', 10, 2 );
