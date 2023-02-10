<?php
/**
 * Routes: GP_Route_Local class
 *
 * @package GlotPress
 * @subpackage Routes
 * @since 4.0.0
 */

/**
 * Core class used to implement the local translation route.
 *
 * @since 4.0.0
 */
class GP_Route_Local extends GP_Route_Main {

	public function test( $path ) {
		switch ( strtok( $path, '/' ) ) {
			case 'core':
				$this->translate_core();
				$this->redirect( gp_url( '/projects/local-core/' . GP_Locales::by_field( 'wp_locale', get_user_locale() )->slug . '/default/' ) );
				break;
			case 'plugin':
				$this->translate_plugin();
				break;
			case 'theme':
				$this->translate_theme();
				break;
			default:
				break;
		}
	}

	private function translate_core() {
		// todo: check if the user is logged in and has the right permissions.
		// Create a new project if it doesn't exist.
		$project = $this->get_or_create_project( 'Local core', 'local-core', 'Local core translation' );

		// Create a new translation set for the user's locale and project if it doesn't exist.
		$locale          = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		$translation_set = $this->get_or_create_translation_set( $project, 'default', $locale );

		// Import the originals if the project doesn't have any string in the originals table
		$file_to_import = ABSPATH . 'wp-content/languages/admin-' . $locale->wp_locale . '.po';
		$originals      = $this->get_or_import_originals( $project, $file_to_import );

		// Import the translations if the project doesn't have any string in
		// the translations table for the translation set.
		$translations = $this->get_or_import_translations( $project, $translation_set, $file_to_import );
	}

	private function translate_plugin() {

	}

	private function translate_theme() {

	}

	/**
	 * Get or create a project.
	 *
	 * @param string $name        The name of the project.
	 * @param string $slug        The slug of the project.
	 * @param string $description The description of the project.
	 *
	 * @return GP_Project
	 */
	private function get_or_create_project( string $name, string $slug, string $description ): GP_Project {
		$project = GP::$project->by_path( $slug );
		if ( ! $project ) {
			$new_project = new GP_Project(
				array(
					'name'        => $name,
					'slug'        => $slug,
					'description' => $description,
				)
			);
			$project     = GP::$project->create_and_select( $new_project );
		}
		return $project;
	}

	/**
	 * Get or create a translation set.
	 *
	 * @param GP_Project $project The project.
	 * @param string     $slug    The slug of the translation set.
	 * @param GP_Locale  $locale  The locale of the translation set.
	 *
	 * @return GP_Translation_Set
	 */
	private function get_or_create_translation_set( GP_Project $project, string $slug, GP_Locale $locale ): GP_Translation_Set {
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale(
			$project->id,
			$slug,
			$locale->slug
		);
		if ( ! $translation_set ) {
			$new_set         = new GP_Translation_Set(
				array(
					'name'       => $locale->english_name,
					'slug'       => $slug,
					'project_id' => $project->id,
					'locale'     => $locale->slug,
				)
			);
			$translation_set = GP::$translation_set->create_and_select( $new_set );
		}
		return $translation_set;
	}

	/**
	 * Get or import the originals.
	 *
	 * @param GP_Project $project The project.
	 * @param string     $file    The file to import.
	 *
	 * @return array
	 */
	private function get_or_import_originals( GP_Project $project, string $file ): array {
		$originals = GP::$original->by_project_id( $project->id );
		if ( ! $originals ) {
			$format    = 'po';
			$format    = gp_array_get( GP::$formats, $format, null );
			$originals = $format->read_originals_from_file( $file, $project );
			GP::$original->import_for_project( $project, $originals );
		}
		return $originals;
	}

	private function get_or_import_translations( GP_Project $project, GP_Translation_Set $translation_set, string $file ):array {
		$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		if ( ! $translations ) {
			$po       = new PO();
			$imported = $po->import_from_file( $file );
			$translation_set->import( $po, 'current' );
			$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		}
		return $translations;
	}
}
