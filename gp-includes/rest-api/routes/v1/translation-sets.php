<?php
/**
 * REST API Translation sets controller
 *
 * Handles requests to the /projects/{$project}/sets endpoint.
 *
 * @package GlotPress\RestApi
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Translation sets controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_Rest_Translation_Sets_V1_Controller extends GP_REST_CRUD_Controller {

private $counter = 1;

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
	protected $thing_type = 'translation-set';

	/**
	 * Register the routes for projects.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<project>[^/]+)/sets', array(
			'args' => array(
				'project' => array(
					'description' => __( 'Project ID or path.', 'glotpress' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array_merge(
					$this->get_collection_params(),
					array(
						'project' => array(
							'description' => __( 'Project ID or path.', 'glotpress' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'name' => array(
						'description' => __( 'Translation set name.', 'glotpress' ),
						'required'    => true,
						'type'        => 'string',
					),
					'locale' => array(
						'description' => __( 'Locale.', 'glotpress' ),
						'required'    => true,
						'type'        => 'string',
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<project>[^/]+)/sets/(?P<locale>[^/]+)/(?P<slug>[^/]+)',
			array(
				'args' => array(
					'project' => array(
						'description' => __( 'Project ID or path.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'locale' => array(
						'description' => __( 'Locale slug.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'slug' => array(
						'description' => __( 'Translation set slug.', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
				),

				// GET /projects/{project}/sets/{locale}/{slug}
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),

				// PUT /projects/{project}/sets/{locale}/{slug}
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => array(
						'name' => array(
							'type'     => 'string',
							'required' => false,
						),
						'active' => array(
							'type'     => 'boolean',
							'required' => false,
						),
					),
				),

				// DELETE /projects/{project}/sets/{locale}/{slug}
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

	}

	/**
	 * Retrieve the desired resource from the request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return GP_Project|WP_Error The requested resource, or WP_Error if not found.
	 */
	protected function get_resource( $request ) {
		
		// Static cache persists across multiple calls within the same request.
		static $cache = array();
		
		$original_params = $request->get_url_params();
		
		// Create a cache key from the relevant parameters.
		$cache_key = md5( serialize( array(
			'project' => $original_params['project'] ?? null,
			'slug'    => $original_params['slug'] ?? null,
			'locale'  => $original_params['locale'] ?? null,
		) ) );
		
		// Return cached result if available.
		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$project = gp_rest_get_project( $request );

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		$translation_set = new GP_Translation_Set(
			array( 
				'project_id' => $project->id,
			)
		);

		if ( ! empty( $original_params['slug'] ) && ! empty( $original_params['locale'] ) ) {

			$locale = GP_Locales::by_slug( $original_params['locale'] );

			if ( ! $locale ) {
				return new WP_Error( 'gp_rest_locale_invalid_id', __( 'Locale not found.', 'glotpress' ) );
			}

			$existing_set = GP::$translation_set->find_one(
				array( 
					'slug'       => $original_params['slug'],
					'project_id' => $project->id,
					'locale'     => $locale->slug,
				)
			);

			if ( $existing_set ) {
				$translation_set = $existing_set;
			}
		}

		// Store in cache before returning.
    	$cache[ $cache_key ] = $translation_set;

		return $translation_set;
	}


	/**
	 * Prepare a single set output for response.
	 *
	 * @param GP_Translation_Set $set Translation set object.
	 * @param WP_REST_Request    $request Request object.
	 * @return WP_REST_Response  $data
	 */
	public function prepare_item_for_response( $set, $request ) {

		$context = $request['context'] ?: 'view';

		$locale = GP_Locales::by_slug( $set->locale );

		$data = array(
			'name'               => $set->name,
			'locale'             => $set->locale,
			'slug'               => $set->slug,
			'wp_locale'          => $locale->wp_locale,
			'all_count'          => $set->all_count(),
			'translated_count'   => $set->current_count(),
			'untranslated_count' => $set->untranslated_count(),
			'fuzzy_count'        => $set->fuzzy_count(),
			'waiting_count'      => $set->waiting_count(),
			'percent_translated' => $set->percent_translated(),
			'last_modified'      => $set->current_count() ? $set->last_modified() : false,
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );	
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $set, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response The response object.
		 * @param GP_Translation_Set $set      Translation set object.
		 * @param WP_REST_Request    $request  Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $set, $request );
	}

	/**
	 * Prepare a single set for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return GP_Translation_Set     $set Translation set object.
	 */
	protected function prepare_item_for_database( $request ) {
		$set = $this->get_resource( $request );

		if ( is_wp_error( $set ) ) {
			return $set;
		}

		$args = array();

		// Set locale.
		if ( isset( $request['locale'] ) ) {
			$args['locale'] = sanitize_locale_name( $request['locale'] );
		}

		// Set name.
		if ( isset( $request['name'] ) ) {
			$args['name'] = wp_filter_post_kses( $request['name'] );
		}

		// Set slug.
		if ( isset( $request['slug'] ) ) {
			$args['slug'] = sanitize_title( $request['slug'] );
		} elseif ( ! $set->slug ) {
			$args['slug'] = 'default';
		}

		if ( isset( $request['active'] ) ) {
			$args['active'] = (bool) $request['active'];
		}

		$set->set_fields( $args );

		/**
		 * Filter the set object before it is inserted or updated in the database.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the post being
		 * prepared for insertion.
		 *
		 * @param GP_Translation_Set       $set An object representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "gp_rest_pre_insert_{$this->thing_type}", $set, $request );
	}

	/**
	 * Get a project's translation sets.
	 * 
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$args = $this->prepare_items_query( $request->get_params(), $request );
		$project = gp_rest_get_project( $request );

		if ( ! $project || ! $project instanceof GP_Project ) {
			return new WP_Error( "gp_rest_project_invalid_id", __( 'Invalid project ID.', 'glotpress' ), array( 'status' => 400 ) );
		}

		$translation_sets = GP::$translation_set->by_project_id( $project->id );

		// Slice sets for this page.
		$paged_sets = array_slice( $translation_sets, ( $args['page'] - 1) * $args['per_page'], $args['per_page'] );

		$items = array();
		foreach ( $paged_sets as $set ) {
			$data = $this->prepare_item_for_response( $set, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $data );

		$total_sets = count( $translation_sets );

		$page           = (int) $args['page'];
		$max_pages      = ceil( (int) $total_sets / (int) $args['per_page'] );

		$response = rest_ensure_response( $items );

		$response->header( 'X-WP-Total', (int) $total_sets );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();

		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s/%s/sets', $this->namespace, $this->rest_base, $request['project'] ) ) );

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
	 * Prepare links for the request.
	 *
	 * @param GP_Translation_Set $set     Project object.
	 * @param WP_REST_Request    $request Request object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $set, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s/sets/%s/%s', $this->namespace, $this->rest_base, $request['project'], $set->locale, $set->slug ) ),
			),
			'translations' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s/sets/%s/%s/translations', $this->namespace, $this->rest_base, $request['project'], $set->locale, $set->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s/sets', $this->namespace, $this->rest_base, $request['project'] ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the project's schema, conforming to JSON Schema.
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
			'title'      => $this->thing_type,
			'type'       => 'object',
			'properties' => array(
				'name' => array(
					'description' => __( 'Set name.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
				),
				'locale' => array(
					'description' => __( 'Set locale.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
				),
				'slug' => array(
					'description' => __( 'Set slug.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
				),
				'wp_locale' => array(
					'description' => __( 'WordPress locale.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'edit', 'view' ),
				),
				'all_count' => array(
					'description' => __( 'Number of all translatable strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'translated_count' => array(
					'description' => __( 'Number of translated strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'untranslated_count' => array(
					'description' => __( 'Number of untranslated strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'fuzzy_count' => array(
					'description' => __( 'Number of fuzzy strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'waiting_count' => array(
					'description' => __( 'Number of waiting strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'percent_translated' => array(
					'description' => __( 'Number of waiting strings.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'edit', 'view' ),
				),
				'last_modified' => array(
					'description' => __( 'Date set last modified.', 'glotpress' ),
					'type'        => 'datetime',
					'context'     => array( 'edit', 'view' ),
				),
			),
		);

		return $this->schema;
	}


	/**
	 * Get the query params for collections of projects.
	 *
	 * @return array
	 */
	public function get_collection_params() {

		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['parent_project_id'] = array(
			'description'       => __( 'Limit result set to specific parent project IDs.', 'glotpress' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		return $params;
	}

}
