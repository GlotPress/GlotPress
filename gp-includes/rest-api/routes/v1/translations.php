<?php
/**
 * REST API Translations controller
 *
 * Handles requests to the /projects/{$project}/sets/{$set}/translations endpoint.
 *
 * @package GlotPress\RestApi
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Translations controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_Rest_Translations_V1_Controller extends GP_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'gp/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'projects';

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $thing_type = 'translation';

	/**
	 * Register the routes for translations.
	 */
	public function register_routes() {
		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base .
			'/(?P<project>[^/]+)/sets/(?P<locale>[^/]+)/(?P<set>[^/]+)/translations',
			array(
				'args' => array(
					'project' => array(
						'description' => __( 'Unique identifier for the project (ID or slug).', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'locale' => array(
						'description' => __( 'Unique identifier for the locale.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'set' => array(
						'description' => __( 'Unique identifier for the translation set.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
						'original_id' => array(
							'description' => __( 'Original translation ID.', 'glotpress' ),
							'required'    => true,
							'type'        => 'integer',
						),
					) ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base .
			'/(?P<project>[^/]+)/sets/(?P<locale>[^/]+)/(?P<set>[^/]+)/translations/(?P<original_id>\d+)',
			array(
				'args' => array(
					'project' => array(
						'description' => __( 'Unique identifier for the project (ID or slug).', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'locale' => array(
						'description' => __( 'Unique identifier for the locale.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'set' => array(
						'description' => __( 'Unique identifier for the translation set.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'original_id' => array(
						'description' => __( 'Original string ID.', 'glotpress' ),
						'type'        => 'integer',
						'required'    => false,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'         => WP_REST_Server::EDITABLE,
					'callback'        => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ), array(
						'original_id' => array(
							'description' => __( 'Original string ID.', 'glotpress' ),
							'required'    => true,
							'type'        => 'integer',
						),
						'status' => array(
							'required'          => false,
							'type'              => 'string',
							'default'           => 'current',
							'enum'              => array( 'current', 'waiting', 'rejected', 'fuzzy' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					) ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Retrieve the desired resource from the request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	protected function get_resource( $request ) {

		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translation ) = $objects;
	
		return $translation;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! GP::$permission->current_user_can( 'read', 'translation-set' ) ) {
			return new WP_Error( 'gp_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set ) = $objects;

		if ( ! $translation_set || ! GP::$permission->current_user_can( 'read', 'translation-set', $translation_set->id ) ) {
			return new WP_Error( 'gp_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}
	
		return true;
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set ) = $objects;

		if ( ! $translation_set || ! GP::$permission->current_user_can( 'write', 'translation-set', $translation_set->id ) ) {
			return new WP_Error( 'gp_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set ) = $objects;

		if ( ! $translation_set || ! GP::$permission->current_user_can( 'delete', 'translation-set', $translation_set->id ) ) {
			return new WP_Error( 'gp_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Retrieves item from the collection.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translations ) = $objects;

		$data = array();
		foreach ( $translations as $entry ) {
			$translation = $this->prepare_item_for_response( $entry, $request );
			$translation = $this->prepare_response_for_collection( $translation );
			$data[]   = $translation;
		}

		$response = rest_ensure_response( $data );

		$total_strings  = GP::$translation->found_rows;
		error_log('found X strings: ' . $total_strings);

		$page           = (int) $request['page'];
		$max_pages      = ceil( $total_strings / (int) $request['per_page'] );

		$response->header( 'X-WP-Total', (int) $total_strings );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();

		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s/%s/%s/%s', $this->namespace, $this->rest_base, $request['project'], $request['locale'], $request['set'] ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Get relevant objects from request.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @param bool $force Whether to force re-fetching the objects instead of using cache.
	 * @return array|WP_Error Array of [GP_Project, GP_Translation_Set, GP_Translation] or WP_Error
	 */
	protected function get_objects( $request, $force = false ) {
		// Static cache persists across multiple calls within the same request.
		static $cache = array();
		
		$params = $request->get_params();
		
		// Create a cache key from the relevant parameters.
		$cache_key = md5( serialize( array(
			'project'     => $params['project'] ?? 0,
			'locale'      => $params['locale'] ?? '',
			'set'         => $params['set'] ?? '',
			'original_id' => $params['original_id'] ?? 0,
		) ) );
		
		// Return cached result if available.
		if ( ! $force && isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}
		
		$project = GP::$project->by_path_or_id( $params['project'] ?? 0 );
		if ( ! $project || ! $project instanceof GP_Project ) {
			return new WP_Error( "gp_rest_{$this->thing_type}_invalid_id", __( 'Invalid project ID.', 'glotpress' ), array( 'status' => 404 ) );
		}
		
		$locale = GP_Locales::by_slug( $params['locale'] ?? '' );
		if ( ! $locale ) {
			return new WP_Error( 'gp_rest_locale_invalid_id', __( 'Locale not found.', 'glotpress' ), array( 'status' => 404 ) );
		}
		
		$translation_set = GP::$translation_set->find_one(
			array( 
				'slug'       => $params['set'] ?? '',
				'project_id' => $project->id,
				'locale'     => $locale->slug,
			)
		);
		
		if ( ! $translation_set ) {
			return new WP_Error( 'gp_rest_translation_set_invalid_id', __( 'Translation set not found.', 'glotpress' ), array( 'status' => 404 )  );
		}
		
		$args = $this->prepare_items_query( $params, $request );

		$original_id = (int) ( $params['original_id'] ?? 0 );
		if ( $original_id && ! GP::$original->get( (int) $params['original_id'] ) ) {
			return new WP_Error( 'gp_rest_translation_invalid_original_id', __( 'Original ID not found.', 'glotpress' ), array( 'status' => 404 ) );
		}

		$args    = $this->prepare_items_query( $params, $request );
		$filters = array();

		// When reading we allow filtering by status and translated_by.
		if ( WP_REST_Server::READABLE === $request->get_method() ) {
			if ( ! empty( $args['status'] ) ) {
				$filters['status'] = $args['status'];
			}
			if ( ! empty( $args['translated_by'] ) ) {
				$filters['user_login'] = $args['translated_by'];
			}
		}

		if ( ! empty( $args['id'] ) ) {
			$filters['translation_id'] = (int) $args['id'];
		}

		if ( $original_id ) {
			$filters['original_id'] = $original_id;
		}

		$translations = GP::$translation->for_translation(
			$project,
			$translation_set,
			$args['page'] ?? 'no-limit',
			$filters,
			array( $args['orderby'] ?? 'id' ),
			$args['per_page'] ?? ''
		);

		if ( empty( $translations ) ) {
			return new WP_Error( 'gp_rest_translation_not_found', __( 'Translation not found.', 'glotpress' ), array( 'status' => 404 ) );
		}

		$result = array(
			$project,
			$translation_set,
			$translations,
			$locale,
		);
		
		// Store in cache before returning.
		$cache[ $cache_key ] = $result;
		
		return $result;
	}

	/**
	 * Retrieves item from the collection.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translations ) = $objects;

		if ( ! GP::$permission->current_user_can( 'read', $this->thing_type, $translation_set->id ) ) {
			return new WP_Error( 'gp_rest_cannot_view', __( 'Sorry, you are not allowed to read this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$data = array();
		foreach ( $translations as $entry ) {
			$translation = $this->prepare_item_for_response( $entry, $request );
			$translation = $this->prepare_response_for_collection( $translation );
			$data[]   = $translation;
		}

		return rest_ensure_response( $data );

	}

	/**
	 * Prepare a single translation output for response.
	 *
	 * @param Translation_Entry $translation Translation object.
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $translation, $request ) {

		$context = $request['context'] ?? 'view';

		$data = array(
			'id'            => (int) $translation->id,
			'original_id'   => (int) $translation->original_id,
			'priority'      => gp_array_get( GP::$original->get_static( 'priorities' ), $translation->priority ),
			'status'        => display_status( $translation->translation_status ),
			'date_added'    => $translation->date_added ?? '',
			'date_modified' => $translation->date_modified ?? '',
			'translated_by' => isset( $translation->user ) ? $translation->user->user_login: '',
			'singular'      => $translation->singular ?? '',
			'plural'        => $translation->plural ?? '',
			'context'       => $translation->context ?? '',
			'translations'  => $translation->translations,
		);
		
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $translation, $request ) );
		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response    The response object.
		 * @param Translation_Entry  $translation Translation object.
		 * @param WP_REST_Request    $request     Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $translation, $request );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translation ) = $objects;

		if ( $translation->id ) {
			/* translators: %s: object type */
			return new WP_Error( "gp_rest_{$this->thing_type}_exists", sprintf( __( 'Cannot create existing %s.', 'glotpress' ), $this->thing_type ), array( 'status' => 400 ) );
		}

		$translation = $this->save_translation( $request );

		if ( is_wp_error( $translation ) ) {
			return $translation;
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param GP_Translation $translation   Project object.
		 * @param WP_REST_Request    $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "gp_rest_insert_{$this->thing_type}", $translation, $request, false );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $translation, $request );
		return rest_ensure_response( $response );

	}

	/**
	 * Update a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		global $wpdb;
		$wpdb->hide_errors();

		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translation ) = $objects;

		// Return a duplicate translation if one exists.
		$duplicate = $this->check_for_duplicate_translation( $request );

		if ( is_wp_error( $duplicate ) ) {
			$error_data = $duplicate->get_error_data();
			if ( isset( $error_data['duplicate'] ) ) {
				$response = $this->prepare_item_for_response( $error_data['duplicate'], $request );
				return rest_ensure_response( $response );
			}			
		}

		// Proceed to create a new translation entry.
		$translation = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $translation ) ) {
			return $translation;
		}

		if ( ! $translation->save() ) {
			/* translators: 1: object type, 2: database error message */
			return new WP_Error( "gp_rest_{$this->thing_type}_failed_to_update", sprintf( __( '%1$s could not be updated. %2$s', 'glotpress' ), ucfirst($this->thing_type), $wpdb->last_error ), array( 'status' => 400 ) );
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param GP_Translation        $translation     Thing object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "gp_rest_insert_{$this->thing_type}", $translation, $request, false );
		$request->set_param( 'context', 'edit' );

		// Response returns Translation_Entry object, so we need to get the new resource.
		$translations = GP::$translation->for_translation(
			$project,
			$translation_set,
			'no-limit',
			array(
				'translation_id' => $translation->id
			),
			array()
		);

		if ( empty( $translations ) ) {
			return new WP_Error( 'gp_rest_translation_not_found', __( 'Translation not found.', 'glotpress' ), array( 'status' => 404 ) );
		}

		$response = $this->prepare_item_for_response( $translations[0], $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Check for duplicate translations.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|true
	 */
	protected function check_for_duplicate_translation( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translations_ignore, $locale ) = $objects;

		$translations = $request['translations'] ?? [];
		$original_id = (int) $request['original_id'];

		if ( ! empty( $translations ) && $original_id ) {
			// Check this is not a duplicate of an existing current or waiting translation.
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
				if ( array_pad( $translations, $locale->nplurals, null ) == $e->translations ) {
					return new WP_Error( 'gp_rest_duplicate_translation', __( 'Identical current or waiting translation already exists.', 'glotpress' ), array( 'status' => 400, 'duplicate' => $e ) );
				}
			}
		}

		return true;

	}

	/**
	 * Prepare a single product for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return GP_Translation $translation Translation object.
	 */
	protected function prepare_item_for_database( $request ) {
		$objects = $this->get_objects( $request );

		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		list( $project, $translation_set, $translation, $locale ) = $objects;

		$data = array(
			'original_id'        => (int) $request['original_id'],
			'translation_set_id' => (int) $translation_set->id,
			'user_id'            => get_current_user_id(),
			'priority'           => gp_array_get( GP::$original->get_static( 'priorities' ), $request['priority'], 5 ),
			'status'             => $this->prepare_translation_status( $request['status'] ?? 'current', $translation_set, $project ),
		);

		// Normalize plural translations.
		$nplurals = GP::$translation->get_static( 'number_of_plural_translations' );

		$translations = (array) $request['translations'];

		foreach ( range( 0, $nplurals - 1 ) as $i ) {
			if ( isset( $translations[ $i ] ) ) {
				$data[ "translation_$i" ] = $translations[ $i ];
			}
		}

		$original         = GP::$original->get( $data['original_id'] );
		$data['warnings'] = GP::$translation_warnings->check( $original->singular, $original->plural, $translations, $locale );

		// Validation.
		$errors           = GP::$translation_errors->check( $original, $translations, $locale );

		if ( is_array( $errors ) ) {
			return new WP_Error( 'gp_rest_invalid_translation', __( 'The translation contains errors.', 'glotpress' ), array( 'status' => 400, 'errors' => $errors ) );
		}
		
		if ( $request['id'] ) {
			$translation = GP::$translation->get( $request['id'] );
			$translation->set_fields( $data );
		} else {
			$translation = new GP_Translation( $data );
		}

		/**
		 * Filter the project object before it is inserted or updated in the database.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the post being
		 * prepared for insertion.
		 *
		 * @param GP_Translation       $translation An object representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "gp_rest_pre_insert_translation", $translation, $request );
	}

	/**
	 * Prepare translation status.
	 *
	 * @param string $status The requested status
	 * @param GP_Translation_Set $translation_set The Translation Set object.
	 * @param GP_Project $project The Project object.
	 * @return string The determined status.
	 */
	private function prepare_translation_status( $requested_status, $translation_set, $project ) {
		$can_approve = GP::$permission->current_user_can( 'approve', 'translation-set', $translation_set->id ) || 
					GP::$permission->current_user_can( 'write', 'project', $project->id );

		// Privileged users can set any valid status.
		if ( $can_approve ) {
			$valid_statuses = array( 'current', 'waiting', 'fuzzy', 'rejected', 'old' );
			return in_array( $requested_status, $valid_statuses, true ) ? $requested_status : 'waiting';
		}

		// Regular users can only set 'fuzzy' or 'waiting'
		if ( 'fuzzy' === $requested_status ) {
			return 'fuzzy';
		}

		return 'waiting';
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		global $wpdb;

		list( $project, $translation_set, $translation_entry ) = $this->get_objects( $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $translation_entry, $request );

		// Get the translation object before deletion.
		$translation = GP::$translation->get( $translation_entry->id );

		// Delete the translation.
		if ( ! GP::$translation->delete_all( array( 'id' => $translation_entry->id ) ) ) {
			/* translators: %s: object type */
			return new WP_Error( 'gp_rest_cannot_delete', sprintf( __( 'The translation cannot be deleted. %s', 'glotpress' ), $this->thing_type, $wpdb->last_error ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single item is deleted or trashed via the REST API.
		 *
		 * @param GP_Translation   $translation The deleted translation.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 */
		do_action( "gp_rest_delete_{$this->thing_type}", $translation, $response, $request );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param Translation_Entry  $translation Translation object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $translation, $request ) {
		$links = parent::prepare_links( $translation, $request );

		$links['self']['href'] = rest_url( sprintf( '/%s/%s/%s/sets/%s/%s/translations/%d', $this->namespace, $this->rest_base, rawurlencode( $request['project'] ), rawurlencode( $request['locale'] ), rawurlencode( $request['set'] ), $translation->original_id ) );
		$links['collection']['href'] = rest_url( sprintf( '/%s/%s/%s/sets/%s/%s/translations', $this->namespace, $this->rest_base, rawurlencode( $request['project'] ), rawurlencode( $request['locale'] ), rawurlencode( $request['set'] ) ) );

		if ( $request['id'] ) {
			$links['self']['href'] = add_query_arg( 'id', (int) $request['id'], $links['self']['href'] );
		}

		return $links;
	}

	/**
	 * Get the translation's schema, conforming to JSON Schema.
	 *
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
 		if ( isset( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'translation',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the translation.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'original_id' => array(
					'description' => __( 'Original string ID this translation belongs to.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly' => true, 
				),
				'priority' => array(
					'description' => __( 'Priority of the original string.', 'glotpress' ),
					'type'        => 'string',
					'enum'        => array( 'hidden', 'low', 'normal', 'high' ),
					'default'     => 'normal',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Translation status.', 'glotpress' ),
					'type'        => 'string',
					'enum'        => array( 'current', 'waiting', 'rejected', 'fuzzy' ),
					'default'     => 'current',
					'context'     => array( 'view', 'edit' ),
				),
				'date_added' => array(
					'description' => __( 'The date the translation was added, in site timezone.', 'glotpress' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly' => true, 
				),
				'date_modified' => array(
					'description' => __( 'The date the translation was last modified, in site timezone.', 'glotpress' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
					'readonly' => true, 
				),
				'translated_by' => array(
					'description' => __( 'Username of the translator.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly' => true, 
				),
				'singular' => array(
					'description' => __( 'Singular translation string.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly' => true, 
				),
				'plural' => array(
					'description' => __( 'Plural translation string.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly' => true, 
				),
				'context' => array(
					'description' => __( 'Translation context.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly' => true, 
				),
				'translations' => array(
					'description' => __( 'Translated strings keyed by plural form.', 'glotpress' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'additionalProperties' => array(
						'type' => 'string',
					),
				),
			),
		);

		return $this->schema;
	}

	/**
	 * Get all the Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars() {
		$valid_vars = array(		
			'id',
			'original_id',
			'priority',
			'status',
			'translated_by',
		);

		return array_merge( parent::get_allowed_query_vars(), $valid_vars );
	}

	/**
	 * Get the query params for collections of translations.
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['status'] = array(
			'description' => __( 'Filter translations by status.', 'glotpress' ),
			'type'        => 'string',
			'enum'        => array( 'current', 'waiting', 'fuzzy', 'rejected' ),
		);

		$params['priority'] = array(
			'description' => __( 'Filter by priority.', 'glotpress' ),
			'type'        => 'integer',
		);

		$params['original_id'] = array(
			'description' => __( 'Filter by original string ID.', 'glotpress' ),
			'type'        => 'integer',
		);

		$params['id'] = array(
			'description' => __( 'Filter by translation ID.', 'glotpress' ),
			'type'        => 'integer',
		);

		$params['translated_by'] = array(
			'description' => __( 'Filter by Translator user name.', 'glotpress' ),
			'type'        => 'string',	
		);

		$params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'glotpress' ),
			'type'        => 'string',
			'default'     => 'date_modified',
			'enum'        => array( 'date_added', 'date_modified', 'priority', 'id' ),
		);

		return $params;
	}

}
