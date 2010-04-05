<?php
require_once dirname( dirname( __FILE__ ) ) . '/gp-load.php';

class GP_Script_Upgrade_Permissions extends GP_CLI {
	
	function run() {
		$permissions = GP::$permission->find_many( array( 'object_type' => 'translation-set', 'action' => 'approve' ) );
		foreach( $permissions as $permission ) {
			$set = GP::$translation_set->get( $permission->object_id );
			$project = GP::$project->get( $set->project_id );
			GP::$permission->create( array(
				'user_id' => $permission->user_id,
				'action' => 'approve',
				'object_type' => 'project|locale|set-slug',
				'object_id' => $project->id.'|'.$set->locale.'|'.$set->slug,
			) );
		}
	}
}
$gp_script_upgrade_permissions = new GP_Script_Upgrade_Permissions;
$gp_script_upgrade_permissions->run();