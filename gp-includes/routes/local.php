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
	 * Imports the originals and translations for the WordPress core, a plugin or a theme.
	 *
	 * @param string $slug The slug of the plugin or theme.
	 */
	public function import( string $slug ) {
		$this->security_checks( $slug );
		$locale = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		switch ( strtok( $slug, '/' ) ) {
			case 'core':
				$this->translate_core( $slug );
				$this->redirect(
					gp_url(
						sprintf(
							'/projects/local-wordpress-core/local-wordpress-core-%s/%s/default',
							basename( $slug ),
							$locale->slug
						)
					)
				);
				break;
			case 'plugin':
				$this->translate_plugin( $slug );
				$this->redirect(
					gp_url(
						sprintf(
							'/projects/local-plugins/%s/%s/default',
							substr( $slug, 7 ),
							$locale->slug,
						)
					)
				);
				break;
			case 'theme':
				$this->translate_theme( $slug );
				$this->redirect(
					gp_url(
						sprintf(
							'/projects/local-themes/%s/%s/default',
							substr( $slug, 6 ),
							$locale->slug,
						)
					)
				);
				break;
			default:
				wp_die(
					wp_kses(
						sprintf(
							/* translators: %s: URL to the local projects page. */
							__( 'We can\'t find this project. <a href="%s">Continue</a>.', 'glotpress' ),
							admin_url( 'admin.php?page=glotpress-local-projects' )
						),
						array(
							'a' => array(
								'href' => array(),
							),
						)
					)
				);
		}
	}

	/**
	 * Checks the nonce and the permissions.
	 *
	 * @param string $slug The slug of the core, plugin or theme.
	 */
	private function security_checks( string $slug ) {
		$element_to_check = 'gp-local-' . basename( dirname( $slug ) ) . '-' . basename( $slug );
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], $element_to_check ) ) {
			wp_die( esc_html__( 'Your nonce could not be verified.', 'glotpress' ) );
		}
		if ( ! $this->can( 'write', 'project', null ) ) {
			wp_die( esc_html__( 'You are not allowed to do that!', 'glotpress' ) );
		}
	}

	/**
	 * Creates the projects and import the originals and translations for the WordPress core.
	 *
	 *  The project name and project description are translatables, so when the project is created will
	 *  be created in the current locale if these strings are translated.
	 *
	 * @param string $slug The slug of the core.
	 */
	private function translate_core( string $slug ) {
		$locale = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		// Create the main project for the WordPress core.
		$main_project = $this->get_or_create_project( 'WordPress', 'local-wordpress-core', 'local-wordpress-core', 'Local WordPress Core Translation' );

		switch ( basename( $slug ) ) {
			case 'development':
				$this->create_project_and_import_strings(
					esc_html__( 'Development', 'glotpress' ),
					'local-wordpress-core-development',
					'local-wordpress-core/local-wordpress-core-development',
					esc_html__( 'WordPress Development. Strings from the main project.', 'glotpress' ),
					$main_project,
					$locale,
					ABSPATH . '/wp-content/languages/' . $locale->wp_locale . '.po'
				);
				break;
			case 'continents-cities':
				$this->create_project_and_import_strings(
					esc_html__( 'Continents & Cities', 'glotpress' ),
					'local-wordpress-core-continents-cities',
					'local-wordpress-core/local-wordpress-core-continents-cities',
					esc_html__( 'WordPress Continents & Cities. List with the continents and main cities around the world.', 'glotpress' ),
					$main_project,
					$locale,
					ABSPATH . '/wp-content/languages/continents-cities-' . $locale->wp_locale . '.po'
				);
				break;
			case 'administration':
				$this->create_project_and_import_strings(
					esc_html__( 'Administration', 'glotpress' ),
					'local-wordpress-core-administration',
					'local-wordpress-core/local-wordpress-core-administration',
					esc_html__( 'WordPress Administration. Strings from the WordPress administration.', 'glotpress' ),
					$main_project,
					$locale,
					ABSPATH . '/wp-content/languages/admin-' . $locale->wp_locale . '.po'
				);
				break;
			case 'network-admin':
				$this->create_project_and_import_strings(
					esc_html__( 'Network Admin', 'glotpress' ),
					'local-wordpress-core-network-admin',
					'local-wordpress-core/local-wordpress-core-network-admin',
					esc_html__( 'WordPress Network Administration. Strings from the WordPress network administration.', 'glotpress' ),
					$main_project,
					$locale,
					ABSPATH . '/wp-content/languages/admin-network-' . $locale->wp_locale . '.po'
				);
				break;
		}
		// Create the subprojects for the WordPress core.
	}

	/**
	 * Creates the projects and import the originals and translations for a plugin.
	 *
	 * @param string $slug The slug of the plugin.
	 */
	private function translate_plugin( string $slug ) {
		$locale = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugins        = apply_filters( 'local_glotpress_local_plugins', get_plugins() );
		$textDomain     = substr( $slug, 7 );
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
	 * @param string $slug The slug of the theme.
	 */
	private function translate_theme( string $slug ) {
		$locale        = GP_Locales::by_field( 'wp_locale', get_user_locale() );
		$themes        = apply_filters( 'local_glotpress_local_themes', wp_get_themes() );
		$textDomain    = substr( $slug, 6 );
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
	 * @param GP_Project $project   The project.
	 * @param string     $file_path The file to import.
	 *
	 * @return array
	 */
	private function get_or_import_originals( GP_Project $project, string $file_path ): array {
		$file_path = $this->get_po_file_path( $project, $file_path );

		$originals = GP::$original->by_project_id( $project->id );
		if ( ! $originals ) {
			$format    = 'po';
			$format    = gp_array_get( GP::$formats, $format, null );
			$originals = $format->read_originals_from_file( $file_path, $project );
			$originals = GP::$original->import_for_project( $project, $originals );
		}
		return $originals;
	}

	/**
	 * Gets or imports the translations.
	 *
	 * @param GP_Project         $project         The project.
	 * @param GP_Translation_Set $translation_set The translation set.
	 * @param string             $file_path       The file path to import.
	 *
	 * @return array
	 */
	private function get_or_import_translations( GP_Project $project, GP_Translation_Set $translation_set, string $file_path ):array {
		$file_path    = $this->get_po_file_path( $project, $file_path );
		$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		if ( ! $translations ) {
			$po       = new PO();
			$imported = $po->import_from_file( $file_path );
			$translation_set->import( $po, 'current' );
			$translations = GP::$translation->for_export( $project, $translation_set, array( 'status' => 'current' ) );
		}
		return $translations;
	}

	/**
	 * Gets the path to the .po file.
	 *
	 * Checks if the file exists in the WordPress "languages" folder.
	 * If not, downloads it from translate.w.org.
	 * If not, gets the translation from the project "languages" folder.
	 * If not, returns an empty string.
	 *
	 * @param GP_Project $project   The project.
	 * @param string     $file_path The file to import.
	 *
	 * @return string The path to the .po file.
	 */
	private function get_po_file_path( GP_Project $project, string $file_path ): string {
		if ( ! file_exists( $file_path ) ) {
			$file_path = $this->download_dotorg_translation( $project );
			if ( ! file_exists( $file_path ) ) {
				$file_path = $this->get_translation_file_path_from_project( $project );
				if ( ! file_exists( $file_path ) ) {
					wp_die( esc_html__( 'We can\'t get any translation file for this project.', 'glotpress' ) );
				}
			}
		}
		return $file_path;
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
	private function download_dotorg_translation( GP_Project $project ): string {
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
		if ( ! $po_tmp_file || is_wp_error( $po_tmp_file ) ) {
			return '';
		}
		$mo_tmp_file = download_url( $po_file_url . '&format=mo' );
		if ( ! $mo_tmp_file || is_wp_error( $po_tmp_file ) ) {
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
		// Move the .po and .mo files to the WordPress "languages" folder.
		rename( $po_tmp_file, $po_file_destination );
		rename( $mo_tmp_file, $mo_file_destination );
		return $po_file_destination;
	}

	/**
	 * Gets the path to the .po file from the project.
	 *
	 * @param GP_Project $project The project.
	 *
	 * @return string The path to the .po file.
	 */
	private function get_translation_file_path_from_project( GP_Project $project ): string {
		$project_type = '';
		$locale       = GP_Locales::by_field( 'wp_locale', get_user_locale() );

		switch ( strtok( $project->path, '/' ) ) {
			case 'local-plugins':
				$project_type = 'plugins';
				break;
			case 'local-themes':
				$project_type = 'themes';
				break;
		}

		if ( ! $project_type ) {
			return '';
		}
		return sprintf(
			'%swp-content/%s/%s/languages/%s-%s.po',
			ABSPATH,
			$project_type,
			$project->slug,
			$project->slug,
			$locale->wp_locale
		);
	}
}
