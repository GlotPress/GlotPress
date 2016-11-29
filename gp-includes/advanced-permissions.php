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
	if ( $project && $project->parent_project_id ) {
		return GP::$permission->user_can( $args['user'], $args['action'], 'project', $project->parent_project_id );
	}
	return false;
}

function gp_recurse_validator_permission( $verdict, $args ) {
	if ( !( !$verdict && $args['object_type'] == GP::$validator_permission->object_type && $args['object_id'] && $args['user'] ) ) {
		return $verdict;
	}
	list( $project_id, $locale_slug, $set_slug ) = GP::$validator_permission->project_id_locale_slug_set_slug( $args['object_id'] );
	$project = GP::$project->get( $project_id );
	if ( $project && $project->parent_project_id ) {
		return GP::$permission->user_can( $args['user'], $args['action'], $args['object_type'], $project->parent_project_id.'|'.$locale_slug.'|'.$set_slug );
	}
	return false;
}


function gp_route_translation_set_permissions_to_validator_permissions( $verdict, $args ) {
	if ( is_bool( $verdict ) ) {
		return $verdict;
	}

	if ( !( $verdict == 'no-verdict' && $args['action'] == 'approve' && $args['object_type'] == 'translation-set'
			&& $args['object_id'] && $args['user'] ) ) {
		return $verdict;
	}
	if ( isset( $args['extra']['set'] ) && $args['extra']['set'] && $args['extra']['set']->id == $args['object_id'] )
		$set = $args['extra']['set'];
	else
		$set = GP::$translation_set->get( $args['object_id'] );
	return GP::$permission->user_can( $args['user'], 'approve', GP::$validator_permission->object_type,
		GP::$validator_permission->object_id( $set->project_id, $set->locale, $set->slug ) );
}

function gp_allow_everyone_to_translate( $verdict, $args ) {
	if ( is_bool( $verdict ) ) {
		return $verdict;
	}

	if ( 'edit' == $args['action'] && 'translation-set' == $args['object_type'] ) {
		return is_user_logged_in();
	}

	return $verdict;
}

/**
 * Maps the translation check to the translation-set.
 *
 * @since 2.3.0
 *
 * @param string|bool $verdict Previous decision whether the user can do this.
 * @param array       $args    Permission details.
 * @return string|bool New decision whether the user can do this.
 */
function gp_allow_approving_translations_with_validator_permissions( $verdict, $args ) {
	if ( is_bool( $verdict ) ) {
		return $verdict;
	}

	if ( 'approve' === $args['action'] && 'translation' === $args['object_type'] ) {
		$args['object_type'] = 'translation-set';

		if ( isset( $args['extra']['translation']->translation_set_id ) ) {
			$args['object_id'] = $args['extra']['translation']->translation_set_id;
		} else {
			return $verdict;
		}

		return gp_route_translation_set_permissions_to_validator_permissions( $verdict, $args );
	}

	return $verdict;
}

add_filter( 'gp_can_user', 'gp_recurse_project_permissions', 10, 2 );
add_filter( 'gp_can_user', 'gp_recurse_validator_permission', 10, 2 );
add_filter( 'gp_pre_can_user', 'gp_route_translation_set_permissions_to_validator_permissions', 10, 2 );
add_filter( 'gp_pre_can_user', 'gp_allow_approving_translations_with_validator_permissions', 10, 2 );
add_filter( 'gp_pre_can_user', 'gp_allow_everyone_to_translate', 10, 2 );
