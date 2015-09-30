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
			return new WP_Error( 'gp_set_no_project', __( 'Project not found!' ) );
		}

		$this->locale = GP_Locales::by_slug( $locale );
		if ( ! $this->locale ) {
			return new WP_Error( 'gp_set_no_locale', __( 'Locale not found!' ) );
		}

		$this->translation_set = GP::$translation_set->by_project_id_slug_and_locale( $this->project->id, $set, $this->locale->slug );

		if ( ! $this->translation_set ) {
			return new WP_Error( 'gp_set_not_found', __( 'Translation set not found!' ) );
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
			WP_CLI::error( __( 'No such format.' ) );
		}

		$filters = array();
		if ( isset( $assoc_args['search'] ) ) {
			$filters['term'] = $assoc_args['search'];
		}
		$assoc_args['status'] = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'current';

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
	 */
	public function import( $args, $assoc_args ) {
		$set_slug = isset( $assoc_args['set'] ) ? $assoc_args['set'] : 'default';
		$translation_set = $this->get_translation_set( $args[0], $args[1], $set_slug );
		if ( is_wp_error( $translation_set ) ) {
			WP_CLI::error( $translation_set->get_error_message() );
		}

		$po = new PO();
		$po->import_from_file( $args[2] );
		$added = $translation_set->import( $po );
		printf( _n( "%s translation were added\n", "%s translations were added\n", $added ), $added );
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
			$warnings = GP::$translation_warnings->check( $entry->singular, $entry->plural, $entry->translations, $locale );
			if ( $warnings == $entry->warnings ) {
				continue;
			}

			$translation = new GP_Translation( array( 'id' => $entry->id ) );
			WP_CLI::line( sprintf( __("Updating warnings for %s"), $entry->id ) );
			$translation->update( array( 'warnings' => $warnings ) );
		}
	}

}