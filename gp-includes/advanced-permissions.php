<?php

/**
 * Filter for can_user, which tries if the user
 * has permissions on project parents
 */
function gp_recurse_project_permissions( $verdict, $args ) {
	if ( !( !$verdict && $args['object_type'] == 'project' && $args['object_id'] && $args['user'] ) ) {
		return $verdict;
	}
	$project = GP::$project->get( $args['object_id'] );
	if ( $project->parent_project_id ) {
		return $args['user']->can( $args['action'], 'project', $project->parent_project_id );
	}
	return false;
}

function gp_recurse_validator_permission( $verdict, $args ) {
	if ( !( !$verdict && $args['object_type'] == GP::$validator_permission->object_type && $args['object_id'] && $args['user'] ) ) {
		return $verdict;
	}
	list( $project_id, $locale_slug, $set_slug ) = GP::$validator_permission->project_id_locale_slug_set_slug( $args['object_id'] );
	$project = GP::$project->get( $project_id );
	if ( $project->parent_project_id ) {
		return $args['user']->can( $args['action'], $args['object_type'], $project->parent_project_id.'|'.$locale_slug.'|'.$set_slug );
	}
	return false;
}


function gp_route_translation_set_permissions_to_validator_permissions( $verdict, $args ) {
	if ( !( $verdict == 'no-verdict' && $args['action'] == 'approve' && $args['object_type'] == 'translation-set'
			&& $args['object_id'] && $args['user'] ) ) {
		return $verdict;
	}
	if ( isset( $args['extra']['set'] ) && $args['extra']['set'] && $args['extra']['set']->id == $args['object_id'] )
		$set = $args['extra']['set'];
	else
		$set = GP::$translation_set->get( $args['object_id'] );
	return $args['user']->can( 'approve', GP::$validator_permission->object_type,
		GP::$validator_permission->object_id( $set->project_id, $set->locale, $set->slug ) );
	
}

add_filter( 'can_user', 'gp_recurse_project_permissions', 10, 2 );
add_filter( 'can_user', 'gp_recurse_validator_permission', 10, 2 );
add_filter( 'pre_can_user', 'gp_route_translation_set_permissions_to_validator_permissions', 10, 2 );
