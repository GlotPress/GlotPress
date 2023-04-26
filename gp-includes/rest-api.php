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
			'suggest-translation',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_suggested_translation' ),
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
			$translations = GP::$translation->find_many_no_map( "original_id = '{$original_id}' AND translation_set_id = '{$translation_set->id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )" );
			if ( ! $translations ) {
				$output[ $original_id ] = false;
				continue;
			}
			if ( ! empty( $translations['warnings'] ) ) {
				$translations['warnings'] = wp_json_encode( $translations['warnings'] );
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

					$query_result->translations = GP::$translation->find_many_no_map( "original_id = '{$query_result->original_id}' AND translation_set_id = '{$translation_set->id}' AND ( status = 'waiting' OR status = 'fuzzy' OR status = 'current' )" );
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
	 * Gets a suggested translation from ChatGPT.
	 *
	 * @param      WP_REST_Request $request  The request.
	 *
	 * @return     array  The suggested translation.
	 */
	public function get_suggested_translation( $request ) {
		// Prefer the user key over the global key.
		$openai_key = get_user_option( 'gp_openai_key' );
		if ( ! $openai_key ) {
			$openai_key = get_option( 'gp_openai_key' );
			if ( ! $openai_key ) {
				return array();
			}
		}

		$text   = $request->get_param( 'text' );
		$prompt = $request->get_param( 'prompt' );

		$language = $request->get_param( 'localeName' );
		if ( 'German' === $language ) {
			$language = 'informal ' . $language;
		}

		if ( ! $prompt ) {
			$custom_prompt = get_option( 'gp_chatgpt_custom_prompt' );
			if ( $custom_prompt ) {
				$prompt .= rtrim( $custom_prompt, '. ' ) . '. ';
			}
			$custom_prompt = get_user_option( 'gp_chatgpt_custom_prompt' );
			if ( $custom_prompt ) {
				$prompt .= rtrim( $custom_prompt, '. ' ) . '. ';
			}
		} else {
			$prompt = rtrim( $prompt, '. ' ) . '. ';
		}
		$intermediary = '';
		if ( $prompt ) {
			$intermediary = 'Given these conditions, ';
		}
		$unmodifyable = 'Translate the following text to ' . $language . ' and respond as a JSON list in the format ["translation","alternate translation (if any)"]: ';

		foreach ( array( 'singular', 'plural' ) as $key ) {
			if ( empty( $text[ $key ] ) ) {
				continue;
			}

			$messages = array(
				array(
					'role'    => 'user',
					'content' => $prompt . $intermediary . $unmodifyable . PHP_EOL . PHP_EOL . $text[ $key ],
				),
			);

			$suggestion = array();
			$response   = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $openai_key,
					),
					'timeout' => 30,
					'body'    => wp_json_encode(
						array(
							'model'      => 'gpt-3.5-turbo',
							'messages'   => $messages,
							'max_tokens' => 1000,
						)
					),
				)
			);
			$body       = wp_remote_retrieve_body( $response );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			$output = json_decode( $body, true );
			if ( empty( $output ) ) {
				return new WP_Error( 'error', $body, array( 'status' => 400 ) );
			}
			if ( ! empty( $output['error'] ) ) {
				return new WP_Error( 'error', $output['error'], array( 'status' => 400 ) );
			}
			$message = preg_replace( '/"\s*,\s*\]/', '"]', $output['choices'][0]['message'] );
			foreach ( json_decode( $message['content'] ) as $answer ) {
				if ( trim( $answer ) ) {
					$suggestion[] = $answer;
				}
			}
		}

		return array(
			'suggestion'   => $suggestion,
			'output'       => $output,
			'prompt'       => $prompt,
			'unmodifyable' => $unmodifyable,
		);
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
		$path = $request->get_param( 'path' );
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

		$originals_added    = false;
		$translations_added = false;
		if ( file_exists( $local_mo ) ) {
			list( $originals_added ) = $this->get_or_import_originals( $project, $local_mo );
			$messages[]              = sprintf(
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
}
