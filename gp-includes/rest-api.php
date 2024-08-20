<?php
/**
 * GlotPress REST API
 *
 * This implements a REST for GlotPress.
 *
 * @package GlotPress
 */

/**
 * This is the class for the REST API of GlotPress.
 *
 * @since 5.0.0
 *
 * @package GlotPress
 */
class GP_Rest_API {
	const PREFIX = 'glotpress/v1';
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_rest_routes' ) );
	}

	/**
	 * Add the REST API to send and receive friend requests
	 */
	public function add_rest_routes() {
		register_rest_route(
			self::PREFIX,
			'translation',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'save_translation' ),
				'permission_callback' => array( $this, 'logged_in_permission_check' ),
			)
		);
		register_rest_route(
			self::PREFIX,
			'translations-by-originals',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_translations_by_originals' ),
				'permission_callback' => array( $this, 'logged_in_permission_check' ),
			)
		);
		register_rest_route(
			self::PREFIX,
			'glossary-markup',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_glossary_markup' ),
				'permission_callback' => array( $this, 'logged_in_permission_check' ),
			)
		);
		register_rest_route(
			self::PREFIX,
			'create-local-project',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_local_project' ),
				'permission_callback' => array( $this, 'write_projects_permission_check' ),
			)
		);
		register_rest_route(
			self::PREFIX,
			'deploy-local-translations',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'deploy_local_translations' ),
				'permission_callback' => array( $this, 'manage_options_permission_check' ),
			)
		);
	}

	/**
	 * Check whether the user is logged-in.
	 *
	 * @return     bool  Whether the permission check was passed.
	 */
	public function logged_in_permission_check() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You need to be logged in to access this API.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check whether the user can write projects.
	 *
	 * @return     bool  Whether the permission check was passed.
	 */
	public function write_projects_permission_check() {
		if ( ! GP::$permission->current_user_can( 'write', 'project' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You don\'t have enough permission to access this API.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Check whether the user can manage options.
	 *
	 * @return     bool  Whether the permission check was passed.
	 */
	public function manage_options_permission_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You don\'t have enough permission to access this API.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}
		return true;
	}

	/**
	 * Get a glossary marked-up original.
	 *
	 * @param  \WP_REST_Request $request The incoming request.
	 * @return array The array to be returned via the REST API.
	 */
	public function get_glossary_markup( \WP_REST_Request $request ) {
		$glossary = false;
		$original = $request->get_param( 'original' );
		if ( ! isset( $original['singular'] ) ) {
			return new WP_Error(
				'rest_invalid_original',
				__( 'You specified an invalid original.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( ! isset( $original['plural'] ) ) {
			$original['plural'] = '';
		}

		$locale_slug          = $request->get_param( 'locale_slug' );
		$translation_set_slug = $request->get_param( 'translation_set_slug' );

		$locale_glossary_translation_set = GP::$translation_set->by_project_id_slug_and_locale( 0, $translation_set_slug, $locale_slug );
		if ( ! $locale_glossary_translation_set ) {
			return $original;
		}

		$locale_glossary = GP::$glossary->by_set_id( $locale_glossary_translation_set->id );

		$project = GP::$project->by_path( $request->get_param( 'project' ) );
		if ( $project ) {
			$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
			if ( $translation_set ) {
				$glossary = GP::$glossary->by_set_or_parent_project( $translation_set, $project );
			}
		}

		// Return locale glossary if a project has no glossary.
		if ( false === $glossary && $locale_glossary instanceof GP_Glossary ) {
			$glossary = $locale_glossary;
		}

		if ( $glossary instanceof GP_Glossary && $locale_glossary instanceof GP_Glossary && $locale_glossary->id !== $glossary->id ) {
			$glossary->merge_with_glossary( $locale_glossary );
		}

		if ( ! $glossary ) {
			return $original;
		}

		$original = map_glossary_entries_to_translation_originals( (object) $original, $glossary );
		return $original;
	}

	/**
	 * Save a translation.
	 *
	 * @param  \WP_REST_Request $request The incoming request.
	 * @return array The array to be returned via the REST API.
	 */
	public function save_translation( \WP_REST_Request $request ) {
		$project_path         = $request->get_param( 'project' );
		$locale_slug          = $request->get_param( 'locale_slug' );
		$translation_set_slug = $request->get_param( 'translation_set_slug' );
		if ( ! $translation_set_slug ) {
			$translation_set_slug = 'default';
		}
		$translation = $request->get_param( 'translation' );

		$project = GP::$project->by_path( $project_path );
		if ( ! $project ) {
			return new WP_Error(
				'rest_invalid_project',
				__( 'You specified an invalid project.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$locale = GP_Locales::by_slug( $locale_slug );
		if ( ! $locale ) {
			return new WP_Error(
				'rest_invalid_locale',
				__( 'You specified an invalid locale.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
		if ( ! $translation_set ) {
			return new WP_Error(
				'rest_invalid_translation_set',
				__( 'You specified an invalid translation set.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$output = array();
		foreach ( $request->get_param( 'translation', array() ) as $original_id => $translations ) {
			$original = GP::$original->get( $original_id );
			if ( ! $original || $original->project_id !== $project->id ) {
				return new WP_Error(
					'rest_invalid_original',
					__( 'You specified an invalid original.', 'glotpress' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}

			$data = array(
				'original_id'        => $original->id,
				'user_id'            => get_current_user_id(),
				'translation_set_id' => $translation_set->id,
			);

			foreach ( range( 0, GP::$translation->get_static( 'number_of_plural_translations' ) ) as $i ) {
				if ( isset( $translations[ $i ] ) ) {
					$data[ "translation_$i" ] = $translations[ $i ];
				}
			}

			$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );

			if ( empty( $data['warnings'] ) && ( GP::$permission->current_user_can( 'approve', 'translation-set', $translation_set->id ) || GP::$permission->current_user_can( 'write', 'project', $project->id ) ) ) {
				$data['status'] = 'current';
			} else {
				$data['status'] = 'waiting';
			}

			$existing_translations = GP::$translation->for_translation(
				$project,
				$translation_set,
				'no-limit',
				array(
					'original_id' => $original_id,
					'status'      => 'current_or_waiting',
				),
				array()
			);
			foreach ( $existing_translations as $e ) {
				if ( array_pad( $translations, $locale->nplurals, null ) === $e->translations ) {
					return new WP_Error(
						'rest_invalid_translation',
						__( 'Identical current or waiting translation already exists.', 'glotpress' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
			}

			$translation = GP::$translation->create( $data );
			if ( ! $translation->validate() ) {
				$error_output = $translation->errors;
				$translation->delete();
				return new WP_Error(
					'rest_invalid_translation',
					$error_output,
					array( 'status' => rest_authorization_required_code() )
				);
			}

			if ( 'current' === $data['status'] ) {
				$translation->set_status( 'current' );
			}

			gp_clean_translation_set_cache( $translation_set->id );
			$translations = GP::$translation->find_many_no_map( "original_id = '{$original_id}' AND translation_set_id = '{$translation_set->id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )", 'date_modified DESC' );
			if ( ! $translations ) {
				$output[ $original_id ] = false;
				continue;
			}
			foreach ( array_keys( $translations ) as $translation_id ) {
				if ( ! empty( $translations[ $translation_id ]->warnings ) ) {
					$translations[ $translation_id ]->warnings = wp_json_encode(
						unserialize( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
							$translations[ $translation_id ]->warnings
						)
					);
				}
			}
			$output[ $original_id ] = $translations;
		}

		return $output;
	}

	/**
	 * Gets translations by title.
	 *
	 * @param  \WP_REST_Request $request The incoming request.
	 * @return array The array to be returned via the REST API.
	 */
	public function get_translations_by_originals( \WP_REST_Request $request ) {
		$projects             = $request->get_param( 'projects' );
		$locale_slug          = $request->get_param( 'locale_slug' );
		$translation_set_slug = $request->get_param( 'translation_set_slug' );
		if ( ! $translation_set_slug ) {
			$translation_set_slug = 'default';
		}
		$original_strings = json_decode( $request->get_param( 'original_strings' ) );

		if ( ! $projects || ! $locale_slug || ! $translation_set_slug || ! $original_strings ) {
			return new WP_Error(
				'rest_missing_parameters',
				__( "You didn't provide all required parameters.", 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$translations     = array();
		$project_paths    = array();
		$translation_sets = array();
		foreach ( $projects as $text_domain => $paths ) {
			foreach ( $paths as $project_path ) {
				$project = GP::$project->by_path( $project_path );
				if ( ! $project ) {
					continue;
				}
				$project_paths[ $project->id ] = $project_path;
				if ( ! isset( $translation_sets[ $text_domain ] ) ) {
					$translation_sets[ $text_domain ] = array();
				}
				if ( isset( $translation_sets[ $text_domain ][ $project->id ] ) ) {
					continue;
				}
				$translation_set = GP::$translation_set->by_project_id_slug_and_locale( $project->id, $translation_set_slug, $locale_slug );
				if ( ! $translation_set ) {
					continue;
				}

				$translation_sets[ $text_domain ][ $project->id ] = $translation_set;
			}
		}

		if ( empty( $translation_sets ) ) {
			return array();
		}

		$checked_originals = array();
		foreach ( $original_strings as $original ) {
			if ( empty( $original ) || ! property_exists( $original, 'singular' ) ) {
				continue;
			}

			if ( ! property_exists( $original, 'domain' ) || ! isset( $translation_sets[ $original->domain ] ) ) {
				$translations['originals_not_found'][] = $original;
				continue;
			}
			$text_domain = $original->domain;

			$contexts = array( false );
			if ( property_exists( $original, 'context' ) && $original->context ) {
				if ( is_array( $original->context ) ) {
					$contexts = $original->context;
				} else {
					$contexts = array( $original->context );
				}
			}

			foreach ( $contexts as $context ) {
				$key = $original->singular;
				if ( $context ) {
					$original->context = $context;
					$key               = $original->context . '\u0004' . $key;
				} else {
					unset( $original->context );
				}

				if ( isset( $checked_originals[ $key ] ) ) {
					continue;
				}
				$checked_originals[ $key ] = true;

				foreach ( $translation_sets[ $text_domain ] as $project_id => $translation_set ) {
					$original_record = GP::$original->by_project_id_and_entry( $project_id, $original );
					if ( ! $original_record ) {
						continue;
					}

					$query_result                   = new stdClass();
					$query_result->original_id      = $original_record->id;
					$query_result->original         = $original;
					$query_result->original_comment = $original_record->comment;
					$query_result->project          = $project_paths[ $project_id ];

					$query_result->translations = GP::$translation->find_many_no_map( "original_id = '{$query_result->original_id}' AND translation_set_id = '{$translation_set->id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )", 'date_modified DESC' );
					foreach ( $query_result->translations as $key => $current_translation ) {
						$query_result->translations[ $key ]                   = GP::$translation->prepare_fields_for_save( $current_translation );
						$query_result->translations[ $key ]['translation_id'] = $current_translation->id;
					}

					$translations[] = $query_result;
					continue 2;
				}

				$translations['originals_not_found'][] = $original;
			}
		}

		return $translations;
	}

	/**
	 * Deploy translations locally.
	 *
	 * @param      WP_REST_Request $request  The request.
	 *
	 * @return     array  The result.
	 */
	public function deploy_local_translations( $request ) {
		$path = $request->get_param( 'path' );
		if ( ! $path ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: path', array( 'status' => 400 ) );
		}
		$project = GP::$project->by_path( apply_filters( 'gp_local_project_path', $path ) );
		if ( ! $project ) {
			return new WP_Error( 'inexistent-project', 'Project does not exist', array( 'status' => 400 ) );
		}

		$locale = $request->get_param( 'locale' );
		if ( ! $locale ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: locale', array( 'status' => 400 ) );
		}
		$locale_slug = $request->get_param( 'locale_slug' );
		if ( ! $locale_slug ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: locale_slug', array( 'status' => 400 ) );
		}

		$locale = GP_Locales::by_field( 'wp_locale', $locale );
		if ( ! $locale ) {
			return new WP_Error( 'invalid-locale', 'Invalid locale supplied', array( 'status' => 400 ) );
		}

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale(
			$project->id,
			$locale_slug,
			$locale->slug
		);
		if ( ! $translation_set ) {
			return new WP_Error( 'inexistent-translation-set', 'Translation Set does not exist', array( 'status' => 400 ) );
		}

		$languages_dir = trailingslashit( WP_CONTENT_DIR ) . 'languages/';

		$local_po = apply_filters( 'gp_local_project_pomo_base', $languages_dir . basename( $path ) . '-' . $locale->wp_locale, $path, $locale_slug, $locale, $languages_dir ) . '.po';

		$filters = array(
			'status' => 'current',
		);
		$entries = GP::$translation->for_export( $project, $translation_set, $filters );

		if ( 'pages/page-' === substr( $path, 0, 11 ) ) {
			$translations_deployed = $this->deploy_page( $path, $project, $locale, $translation_set );
			return array(
				'message' => sprintf(
				/* translators: %s: Number of entries deployed. */
					_n( '%d translation was deployed.', '%d translations were deployed.', $translations_deployed, 'glotpress' ),
					$translations_deployed
				),
			);
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Check if WP_Filesystem is initialized.
		if ( ! WP_Filesystem( true ) ) {
			return new WP_Error( 'error-writing', 'Could not write the file ' . $local_po, array( 'status' => 400 ) );
		}

		// Write the file using WP_Filesystem methods.
		$wp_filesystem->put_contents( $local_po, GP::$formats['po']->print_exported_file( $project, $locale, $translation_set, $entries ) );

		if ( substr( $local_po, -2 ) === 'po' ) {
			$local_mo = substr( $local_po, 0, -2 ) . 'mo';
			$wp_filesystem->put_contents( $local_mo, GP::$formats['mo']->print_exported_file( $project, $locale, $translation_set, $entries ) );
		}

		return array(
			'message' => sprintf(
				/* translators: %s: Number of entries deployed. */
				_n( '%d translation was deployed.', '%d translations were deployed.', count( $entries ), 'glotpress' ),
				count( $entries )
			),
		);

	}
	/**
	 * Creates a Local Project.
	 *
	 * @param      WP_REST_Request $request  The request.
	 *
	 * @return     array  The result.
	 */
	public function create_local_project( $request ) {
		$path               = $request->get_param( 'path' );
		$originals_added    = false;
		$translations_added = false;
		if ( ! $path ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: path', array( 'status' => 400 ) );
		}
		$project_name = $request->get_param( 'name' );
		if ( ! $project_name ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: name', array( 'status' => 400 ) );
		}
		$project_description = $request->get_param( 'description' );
		if ( ! $project_description ) {
			$project_description = '';
		}
		$locale = $request->get_param( 'locale' );
		if ( ! $locale ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: locale', array( 'status' => 400 ) );
		}
		$locale_slug = $request->get_param( 'locale_slug' );
		if ( ! $locale_slug ) {
			return new WP_Error( 'missing-parameter', 'Missing parameter: locale_slug', array( 'status' => 400 ) );
		}

		$locale = GP_Locales::by_field( 'wp_locale', $locale );
		if ( ! $locale ) {
			return new WP_Error( 'invalid-locale', 'Invalid locale supplied', array( 'status' => 400 ) );
		}
		$messages = array();

		$project = $this->get_or_create_project( $project_name, apply_filters( 'gp_local_project_path', $path ), $project_description );

		$translation_set = GP::$translation_set->by_project_id_slug_and_locale(
			$project->id,
			$locale_slug,
			$locale->slug
		);

		if ( ! $translation_set ) {
			$new_set         = new GP_Translation_Set(
				array(
					'name'       => $locale->english_name,
					'slug'       => $locale_slug,
					'project_id' => $project->id,
					'locale'     => $locale->slug,
				)
			);
			$translation_set = GP::$translation_set->create_and_select( $new_set );
			$messages[]      = __( 'Translation set was created.', 'glotpress' );
		}

		if ( 'pages/page-' === substr( $path, 0, 11 ) ) {
			list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = $this->import_page( $project, $path );
			$messages[] = sprintf(
			/* Translators: 1: Added strings count. 2: Updated strings count. 3: Fuzzied strings count. 4: Obsoleted strings count. 5: Error string count. */
				__( '%1$d new strings added, %2$d updated, %3$d fuzzied, %4$d obsoleted and %5$d with error.', 'glotpress' ),
				$originals_added,
				$originals_existing,
				$originals_fuzzied,
				$originals_obsoleted,
				$originals_error
			);
		} else {
			list($messages_added, $originals_added, $translations_added) = $this->import_mo_file( $path, $locale, $locale_slug, $translation_set, $project, $messages );
			$messages = array_merge( $messages, $messages_added );
		}

		return array(
			'project'            => $project->id,
			'translation_set'    => $translation_set->id,
			'url'                => gp_url( 'projects/' . $project->path . '/' . $translation_set->locale . '/' . $translation_set->slug ),
			'originals_added'    => $originals_added,
			'translations_added' => $translations_added,
			'messages'           => $messages,
		);
	}

	/**
	 * Gets or creates a project.
	 *
	 * @param string $name The name of the project.
	 * @param string $path The path of the project. The last element of the path is the slug.
	 * @param string $description The description of the project.
	 *
	 * @return GP_Project
	 */
	private function get_or_create_project( string $name, string $path, string $description ): GP_Project {
		$project = GP::$project->by_path( $path );

		if ( ! $project ) {
			$path_separator = '';
			$project_path   = '';
			$parent_project = null;
			$path_snippets  = explode( '/', $path );
			$project_slug   = array_pop( $path_snippets );
			foreach ( $path_snippets as $slug ) {
				$project_path  .= $path_separator . $slug;
				$path_separator = '/';
				$project        = GP::$project->by_path( $project_path );
				if ( ! $project ) {
					$new_project = new GP_Project(
						array(
							'name'              => GP::$local->get_project_name( $project_path ),
							'slug'              => $slug,
							'path'              => $project_path,
							'description'       => GP::$local->get_project_description( $project_path ),
							'parent_project_id' => $parent_project ? $parent_project->id : null,
							'active'            => true,
						)
					);
					$project     = GP::$project->create_and_select( $new_project );
				}
				$parent_project = $project;
			}

			$new_project = new GP_Project(
				array(
					'name'              => $name,
					'slug'              => $project_slug,
					'path'              => $path,
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
	 * Gets or imports the originals.
	 *
	 * @param GP_Project $project   The project.
	 * @param string     $local_mo The file to import.
	 *
	 * @return array
	 */
	private function get_or_import_originals( GP_Project $project, string $local_mo ): array {
		$format    = 'mo';
		$format    = gp_array_get( GP::$formats, $format, null );
		$originals = $format->read_originals_from_file( $local_mo, $project );
		$originals = GP::$original->import_for_project( $project, $originals );

		return $originals;
	}

	/**
	 * Gets or imports the translations.
	 *
	 * @param GP_Project         $project         The project.
	 * @param GP_Translation_Set $translation_set The translation set.
	 * @param string             $local_mo       The file path to import.
	 *
	 * @return array
	 */
	private function get_or_import_translations( GP_Project $project, GP_Translation_Set $translation_set, string $local_mo ):int {
		$mo = new MO();
		$mo->import_from_file( $local_mo );

		add_filter( 'gp_translation_prepare_for_save', array( $this, 'translation_import_overrides' ) );
		return $translation_set->import( $mo, 'current' );
	}

	/**
	 * Override the saved translation.
	 *
	 * @param      array $fields   The fields.
	 *
	 * @return     array  The updated fields.
	 */
	public function translation_import_overrides( $fields ) {
		// Discard warnings of current strings upon import.
		if ( ! empty( $fields['warnings'] ) ) {
			unset( $fields['warnings'] );
			$fields['status'] = 'current';
		}

		// Don't set the user id upon import so that we can later identify translations by users.
		unset( $fields['user_id'] );

		return $fields;
	}


	/**
	 * Downloads the translations from translate.w.org.
	 *
	 * Downloads the .po and .mo files, stores them in the
	 * - wp-content/languages/plugins/ or
	 * - wp-content/languages/themes/
	 *
	 * @param GP_Project $project The project.
	 * @param GP_Locale  $locale  The locale.
	 * @param string     $locale_slug The locale slug.
	 * @param string     $local_mo The .mo file path.
	 *
	 * @return bool|WP_Error The path to the .po file.
	 */
	private function download_dotorg_translation( GP_Project $project, GP_Locale $locale, string $locale_slug, string $local_mo ) {
		$remote_path = apply_filters( 'gp_remote_project_path', $project->path . '/dev/' . $locale->slug . '/' . $locale_slug . '/' );

		$url         = apply_filters( 'gp_local_sync_url', 'https://translate.wordpress.org/projects/' . $remote_path, $remote_path );
		$mo_file_url = $url . 'export-translations?format=mo';

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$mo_file = download_url( $mo_file_url );
		if ( is_wp_error( $mo_file ) ) {
			return $mo_file;
		} elseif ( ! $mo_file ) {
			return new WP_Error(
				'download_failed',
				sprintf(
					// translators: %s is a URL.
					__( 'Failed to download the translation file from %s', 'glotpress' ),
					$mo_file_url
				)
			);
		}
		rename( $mo_file, $local_mo );
		return $mo_file_url;
	}

	/**
	 * Import a page.
	 *
	 * @param GP_Project $project The project.
	 * @param string     $path    The path.
	 *
	 * @return array The number of added, existing, fuzzied, obsoleted and error strings.
	 */
	private function import_page( GP_Project $project, string $path ): array {
		$page_id     = str_replace( 'pages/page-', '', $path );
		$page        = get_post( $page_id );
		$page_blocks = array();
		if ( has_blocks( $page->post_content ) ) {
			$blocks      = parse_blocks( $page->post_content );
			$page_blocks = $this->get_content_from_blocks( $blocks );
		}
		$page_blocks = array_merge( array( $page->post_title ), $page_blocks );
		$po_object   = $this->get_po_object( $page_blocks );
		return GP::$original->import_for_project( $project, $po_object );
	}

	/**
	 * Get only the content from blocks.
	 *
	 * @param array $blocks The blocks.
	 *
	 * @return array The content.
	 */
	private function get_content_from_blocks( array $blocks ): array {
		$page_blocks = array();
		foreach ( $blocks as $block ) {
			if ( ! $this->is_empty_block( $block ) ) {
				if ( ! empty( $block['innerHTML'] ) ) {
					$page_blocks[] = str_replace( array( "\n", "\r", "\t" ), '', $block['innerHTML'] );
				} elseif ( ! empty( $block['innerContent'] ) ) {
					$page_blocks[] = $block['innerContent'];
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$page_blocks = array_merge( $page_blocks, $this->get_content_from_blocks( $block['innerBlocks'] ) );
			}
		}
		return $page_blocks;
	}

	/**
	 * Check if a block is empty: only has new lines, spaces, tabs, HTML tags, etc.
	 *
	 * @param array $block The block to check.
	 *
	 * @return bool
	 */
	private function is_empty_block( array $block ): bool {
		$block_content = str_replace( array( "\n", "\r", "\t" ), '', $block['innerHTML'] );
		$block_content = wp_strip_all_tags( $block_content );
		return '' === $block_content;
	}

	/**
	 * @param string             $path            The type of project and its name, in URL format.
	 * @param GP_Locale          $locale          The locale.
	 * @param string             $locale_slug     The locale slug.
	 * @param GP_Translation_Set $translation_set The translation set.
	 * @param GP_Project         $project         The project.
	 *
	 * @return array
	 */
	private function import_mo_file( string $path, GP_Locale $locale, string $locale_slug, GP_Translation_Set $translation_set, GP_Project $project ): array {
		$messages      = array();
		$languages_dir = trailingslashit( WP_CONTENT_DIR ) . 'languages/';

		$local_mo = apply_filters( 'gp_local_project_pomo_base', $languages_dir . basename( $path ) . '-' . $locale->wp_locale, $path, $locale_slug, $locale, $languages_dir ) . '.mo';
		if ( ! file_exists( $local_mo ) || $translation_set ) {
			$downloaded = $this->download_dotorg_translation( $project, $locale, $locale_slug, $local_mo );
			if ( is_wp_error( $downloaded ) ) {
				$messages[] = $downloaded->get_error_message();
			} else {
				$messages[] = make_clickable(
					sprintf(
					// translators: %s is a URL.
						__( 'Downloaded MO file from %s', 'glotpress' ),
						$downloaded
					)
				);
			}
		}

		$originals_added    = false;
		$translations_added = false;
		if ( file_exists( $local_mo ) ) {
			list($originals_added) = $this->get_or_import_originals( $project, $local_mo );
			$messages[]            = sprintf(
			// translators: %s is the number of originals.
				_n( 'Imported %s original.', 'Imported %s originals.', $originals_added, 'glotpress' ),
				$originals_added
			);

			$translations_added = $this->get_or_import_translations( $project, $translation_set, $local_mo );
			$messages[]         = sprintf(
			// translators: %s is the number of translations.
				_n( 'Imported new %s translation.', 'Imported new %s translations.', $translations_added, 'glotpress' ),
				$translations_added
			);
		}
		return array( $messages, $originals_added, $translations_added );
	}

	/**
	 * Get a PO object.
	 *
	 * @param array $page_blocks The page blocks.
	 *
	 * @return PO The PO object.
	 */
	private function get_po_object( array $page_blocks ): PO {
		$po          = new PO();
		$po->entries = array();
		foreach ( $page_blocks as $block ) {
			$entry                           = new Translation_Entry();
			$entry->singular                 = $block;
			$entry->status                   = '+active';
			$po->entries[ $entry->singular ] = $entry;
		}
		return $po;
	}

	/**
	 * Deploy a translated page.
	 *
	 * @param string             $path            The path.
	 * @param GP_Project         $project         The project.
	 * @param GP_Locale          $locale          The locale.
	 * @param GP_Translation_Set $translation_set The translation set.
	 *
	 * @return int The number of translations deployed.
	 */
	private function deploy_page( string $path, GP_Project $project, GP_Locale $locale, GP_Translation_Set $translation_set ): int {
		$original_page_id      = str_replace( 'pages/page-', '', $path );
		$translated_page       = $this->get_translated_page( $original_page_id, $locale );
		$translations_deployed = 0;
		$translated_page_id    = $translated_page->ID;
		if ( ! $translated_page_id ) {
			$translated_page_id = $this->create_translated_page( $original_page_id, $locale );
		}
		if ( $translated_page_id ) {
			$original_page     = get_post( $original_page_id );
			$translated_blocks = array();
			if ( has_blocks( $original_page->post_content ) ) {
				$original_blocks  = parse_blocks( $original_page->post_content );
				$project_id       = $project->id;
				$original_strings = GP::$original->by_project_id( $project_id );
				foreach ( $original_blocks as $block ) {
					$block_translated    = $this->get_block_translated( $block, $original_strings, $translation_set );
					$translated_blocks[] = $block_translated;
					if ( $block === $block_translated ) {
						$translations_deployed++;
					}
				}
				wp_update_post(
					wp_slash(
						array(
							'ID'           => $translated_page_id,
							'post_title'   => $this->get_title_translated( $original_page->post_title, $original_strings, $translation_set ),
							'post_content' => serialize_blocks( $translated_blocks ),
						)
					),
					false,
					false
				);
			}
		}
		return $translations_deployed;
	}

	/**
	 * Get the translated page.
	 *
	 * @param int       $original_page_id The original page ID from the non-translated page.
	 * @param GP_Locale $locale          The locale used in the translated page.
	 *
	 * @return false|WP_Post The translated page.
	 */
	private function get_translated_page( int $original_page_id, GP_Locale $locale ) {
		$args = array(
			'meta_query'     => array(
				array(
					'key'     => '_original_page_id',
					'value'   => $original_page_id,
					'compare' => '=',
				),
				array(
					'key'     => '_locale',
					'value'   => $locale->wp_locale,
					'compare' => '=',
				),
			),
			'post_type'      => 'any',
			'posts_per_page' => 1,
		);

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			return $query->posts[0];
		}

		return false;
	}

	/**
	 * Create a new page to store the translated content.
	 *
	 * @param int       $original_page_id The original page ID from the non-translated page.
	 * @param GP_Locale $locale          The locale used in the translated page.
	 * @param array     $page_data        The page extra data.
	 *
	 * @return false|int|WP_Error
	 */
	private function create_translated_page( int $original_page_id, GP_Locale $locale, $page_data = array() ) {
		$default_page_data = array(
			'post_title'   => 'Translated Page - ' . $locale,
			'post_content' => '',
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);

		$post_data   = wp_parse_args( $page_data, $default_page_data );
		$new_post_id = wp_insert_post( $post_data );

		if ( $new_post_id && ! is_wp_error( $new_post_id ) ) {
			update_post_meta( $new_post_id, '_original_page_id', $original_page_id );
			update_post_meta( $new_post_id, '_locale', $locale->wp_locale );
			return $new_post_id;
		}

		return false;
	}

	/**
	 * Return the translated block, if it exists.
	 *
	 * @param array              $block            The block.
	 * @param array              $original_strings The original strings.
	 * @param GP_Translation_Set $translation_set  The translation set.
	 *
	 * @return array The translated block.
	 */
	private function get_block_translated( array $block, array $original_strings, GP_Translation_Set $translation_set ): array {
		foreach ( $original_strings as $original ) {
			$block_cleaned = str_replace( array( "\n", "\r", "\t" ), '', $block['innerHTML'] );
			if ( $block_cleaned === $original->singular ) {
				$translation = GP::$translation->find_one(
					array(
						'status'             => 'current',
						'original_id'        => $original->id,
						'translation_set_id' => $translation_set->id,
					)
				);
				if ( ! isset( $block['attrs']['className'] ) ) {
					$block['attrs']['className'] = '';
				}
				$block['attrs']['className'] .= ' translator-checked translator-original-' . $original->id;
				if ( $translation ) {
					$block['innerHTML']              = $translation->translation_0;
					$block['innerContent'][0]        = $translation->translation_0;
					$block['attrs']['translationId'] = $translation->id;
					$block['attrs']['className']    .= ' translator-translatable translator-translated';
				} else {
					$block['attrs']['className'] .= ' translator-translatable translator-untranslated';
				}
				$block['attrs']['originalId'] = $original->id;

				$block['innerHTML'] = preg_replace_callback(
					'/<(\w+)([^>]*)class="([^"]*)"([^>]*)>/',
					function( $matches ) use ( $block ) {
						$tag_name         = $matches[1]; // The tag name (e.g., p, div, etc.).
						$pre_class_attrs  = $matches[2]; // Attributes before class.
						$existing_classes = $matches[3]; // The existing class values.
						$post_class_attrs = $matches[4]; // Attributes after class.

						// Combine existing class with new classes.
						$new_class_value = trim( $existing_classes . ' ' . $block['attrs']['className'] );

						// Return the rebuilt tag with the new class value.
						return "<{$tag_name}{$pre_class_attrs}class=\"{$new_class_value}\"{$post_class_attrs}>";
					},
					$block['innerHTML']
				);

				// Handle tags without an existing class attribute.
				$block['innerHTML'] = preg_replace(
					'/<(\w+)([^>]*)>/',
					'<$1$2 class="' . esc_attr( $block['attrs']['className'] ) . '">',
					$block['innerHTML']
				);

				$block['innerContent'][0] = $block['innerHTML'];

				break;
			}
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$new_inner_blocks = array();
			foreach ( $block['innerBlocks'] as $nested_block ) {
				$new_inner_blocks[] = $this->get_block_translated( $nested_block, $original_strings, $translation_set );
			}
			$block['innerBlocks'] = $new_inner_blocks;
		}
		return $block;
	}

	/**
	 * Get the title translated.
	 *
	 * @param string             $original_title   The original title.
	 * @param array              $original_strings The original strings.
	 * @param GP_Translation_Set $translation_set  The translation set.
	 *
	 * @return string The translated title.
	 */
	private function get_title_translated( string $original_title, array $original_strings, GP_Translation_Set $translation_set ): string {
		$translated_title = $original_title;
		foreach ( $original_strings as $original ) {
			$title_cleaned = str_replace( array( "\n", "\r", "\t" ), '', $original_title );
			if ( $title_cleaned === $original->singular ) {
				$translation = GP::$translation->find_one(
					array(
						'status'             => 'current',
						'original_id'        => $original->id,
						'translation_set_id' => $translation_set->id,
					)
				);
				if ( $translation ) {
					$translated_title = $translation->translation_0;
				}
				break;
			}
		}
		return $translated_title;
	}
}
