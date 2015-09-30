<?php

class GP_CLI_Import_Originals extends WP_CLI_Command {
	/**
	 * Import originals for a project from a file
	 *
	 * ## OPTIONS
	 *
	 * <project>
	 * : Project name
	 *
	 * <file>
	 * : File to import from
	 *
	 * [--format=<format>]
	 * : File format (one of "po", "mo", "android", "resx", "strings"; default is "po")
	 */
	public function __invoke( $args, $assoc_args ) {
		// Double-check for compatibility
		if ( $args[0] === '-p' || $args[1] === '-f' ) {
			WP_CLI::error( '-p and -f are no longer required and should be removed.' );
		}

		$project = GP::$project->by_path( $args[0] );
		if ( !$project ) {
			WP_CLI::error( __('Project not found!') );
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'po';
		$format = gp_array_get( GP::$formats, $format, null );
		if ( !$format ) {
			WP_CLI::error( __('No such format.') );
		}

		$translations = $format->read_originals_from_file( $args[1], $project );
		if ( !$translations ) {
			WP_CLI::error( __("Couldn't load translations from file!") );
		}

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = GP::$original->import_for_project( $project, $translations );
		WP_CLI::line(
			__( '%1$s new strings added, %2$s updated, %3$s fuzzied, and %4$s obsoleted.' ),
			$originals_added,
			$originals_existing,
			$originals_fuzzied,
			$originals_obsoleted
		);
	}
}
