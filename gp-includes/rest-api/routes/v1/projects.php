<?php
/**
 * REST API Projects controller
 *
 * Handles requests to the /projects endpoint.
 *
 * @package GlotPress\RestApi
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Projects controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_Rest_Projects_V1_Controller extends GP_REST_CRUD_Controller {

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
	protected $thing_type = 'project';

	/**
	 * Register the routes for projects.
	 */
	public function register_routes() {
		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base,
			array(
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
						'name' => array(
							'description' => __( 'Project name.', 'glotpress' ),
							'required'    => true,
							'type'        => 'string',
						),
					) ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<project>[^/]+)', array(
			'args' => array(
				'project' => array(
					'description' => __( 'Project ID or path.', 'glotpress' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context'         => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}


	/**
	 * Retrieve the desired resource from the request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return GP_Project|WP_Error The requested resource, or WP_Error if not found.
	 */
	protected function get_resource( $request ) {
		return gp_rest_get_project( $request );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$args = $this->prepare_items_query( $request->get_params(), $request );

		$projects = GP::$project->find_some( $args );

		$items = array();
		foreach ( $projects as $project ) {
			$data = $this->prepare_item_for_response( $project, $request );
			$items[] = $this->prepare_response_for_collection( $data );
		}

		$total_projects = GP::$project->count( array( 'per_page' => 'no-limit' ) );

		$page           = (int) $args['page'];
		$max_pages      = ceil( (int) $total_projects / (int) $args['per_page'] );

		$response = rest_ensure_response( $items );

		$response->header( 'X-WP-Total', (int) $total_projects );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();

		if ( ! empty( $request_params['filter'] ) ) {
			// Normalize the pagination params.
			unset( $request_params['filter']['per_page'] );
			unset( $request_params['filter']['page'] );
		}
		$base = add_query_arg( $request_params, rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );

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
	 * Retrieves one item from the collection.
	 *
	 * @since 5.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$project = $this->get_resource( $request );

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		$data     = $this->prepare_item_for_response( $project, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a single project output for response.
	 *
	 * @param GP_Project        $project Project object.
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $project, $request ) {

		$context = $request['context'] ?: 'view';

		$data = array(
			'id'                => (int) $project->id,
			'name'              => $project->name,
			'path'              => $project->path,
			'active'            => (bool) $project->active,
			'description'       => $project->description,
			'parent_project_id' => (int) $project->parent_project_id,
			'source_url'        => $project->source_url_template,
		);

		if ( 'view' === $context ) {
			$data['sets']         = $this->prepare_sets_for_response( $project, $request );
			$data['sub_projects'] = $this->prepare_subprojects_for_response( $project, $request );
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $project, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param GP_Project         $project    Project object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $project, $request );
	}

	/**
	 * Prepare sets for response.
	 *
	 * @param GP_Project        $project Project object.
	 * @param WP_REST_Request   $request Request object.
	 * @return array
	 */
	protected function prepare_sets_for_response( $project, $request ) {
		$translation_sets = GP::$translation_set->by_project_id( $project->id );

		// Sort sets by translated count if not full data.
		usort(
			$translation_sets,
			function ( $a, $b ) {
				return( $a->current_count <=> $b->current_count );
			}
		);

		// Slice top 30 sets for this page.
		$paged_sets = array_slice( $translation_sets, 0, GP::$translation_set->per_page );

		$prepared_sets = array();

		foreach ( $paged_sets as $set ) {

			$prepared_set = array(
				'name'   => $set->name,
				'locale' => $set->locale,
				'slug'   => $set->slug,
			);

			$prepared_set['_links'] = array(
				'self' => array(
					array(
						'href' => rest_url(
							sprintf(
								'%s/%s/%s/%s/%s',
								$this->namespace,
								$this->rest_base,
								gp_rest_encode_project_path( $project->path ),
								rawurlencode( $set->locale ),
								rawurlencode( $set->slug )
							)
						)
					),
				),
			);

			$prepared_sets[] = $prepared_set;
		}

		return $prepared_sets;

	}

	/**
	 * 
	 * Prepare sub-projects for response. 
	 *
	 * @param GP_Project $project
	 * @param WP_REST_Request $request
	 * @return array
	 */
	protected function prepare_subprojects_for_response( $project, $request ) {
		$sub_projects = $project->sub_projects();

		$items = array();
		foreach ( $sub_projects as $sub_project ) {
			$prepared_project = array(
				'id'   => (int) $sub_project->id,
				'slug' => $sub_project->path,
				'name' => $sub_project->name,
			);
			$prepared_project['_links'] = array(
				'self' => array(
					array(
						'href' => rest_url(
							sprintf(
								'%s/%s/%s',
								$this->namespace,
								$this->rest_base,
								rawurlencode( $sub_project->path ),
							)
						),
					),
				),
			);
			$items[] = $prepared_project;
		}

		return $items;
	}
	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		if ( isset( $request['path'] ) ) {
			$existing_project = GP::$project->by_path_or_id( $request['path'] );

			if ( $existing_project instanceof GP_Project && $existing_project->path === $request['path'] ) {
				return new WP_Error( "gp_rest_{$this->thing_type}_already_exists", __( 'Resource already exists with this path.', 'glotpress' ), array( 'status' => 400 ) );
			}
		}

		$response = parent::create_item( $request );

		$data = $response->get_data();

		$path = $data['path'] ?? '';

		if ( $path ) {
			$path = gp_rest_encode_project_path( $path );
			$response->header( 'Location', rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $path ) ) );
		}

		return $response;		
	}

	/**
	 * Prepare a single product for create or update.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_Error|WP_Project     $project Project object or WP_Error if not valid.
	 */
	protected function prepare_item_for_database( $request ) {
		$project = $this->get_resource( $request );

		if ( is_wp_error( $project ) ) {
			$project = new GP_Project();
		}

		$args = array();

		// Project title.
		if ( isset( $request['name'] ) ) {
			$args['name'] = wp_filter_post_kses( $request['name'] );
		} elseif ( ! $project->id ) {
			return new WP_Error( "gp_rest_{$this->thing_type}_missing_name", __( 'Resource name is required.', 'glotpress' ), array( 'status' => 400 ) );
		}

		// Project parent ID.
		if ( isset( $request['parent_project_id'] ) ) {
			$args['parent_project_id'] = intval( $request['parent_project_id'] );

			$parent_project = GP::$project->get( $args['parent_project_id'] );

			if ( ! $parent_project || ! $parent_project instanceof GP_Project ) {
				return new WP_Error( "gp_rest_{$this->thing_type}_nonexistant_parent_id", __( 'That parent resource does not exist.', 'glotpress' ), array( 'status' => 400 ) );
			}

			if ( $project->id && $project->id === $args['parent_project_id'] ) {
				return new WP_Error( "gp_rest_{$this->thing_type}_invalid_parent_id", __( 'Resource cannot be a parent of itself.', 'glotpress' ), array( 'status' => 400 ) );
			}

		}

		// Project description.
		if ( isset( $request['description'] ) ) {
			$args['description'] = wp_filter_post_kses( $request['description'] );
		}

		// Project status.
		if ( isset( $request['active'] ) ) {
			$args['active'] = boolval( $request['active'] ) ? 1 : 0;
		} elseif ( ! $project->id ) {
			$args['active'] = 1; // Default to active on create.
		}

		// Project slug.
		if ( isset( $request['slug'] ) ) {
			$args['slug'] = sanitize_title( $request['slug'] );
		}

		// Source template.
		if ( isset( $request['source_url_template'] ) ) {
			$args['source_url_template'] = sanitize_url( $request['source_url_template'] );
		}

		$project->set_fields( $args );

		/**
		 * Filter the project object before it is inserted or updated in the database.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the post being
		 * prepared for insertion.
		 *
		 * @param GP_Project       $project An object representing a single item prepared
		 *                                       for inserting or updating the database.
		 * @param WP_REST_Request $request       Request object.
		 */
		return apply_filters( "gp_rest_pre_insert_{$this->thing_type}", $project, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param GP_Project      $project   Project object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $project, $request ) {
		$links = parent::prepare_links( $project, $request );

		$links['self'] = array(
			'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $project->path ) ),
		);

		if ( $project->parent_project_id ) {
			$parent_project = GP::$project->get( $project->parent_project_id );
			if ( $parent_project ) {
				$links['up'] = array(
					array(
						'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, rawurlencode( $parent_project->path ) ) ),
					),
				);
			}
		}

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
				'id' => array(
					'description' => __( 'Unique identifier for the project.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'detail' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Project name.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'slug' => array(
					'description' => __( 'Project slug.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'active' => array(
					'description' => __( 'Whether the project is active.', 'glotpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'description' => array(
					'description' => __( 'Project description.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'parent_project_id' => array(
					'description' => __( 'Parent project ID.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'source_url' => array(
					'description' => __( 'Source URL template.', 'glotpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'detail' ),
				),
				'sub_projects' => array(
					'description' => __( 'Sub-projects of this project.', 'glotpress' ),
					'type'        => 'array',
					'context'     => array( 'detail' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id' => array(
								'description' => __( 'Project ID.', 'glotpress' ),
								'type'        => 'integer',
								'context'     => array( 'detail' ),
							),
							'slug' => array(
								'description' => __( 'Project slug.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
							'name' => array(
								'description' => __( 'Project name.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
						),
					),
				),
				'sets' => array(
					'description' => __( 'Translation sets. On the product detail this is limited to top 30 sets. For full list see the project/{project}/sets endpoint', 'glotpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'detail' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name' => array(
								'description' => __( 'Set name.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
							'locale' => array(
								'description' => __( 'Set locale.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
							'slug' => array(
								'description' => __( 'Set slug.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
							'wp_locale' => array(
								'description' => __( 'WordPress locale.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'detail' ),
							),
						),
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
			'name',
			'path',
			'parent_project_id'
		);

		return array_merge( parent::get_allowed_query_vars(), $valid_vars );
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
