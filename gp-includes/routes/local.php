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

	/**
	 * If we are using a .po file from translate.w.org, so we remove it after using.
	 *
	 * @var string
	 */
	public $using_external_file = false;

	/**
	 * Imports the originals and translations for the WordPress core, a plugin or a theme.
	 *
	 * @param string $slug The slug of the plugin or theme.
	 */
	public function import( string $slug ) {
		$this->security_checks( $slug );
		switch ( strtok( $slug, '/' ) ) {
			case 'core':
				$this->translate_core();
				$this->redirect( gp_url( '/projects/local-wordpress/' ) );
				break;
			case 'plugin':
				$this->translate_plugin( $slug );
				$this->redirect( gp_url( '/projects/local-plugins/' . substr( $slug, 7 ) ) );
				break;
			case 'theme':
				$this->translate_theme( $slug );
				$this->redirect( gp_url( '/projects/local-themes/' . substr( $slug, 6 ) ) );
				break;
		}
	}

	/**
	 * Checks the nonce and the permissions.
	 *
	 * @param string $slug The slug of the core, plugin or theme.
	 */
	private function security_checks( string $slug ) {
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'gp-local-' . basename( $slug ) ) ) {
			wp_die( esc_html__( 'Your nonce could not be verified.', 'glotpress' ) );
		}
		if ( ! $this->can( 'write', 'project', null ) ) {
			wp_die( esc_html__( 'You are not allowed to do that!', 'glotpress' ) );
		}
	}

	/**
	 * Creates the projects and import the originals and translations for the WordPress core.
	 */
	private function translate_core() {
		$locale = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		// Create the main project for the WordPress core.
		$main_project = $this->get_or_create_project( 'WordPress', 'local-wordpress', 'local-wordpress', 'Local WordPress Core Translation' );

		// Create the subprojects for the WordPress core.
		$this->create_project_and_import_strings(
			'Administration',
			'local-wordpress-administration',
			'local-wordpress/local-wordpress-administration',
			'WordPress Administration',
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/admin-' . $locale->wp_locale . '.po'
		);
		$this->create_project_and_import_strings(
			'Network Admin',
			'local-wordpress-network-admin',
			'local-wordpress/local-wordpress-network-admin',
			'WordPress Network Administration',
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/admin-network-' . $locale->wp_locale . '.po'
		);
		$this->create_project_and_import_strings(
			'Continents & Cities',
			'local-wordpress-continents-cities',
			'local-wordpress/local-wordpress-continents-cities',
			'WordPress Continents & Cities',
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/continents-cities-' . $locale->wp_locale . '.po'
		);
		$this->create_project_and_import_strings(
			'Development',
			'local-wordpress-development',
			'local-wordpress/local-wordpress-development',
			'WordPress Development',
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/' . $locale->wp_locale . '.po'
		);
	}

	/**
	 * Creates the projects and import the originals and translations for a plugin.
	 *
	 * @param string $path The path of the plugin.
	 */
	private function translate_plugin( string $path ) {
		$locale = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins        = apply_filters( 'local_glotpress_local_plugins', get_plugins() );
		$textDomain     = substr( $path, 7 );
		$current_plugin = null;
		foreach ( $plugins as $plugin ) {
			if ( $plugin['TextDomain'] === $textDomain ) {
				$current_plugin = $plugin;
				break;
			}
		}

		// Create the main project for the plugins.
		$main_project = $this->get_or_create_project( 'Plugins', 'local-plugins', 'local-plugins', 'Local Plugins' );

		$this->create_project_and_import_strings(
			$current_plugin['Name'],
			$current_plugin['TextDomain'],
			'local-plugins/' . $current_plugin['TextDomain'],
			$current_plugin['Description'],
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/plugins/' . $current_plugin['TextDomain'] . '-' . $locale->wp_locale . '.po'
		);
	}

	/**
	 * Creates the projects and import the originals and translations for a theme.
	 *
	 * @param string $path The path of the theme.
	 */
	private function translate_theme( string $path ) {
		$locale        = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		$themes        = apply_filters( 'local_glotpress_local_themes', wp_get_themes() );
		$textDomain    = substr( $path, 6 );
		$current_theme = null;
		foreach ( $themes as $theme ) {
			if ( $theme->get( 'TextDomain' ) === $textDomain ) {
				$current_theme = $theme;
				break;
			}
		}

		// Create the main project for the themes.
		$main_project = $this->get_or_create_project( 'Themes', 'local-themes', 'local-themes', 'Local Themes' );

		$this->create_project_and_import_strings(
			$current_theme->get( 'Name' ),
			$current_theme->get( 'TextDomain' ),
			'local-themes/' . $current_theme->get( 'TextDomain' ),
			$current_theme->get( 'Description' ),
			$main_project,
			$locale,
			ABSPATH . '/wp-content/languages/themes/' . $current_theme->get( 'TextDomain' ) . '-' . $locale->wp_locale . '.po'
		);
	}


	/**
	 * Creates a project and import the strings.
	 *
	 * @param string     $project_name The name of the project.
	 * @param string     $project_slug The slug of the project.
	 * @param string     $path The path of the project.
	 * @param string     $project_description The description of the project.
	 * @param GP_Project $parent_project The parent project.
	 * @param GP_Locale  $locale The locale of the project.
	 * @param string     $file_to_import The file to import.
	 */
	private function create_project_and_import_strings( string $project_name, string $project_slug, string $path, string $project_description, GP_Project $parent_project, GP_Locale $locale, string $file_to_import ) {
		$project         = $this->get_or_create_project( $project_name, $project_slug, $path, $project_description, $parent_project );
		$translation_set = $this->get_or_create_translation_set( $project, 'default', $locale );
		$this->get_or_import_originals( $project, $file_to_import );
		$this->get_or_import_translations( $project, $translation_set, $file_to_import );
	}

	/**
	 * Gets or creates a project.
	 *
	 * @param string          $name The name of the project.
	 * @param string          $slug The slug of the project.
	 * @param string          $path The path of the project.
	 * @param string          $description The description of the project.
	 * @param GP_Project|null $parent_project The parent project.
	 *
	 * @return GP_Project
	 */
	private function get_or_create_project( string $name, string $slug, string $path, string $description, GP_Project $parent_project = null ): GP_Project {
		$project = GP::$project->by_path( $path );
		if ( ! $project ) {
			$new_project = new GP_Project(
				array(
					'name'              => $name,
					'slug'              => $slug,
					'description'       => $description,
					'parent_project_id' => $parent_project ? $parent_project->id : null,
					'active'            => true,
				)
			);
			$project     = GP::$project->create_and_select( $new_project );
		}
		return $project;
	}

	/**
	 * Gets or creates a translation set.
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
	 * Gets or imports the originals.
	 *
	 * @param GP_Project $project The project.
	 * @param string     $file    The file to import.
	 *
	 * @return array
	 */
	private function get_or_import_originals( GP_Project $project, string $file ): array {
		if ( ! file_exists( $file ) ) {
			$file = $this->download_dotorg_translation( $project );
			if ( ! file_exists( $file ) ) {
				return array();
			}
		}
		$originals = GP::$original->by_project_id( $project->id );
		if ( ! $originals ) {
			$format    = 'po';
			$format    = gp_array_get( GP::$formats, $format, null );
			$originals = $format->read_originals_from_file( $file, $project );
			$originals = GP::$original->import_for_project( $project, $originals );
		}
		return $originals;
	}

	/**
	 * Gets or imports the translations.
	 *
	 * @param GP_Project         $project         The project.
	 * @param GP_Translation_Set $translation_set The translation set.
	 * @param string             $file            The file to import.
	 *
	 * @return array
	 */
	private function get_or_import_translations( GP_Project $project, GP_Translation_Set $translation_set, string $file ):array {
		if ( ! file_exists( $file ) ) {
			$file = $this->download_dotorg_translation( $project );
			if ( ! file_exists( $file ) ) {
				return array();
			}
		}
		$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		if ( ! $translations ) {
			$po       = new PO();
			$imported = $po->import_from_file( $file );
			$translation_set->import( $po, 'current' );
			$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		}
		return $translations;
	}

	private function get_po_file_path() {

	}

	/**
	 * Downloads the translations from translate.w.org.
	 *
	 * Downloads the .po and .mo files, stores them in the
	 * - wp-content/languages/plugins/ or
	 * - wp-content/languages/themes/
	 *
	 * @param GP_Project $project The project.
	 *
	 * @return string The path to the .po file.
	 */
	private function download_dotorg_translation( GP_Project $project ) {
		$locale       = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		$project_type = '';
		$po_file_url  = '';
		$mo_file_url  = '';
		switch ( strtok( $project->path, '/' ) ) {
			case 'local-plugins':
				// Stable branch.
				$po_file_url  = sprintf(
					'https://translate.wordpress.org/projects/wp-plugins/%s/stable/%s/default/export-translations/?filters%%status%%5D=current_or_waiting_or_fuzzy_or_untranslated',
					$project->slug,
					$locale->slug
				);
				$project_type = 'plugins';
				break;
			case 'local-themes':
				$po_file_url  = sprintf(
					'https://translate.wordpress.org/projects/wp-themes/%s/%s/default/export-translations/?filters%%5Bstatus%%5D=current_or_waiting_or_fuzzy_or_untranslated',
					$project->slug,
					$locale->slug
				);
				$project_type = 'themes';
				break;
			default:
				return '';
		}
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$po_tmp_file = download_url( $po_file_url );
		if ( ! $po_tmp_file ) {
			return '';
		}
		$mo_tmp_file = download_url( $po_file_url . '&format=mo' );
		if ( ! $mo_tmp_file ) {
			return '';
		}

		$po_file_destination = sprintf(
			'%swp-content/languages/%s/%s-%s.po',
			ABSPATH,
			$project_type,
			$project->slug,
			$locale->wp_locale
		);
		$mo_file_destination = sprintf(
			'%swp-content/languages/%s/%s-%s.mo',
			ABSPATH,
			$project_type,
			$project->slug,
			$locale->wp_locale
		);
		rename( $po_tmp_file, $po_file_destination );
		rename( $mo_tmp_file, $mo_file_destination );
		return $po_file_destination;
	}
}
