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
	 * : Accepted values: po, mo, android, resx, strings. Default: po
	 *
	 * [--disable-propagating]
	 * : If set, propagation will be disabled.
	 *
	 * [--disable-matching]
	 * : If set, matching will be disabled.
	 */
	public function __invoke( $args, $assoc_args ) {
		// Double-check for compatibility
		if ( $args[0] === '-p' || $args[1] === '-f' ) {
			WP_CLI::error( __( '-p and -f are no longer required and should be removed.', 'glotpress' ) );
		}

		$project = GP::$project->by_path( $args[0] );
		if ( !$project ) {
			WP_CLI::error( __( 'Project not found!', 'glotpress' ) );
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'po';
		$format = gp_array_get( GP::$formats, $format, null );
		if ( !$format ) {
			WP_CLI::error( __( 'No such format.', 'glotpress' ) );
		}

		$translations = $format->read_originals_from_file( $args[1], $project );
		if ( ! $translations ) {
			WP_CLI::error( __( "Couldn't load translations from file!", 'glotpress' ) );
		}

		$disable_propagating = isset( $assoc_args['disable-propagating'] );
		$disable_matching = isset( $assoc_args['disable-matching'] );

		if ( $disable_propagating ) {
			add_filter( 'gp_enable_propagate_translations_across_projects', '__return_false' );
		}
		if ( $disable_matching ) {
			add_filter( 'gp_enable_add_translations_from_other_projects', '__return_false' );
		}

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted ) = GP::$original->import_for_project( $project, $translations );

		if ( $disable_matching ) {
			remove_filter( 'gp_enable_add_translations_from_other_projects', '__return_false' );
		}
		if ( $disable_propagating ) {
			remove_filter( 'gp_enable_propagate_translations_across_projects', '__return_false' );
		}

		WP_CLI::line(
			sprintf(
				/* translators: 1: number added, 2: number updated, 3: number fuzzied, 4: number obsoleted */
				__( '%1$s new strings added, %2$s updated, %3$s fuzzied, and %4$s obsoleted.', 'glotpress' ),
				$originals_added,
				$originals_existing,
				$originals_fuzzied,
				$originals_obsoleted
			)
		);
	}
}
