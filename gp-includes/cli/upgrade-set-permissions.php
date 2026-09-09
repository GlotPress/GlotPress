<?php
/**
 * GlotPress CLI Command to Upgrade Set Permissions
 *
 * @package GlotPress
 */

/**
 * WP-CLI command class to upgrade set permissions in GlotPress.
 */
class GP_CLI_Upgrade_Set_Permissions extends WP_CLI_Command {

	/**
	 * Upgrade translation set permissions.
	 */
	public function __invoke() {
		$permissions = GP::$permission->find_many(
			array(
				'object_type' => 'translation-set',
				'action'      => 'approve',
			)
		);
		foreach ( $permissions as $permission ) {
			$set     = GP::$translation_set->get( $permission->object_id );
			$project = GP::$project->get( $set->project_id );
			GP::$permission->create(
				array(
					'user_id'     => $permission->user_id,
					'action'      => 'approve',
					'object_type' => 'project|locale|set-slug',
					'object_id'   => $project->id . '|' . $set->locale . '|' . $set->slug,
				)
			);
		}
	}
}
