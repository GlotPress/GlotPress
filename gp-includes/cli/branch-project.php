<?php

class GP_CLI_Branch_Project extends WP_CLI_Command {
	/**
	 * Branch a project
	 *
	 * Duplicates an existing project to create a new one.
	 *
	 * ## OPTIONS
	 *
	 * <source>
	 * : Source project path to duplicate from
	 *
	 * <destination>
	 * : Destination project path to duplicate to (must exist first)
	 */
	public function __invoke( $args ) {
		$source_project = GP::$project->by_path( $args[0] );
		if ( ! $source_project ){
			WP_CLI::error( __( 'Source project not found!', 'glotpress' ) );
		}

		$destination_project = GP::$project->by_path( $args[1] );
		if ( ! $destination_project ){
			WP_CLI::error( __( 'Destination project not found!', 'glotpress' ) );
		}

		$destination_project->duplicate_project_contents_from( $source_project );
	}
}
