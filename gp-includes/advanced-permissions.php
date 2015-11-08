<?php

/**
 *
 * @param array  $caps    Returns the user's actual capabilities.
 * @param string $cap     Capability name.
 * @param int    $user_id The user ID.
 * @param array  $args    Adds the context to the cap. Typically the object ID.
 * @return array Actual capabilities for meta capability.
 */
function gp_map_meta_cap( $caps, $cap, $user_id, $args ) {
	switch ( $cap ) {
		case 'gp_create_projects':
		case 'gp_delete_projects':
		case 'gp_delete_project':
		case 'gp_edit_projects':
		case 'gp_edit_project':
			array_pop( $caps );

			$caps[] = 'manage_options';
			break;

		case 'gp_suggest_translations':
			array_pop( $caps );

			if ( $user_id ) {
				$caps[] = 'gp_suggest_translations';
			} else {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'gp_approve_translations':
			array_pop( $caps );

			if ( ! isset( $args[0] ) ) { // ID of a translation set.
				$caps[] = 'do_not_allow';
				break;
			}

			$translation_set = GP::$translation_set->get( $args[0] );
			if ( ! $translation_set ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$permission_args = array(
				'user_id'     => $user_id,
				'action'      => 'approve',
				'object_type' => GP::$validator_permission->object_type,
				'object_id'   => GP::$validator_permission->object_id( $translation_set->project_id, $translation_set->locale, $translation_set->slug )
			);

			$has_permission =
				GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
				GP::$permission->find_one( $permission_args );

			if ( $has_permission ) {
				$caps[] = 'gp_approve_translations';
			} else {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'gp_create_glossary_entry':
		case 'gp_edit_glossary':
			array_pop( $caps );

			if ( ! isset( $args[0] ) ) { // ID of a glossary
				$caps[] = 'do_not_allow';
				break;
			}

			$glossary = GP::$glossary->get( $args[0] );
			if ( ! $glossary ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$translation_set = GP::$translation_set->get( $glossary->translation_set_id );
			if ( ! $translation_set ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$permission_args = array(
				'user_id'     => $user_id,
				'action'      => 'approve',
				'object_type' => GP::$validator_permission->object_type,
				'object_id'   => GP::$validator_permission->object_id( $translation_set->project_id, $translation_set->locale, $translation_set->slug )
			);

			$has_permission =
				GP::$permission->find_one( array( 'action' => 'admin', 'user_id' => $user_id ) ) ||
				GP::$permission->find_one( $permission_args );

			if ( $has_permission ) {
				$caps[] = 'gp_edit_glossaries';
			} else {
				$caps[] = 'do_not_allow';
			}
			break;
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'gp_map_meta_cap', 10, 4 );

/**
 *
 * @return array An array of primary capabilities for GlotPress.
 */
function gp_get_primary_capabilities() {
	return array(
		'gp_create_projects',
		'gp_delete_projects',
		'gp_edit_projects',

		'gp_suggest_translations',
		'gp_approve_translations',
	//	'gp_import_translations', => gp_approve_translations

	//	'gp_import_originals', => 'gp_edit_projects'

		'gp_create_glossaries',
		'gp_edit_glossaries',
		'gp_delete_glossaries',

		'gp_create_glossary_entries',
		'gp_edit_glossary_entries',
		'gp_delete_glossary_entries',
		'gp_import_glossary_entries',
	);
}

/**
 *
 * @param array   $allcaps An array of all the user's capabilities.
 * @param array   $caps    Actual capabilities for meta capability.
 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
 * @param WP_User $user    The user object.
 * @return array An array of all the user's capabilities.
 */
function gp_user_has_cap( $allcaps, $caps, $args, $user ) {

	$caps_keyed = array_flip( $caps );
	$primary_capabilities = gp_get_primary_capabilities();
	foreach ( $primary_capabilities as $primary_capability ) {
		if ( array_key_exists( $primary_capability, $caps_keyed ) ) {
			$allcaps[ $primary_capability ] = true;
		}
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'gp_user_has_cap', 10 ,4 );

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

function gp_allow_everyone_to_translate( $verdict, $args ) {
	if ( 'edit' == $args['action'] && 'translation-set' == $args['object_type'] ) {
		return is_user_logged_in();
	}

	return $verdict;
}

add_filter( 'gp_can_user', 'gp_recurse_project_permissions', 10, 2 );
add_filter( 'gp_can_user', 'gp_recurse_validator_permission', 10, 2 );
add_filter( 'gp_pre_can_user', 'gp_route_translation_set_permissions_to_validator_permissions', 10, 2 );
add_filter( 'gp_pre_can_user', 'gp_allow_everyone_to_translate', 10, 2 );
