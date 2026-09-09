<?php
/**
 * GlotPress CLI Command to Wipe Permissions
 *
 * @package GlotPress
 */

/**
 * WP-CLI command class to wipe all permissions in GlotPress.
 */
class GP_CLI_Wipe_Permissions extends WP_CLI_Command {

	/**
	 * Wipe all permissions.
	 */
	public function __invoke() {
		WP_CLI::confirm( "This will erase all current permissions!\nAre you sure you want to delete them?" );

		if ( ! GP::$permission->delete_all() ) {
			WP_CLI::error( __( 'Error in deleting permissions.', 'glotpress' ) );
		}

		WP_CLI::success( __( 'Permissions were deleted. Now you can use `wp glotpress add-admin` to add a new administrator.', 'glotpress' ) );
	}
}
