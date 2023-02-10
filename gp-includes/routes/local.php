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
		$project = GP::$project->by_path( 'local-core' );
		if ( ! $project ) {
			$new_project = new GP_Project(
				array(
					'name'        => 'Local core',
					'slug'        => 'local-core',
					'description' => 'Local core translation',
				)
			);
			$project     = GP::$project->create_and_select( $new_project );
		}

		// Create a new translation set for the user's locale and project if it doesn't exist.
		$locale          = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		$translation_set = GP::$translation_set->by_project_id_slug_and_locale(
			$project->id,
			'default',
			$locale->slug
		);
		if ( ! $translation_set ) {
			$new_set         = new GP_Translation_Set(
				array(
					'name'       => $locale->english_name,
					'slug'       => 'default',
					'project_id' => $project->id,
					'locale'     => $locale->slug,
				)
			);
			$translation_set = GP::$translation_set->create_and_select( $new_set );
		}

		// Import the originals if the project doesn't have any string in the originals table
		$originals = GP::$original->by_project_id( $project->id );
		if ( ! $originals ) {
			$format    = 'po';
			$format    = gp_array_get( GP::$formats, $format, null );
			$file      = ABSPATH . 'wp-content/languages/admin-' . $locale->wp_locale . '.po';
			$originals = $format->read_originals_from_file( $file, $project );
			GP::$original->import_for_project( $project, $originals );
			dd( $originals );
		}

		// Import the translations if the project doesn't have any string in
		// the translations table for the translation set.
		$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );

		if ( ! $translations ) {
			$po       = new PO();
			$file     = ABSPATH . 'wp-content/languages/admin-' . $locale->wp_locale . '.po';
			$imported = $po->import_from_file( $file );
			$translation_set->import( $po, 'current' );
		}
	}

	private function translate_plugin() {

	}

	private function translate_theme() {

	}
}
