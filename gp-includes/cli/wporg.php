<?php

class GP_CLI_Wporg extends WP_CLI_Command {

	public const OPTION_NAME = 'gp_is_wporg';

	/**
	 * Sets/unsets a wporg variable in the options table
	 *
	 * This option value is used to conditionally execute code related with the wporg installation
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : The action to be executed.
	 * ---
	 * options:
	 *   - set
	 *   - unset
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp glotpress wporg set
	 *     wp glotpress wporg unset
	 */
	public function __invoke( $args, $assoc_args ) {
		if ( $args[0] === 'set' ) {
			update_option( 'gp_wporg', true );
			WP_CLI::line( sprintf( __( "The '%s' option value has been set in the options table.", 'glotpress' ), self::OPTION_NAME ) );
		}
		if ( $args[0] === 'unset' ) {
			delete_option( 'gp_wporg' );
			WP_CLI::line( sprintf( __( "The '%s' option value has been deleted from the options table.", 'glotpress' ), self::OPTION_NAME ) );
		}
	}
}
