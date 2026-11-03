<?php
/**
 * GlotPress CLI Command to Regenerate Paths
 *
 * @package GlotPress
 */

/**
 * WP-CLI command class to regenerate project paths in GlotPress.
 */
class GP_CLI_Regenerate_Paths extends WP_CLI_Command {
	/**
	 * Regenerate paths of all projects.
	 */
	public function __invoke() {
		GP::$project->regenerate_paths();

		WP_CLI::success( 'The paths of all projects are regenerate.' );
	}
}
