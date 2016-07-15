<?php
/**
 * CLI Interface to wipe all GlotPress data from the database.
 *
 * @package GlotPress
 * @since 2.2.0
 */

/**
 * CLI class used to wipe data from the database.
 *
 * @since 2.2.0
 */
class GP_CLI_Wipe_Data extends WP_CLI_Command {

	/**
	 * Invoke function used to wipe the data from the database.
	 *
	 * @since 2.2.0
	 */
	public function __invoke() {
		WP_CLI::confirm( __( "This will erase all GlotPress data from the database!\nAre you sure you want to delete all data?", 'glotpress' ) );

		include_once( GP_PATH . '/uninstall.php' );

		gp_uninstall();

		WP_CLI::success( __( 'All data delete.', 'glotpress' ) );
	}
}
