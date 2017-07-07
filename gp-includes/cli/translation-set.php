<?php

class GP_CLI_Translation_Set extends WP_CLI_Command {
	/**
	 * Get a translation set for a project.
	 *
	 * @param string $project Project path
	 * @param string $locale Locale slug
	 * @param string $set Set slug
	 * @return GP_Translation_Set|WP_Error Translation set if available, error otherwise.
	 */
	protected function get_translation_set( $project, $locale, $set = 'default' ) {
		$this->project = GP::$project->by_path( $project );
		if ( ! $this->project ) {
			return new WP_Error( 'gp_set_no_project', __( 'Project not found!', 'glotpress' ) );
		}

		$this->locale = GP_Locales::by_slug( $locale );
		if ( ! $this->locale ) {
			return new WP_Error( 'gp_set_no_locale', __( 'Locale not found!', 'glotpress' ) );
		}

		$this->translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->project->id, $set, $this->locale->slug );
		if ( ! $this->translation_set ) {
			return new WP_Error( 'gp_set_not_found', __( 'Translation set not found!', 'glotpress' ) );
		}

		return $this->translation_set;
	}

	/**
	 * Export the translation set
	 *
	 * ## OPTIONS
	 *
	 * <project>
	 * : Project path
	 *
	 * <locale>
	 * : Locale to export
	 *
	 * [--set=<set>]
	 * : Translation set slug; default is "default"
	 *
	 * [--format=<format>]
	 * : Format for output (one of "po", "mo", "android", "resx", "strings"; default is "po")
	 *
	 * [--search=<search>]
	 * : Search term
	 *
	 * [--status=<status>]
	 * : Translation string status; default is "current"
	 *
	 * [--priority=<priorities>]
	 * : Original priorities, comma separated. Possible values are "hidden,low,normal,high"
	 */
	public function export( $args, $assoc_args ) {
		$set_slug = isset( $assoc_args['set'] ) ? $assoc_args['set'] : 'default';
		$translation_set = $this->get_translation_set( $args[0], $args[1], $set_slug );
		if ( is_wp_error( $translation_set ) ) {
			WP_CLI::error( $translation_set->get_error_message() );
		}

		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'po';
		$format = gp_array_get( GP::$formats, $format, null );
		if ( ! $format ) {
			WP_CLI::error( __( 'No such format.', 'glotpress' ) );
		}

		$filters = array();
		if ( isset( $assoc_args['search'] ) ) {
			$filters['term'] = $assoc_args['search'];
		}
		$filters['status'] = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'current';

		if ( isset( $assoc_args['priority'] ) ) {

			$filters['priority'] = array();

			$priorities = explode( ',', $assoc_args['priority'] );
			$valid_priorities = GP::$original->get_static( 'priorities' );

			foreach ( $priorities as $priority ) {
				$key = array_search( $priority, $valid_priorities );
				if ( false === $key ) {
					WP_CLI::warning( sprintf( 'Invalid priority %s', $priority ) );
				} else {
					$filters['priority'][] = $key;
				}
			}
		}

		$entries = GP::$translation->for_export( $this->project, $translation_set, $filters );
		WP_CLI::line( $format->print_exported_file( $this->project, $this->locale, $translation_set, $entries ) );
	}

	/**
	 * Import a file into the translation set
	 *
	 * ## OPTIONS
	 *
	 * <project>
	 * : Project path
	 *
	 * <locale>
	 * : Locale to export
	 *
	 * <file>
	 * : File to import
	 *
	 * [--set=<set>]
	 * : Translation set slug; default is "default"
	 *
	 * [--status=<status>]
	 * : Translation string status; default is "current"
	 */
	public function import( $args, $assoc_args ) {
		$set_slug = isset( $assoc_args['set'] ) ? $assoc_args['set'] : 'default';
		$translation_set = $this->get_translation_set( $args[0], $args[1], $set_slug );
		if ( is_wp_error( $translation_set ) ) {
			WP_CLI::error( $translation_set->get_error_message() );
		}

		$po = new PO();
		$imported = $po->import_from_file( $args[2] );
		if ( ! $imported ) {
			WP_CLI::error( __( "Couldn't load translations from file!", 'glotpress' ) );
		}

		$desired_status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'current';

		$added = $translation_set->import( $po, $desired_status );

		/* translators: %s: Number of imported translations */
		WP_CLI::line( sprintf( _n( '%s translation was added', '%s translations were added', $added, 'glotpress' ), $added ) );
	}

	/**
	 * Recheck warnings for the translation set
	 *
	 * ## OPTIONS
	 *
	 * <project>
	 * : Project path
	 *
	 * <locale>
	 * : Locale to export
	 *
	 * [--set=<set>]
	 * : Translation set slug; default is "default"
	 *
	 * @subcommand recheck-warnings
	 */
	public function recheck_warnings( $args, $assoc_args ) {
		$set_slug = isset( $assoc_args['set'] ) ? $assoc_args['set'] : 'default';
		$translation_set = $this->get_translation_set( $args[0], $args[1], $set_slug );
		if ( is_wp_error( $translation_set ) ) {
			WP_CLI::error( $translation_set->get_error_message() );
		}

		$project = GP::$project->get( $translation_set->project_id );
		$locale = GP_Locales::by_slug( $translation_set->locale );
		foreach( GP::$translation->for_translation( $project, $translation_set, 'no-limit' ) as $entry ) {
			$warnings = GP::$translation_warnings->check( $entry->singular, $entry->plural, $entry->translations, $locale, $project->plurals_type );
			if ( $warnings == $entry->warnings ) {
				continue;
			}

			$translation = new GP_Translation( array( 'id' => $entry->id ) );
			/* translators: %s: ID of a translation */
			WP_CLI::line( sprintf( __( 'Updating warnings for #%s', 'glotpress' ), $entry->id ) );
			$translation->update( array( 'warnings' => $warnings ) );
		}
	}

}
