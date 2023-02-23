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
			}

			$output[ $original_id ] = $translations;
		}

		return $output;
	}

	/**
	 * A slightly modified version og GP_Original->by_project_id_and_entry without the BINARY search keyword
	 * to make sure the index on the gp_originals table is used
	 *
	 * @param      int    $project_id  The project id.
	 * @param      object $entry       The entry.
	 * @param      string $status      The status.
	 *
	 * @return     array|null  The original entry.
	 */
	private function by_project_id_and_entry( $project_id, $entry, $status = '+active' ) {
		global $wpdb;

		$entry->plural  = isset( $entry->plural ) ? $entry->plural : null;
		$entry->context = isset( $entry->context ) ? $entry->context : null;

		$where = array();

		$where[] = is_null( $entry->context ) ? '(context IS NULL OR %s IS NULL)' : 'context = %s';
		$where[] = 'singular = %s';
		$where[] = is_null( $entry->plural ) ? '(plural IS NULL OR %s IS NULL)' : 'plural = %s';
		$where[] = 'project_id = %d';
		$where[] = $wpdb->prepare( 'status = %s', $status );

		$where = implode( ' AND ', $where );

		$query  = "SELECT * FROM $wpdb->gp_originals WHERE $where";
		$result = GP::$original->one( $query, $entry->context, $entry->singular, $entry->plural, $project_id );
		if ( ! $result ) {
			return null;
		}
		// We want case sensitive matching but this can't be done with MySQL while continuing to use the index therefore we do an additional check here.
		if ( $result->singular === $entry->singular ) {
			return $result;
		}

		// We then get the whole result set here and check each entry manually.
		$results = GP::$original->many( $query . ' AND id != %d', $entry->context, $entry->singular, $entry->plural, $project_id, $result->id );
		foreach ( $results as $result ) {
			if ( $result->singular === $entry->singular ) {
				return $result;
			}
		}

		return null;
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

		$translations    = array();
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
			return new WP_Error(
				'rest_invalid_projects',
				__( 'You specified invalid projects.', 'glotpress' ),
				array( 'status' => rest_authorization_required_code() )
			);
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
					$original_record = $this->by_project_id_and_entry( $project_id, $original );
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
}
