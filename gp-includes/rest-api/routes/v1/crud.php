<?php
/**
 * Abstract Rest CRUD Controller Class
 *
 * @package GlotPress\RestApi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GP_REST_CRUD_Controller
 *
 * @package GlotPress\RestApi
 * @version 5.0.0
 */
abstract class GP_REST_CRUD_Controller extends WP_REST_Controller {

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
	protected $rest_base = '';

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $thing_type = '';

	/**
	 * Retrieve the desired resource from the request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	protected function get_resource( $request ) {

		$thing = gp_get_thing( (int) $request['id'], $this->thing_type );

		if ( ! $thing ) {
			return new WP_Error( 'gp_rest_invalid_resource', __( 'Sorry, no resource found with those parameters.', 'glotpress' ), array( 'status' => 404 ) );
		}
	
		return $thing;
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! GP::$permission->current_user_can( 'read', $this->thing_type ) ) {
			return new WP_Error( 'gp_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! GP::$permission->current_user_can( 'write', $this->thing_type ) ) {
			return new WP_Error( 'gp_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
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
		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		if ( ! GP::$permission->current_user_can( 'read', $this->thing_type, $thing->id ) ) {
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
		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		if ( ! GP::$permission->current_user_can( 'write', $this->thing_type, $thing->id ) ) {
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
		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		if ( ! GP::$permission->current_user_can( 'delete', $this->thing_type, $thing->id ) ) {
			return new WP_Error( 'gp_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		$data = $this->prepare_item_for_response( $thing, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a single item output for response.
	 *
	 * @param GP_Thing        $thing   Thing object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $thing, $request ) {

		$data = array(
			'id'                => (int) $thing->id,
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $thing, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param GP_Thing           $thing      Thing object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $thing, $request );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		global $wpdb;
		$wpdb->hide_errors();

		$thing = $this->get_resource( $request );

		if ( ! is_wp_error( $thing ) && $thing->id ) {
			/* translators: %s: object type */
			return new WP_Error( "gp_rest_{$this->thing_type}_exists", __( 'Cannot create existing resource.', 'glotpress' ), array( 'status' => 400 ) );
		}

		$thing = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		if ( ! $thing->save() ) {
			return new WP_Error( "gp_rest_{$this->thing_type}_failed_to_create", sprintf( __( 'Resource could not be created. %s', 'glotpress' ), $wpdb->last_error ), array( 'status' => 400 ) );
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param GP_Thing        $thing     Thing object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "gp_rest_insert_{$this->thing_type}", $thing, $request, true );

		$request->set_param( 'context', 'edit' );
		$data     = $this->prepare_item_for_response( $thing, $request );
		$response = rest_ensure_response( $data );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $thing->id ) ) );

		return $response;
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

		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		$thing = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}
		
		if ( ! $thing->save() ) {
			/* translators: 1: object type, 2: database error message */
			return new WP_Error( "gp_rest_{$this->thing_type}_failed_to_update", sprintf( __( '%1$s could not be updated. %2$s', 'glotpress' ), ucfirst($this->thing_type), $wpdb->last_error ), array( 'status' => 400 ) );
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param GP_Thing        $thing     Thing object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "gp_rest_insert_{$this->thing_type}", $thing, $request, false );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $thing, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = $this->prepare_items_query( $request->get_params(), $request );

		$object = GP::${ $this->thing_type } ?? null;

		$things = array();

		if ( ! $object || ! is_callable( array( $object, 'find_some' ) ) ) {
			return rest_ensure_response( $things );
		}

		$query_result = $object->find_some( $args );

		foreach ( $query_result as $thing ) {
			if ( ! GP::$permission->current_user_can( 'read', $this->thing_type, $thing->id ) ) {
				continue;
			}

			$data = $this->prepare_item_for_response( $thing, $request );
			$things[] = $this->prepare_response_for_collection( $data );
		}

		if ( ! $object || ! is_callable( array( $object, 'count' ) ) ) {
			rest_ensure_response( $things );
		}

		// Add pagination headers.
		$response = rest_ensure_response( $things );

		$page         = (int) $args['page'];
		$total_things = $object->count( array( 'per_page' => -1 ) );

		$max_pages = ceil( $total_things / (int) $args['per_page'] );

		$response->header( 'X-WP-Total', (int) $total_things );
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
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		global $wpdb;
		$wpdb->hide_errors();

		$thing = $this->get_resource( $request );

		if ( is_wp_error( $thing ) ) {
			return $thing;
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $thing, $request );

		if ( ! $thing->delete() ) {
			/* translators: %s: post type */
			return new WP_Error( 'gp_rest_cannot_delete', sprintf( __( 'The resource cannot be deleted. %s', 'glotpress' ), $wpdb->last_error ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single item is deleted or trashed via the REST API.
		 *
		 * @param object           $thing     The deleted or trashed item.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "gp_rest_delete_{$this->thing_type}", $thing, $response, $request );

		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param GP_Thing        $thing   Thing object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $thing, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $thing->id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Determine the allowed query_vars for a get_items() response and
	 * prepare for WP_Query.
	 *
	 * @param array           $prepared_args Prepared arguments.
	 * @param WP_REST_Request $request       The request used.
	 * @return array          $query_args
	 */
	protected function prepare_items_query( $prepared_args, $request ) {
		$query_args = array();
		foreach ( $this->get_allowed_query_vars() as $var ) {
			if ( array_key_exists( $var, $prepared_args ) ) {
				$query_args[ $var ] = $prepared_args[ $var ];
			}
		}

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a thing
		 * collection request.
		 *
		 * @param array           $query_args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 */
		return apply_filters( "gp_rest_{$this->thing_type}_query_args", $query_args, $request );
	}

	/**
	 * Get all the Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars() {
		return array(
			'order',
			'orderby',
			'page',
			'per_page',
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['id'] = array(
			'description'       => __( 'Limit result set to specific ids.', 'glotpress' ),
			'type'              => 'array',
			'items'             => array(
				'type'          => 'integer',
			),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['order'] = array(
			'description'        => __( 'Order sort attribute ascending or descending.', 'glotpress' ),
			'type'               => 'string',
			'default'            => 'asc',
			'enum'               => array( 'asc', 'desc' ),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'description'        => __( 'Sort collection by object attribute.', 'glotpress' ),
			'type'               => 'string',
			'default'            => 'id',
			'enum'               => GP::${ $this->thing_type }->query_vars ?? array(),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$param['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set. Use "no-limit" for all items.', 'glotpress' ),
			'type'              => array( 'integer', 'string' ),  // ← Accept both types
			'default'           => GP::${ $this->thing_type }->per_page ?? 10,
			'sanitize_callback' => function( $value ) {
				if ( 'no-limit' === $value ) {
					return 'no-limit';
				}
				return absint( $value );
			},
			'validate_callback' => function( $value, $request, $param ) {
				// Allow 'no-limit' string
				if ( 'no-limit' === $value ) {
					return true;
				}
				// Otherwise validate as integer
				$value = (int) $value;
				if ( $value < 1 || $value > 100 ) {
					return new WP_Error(
						'rest_invalid_param',
						__( 'per_page must be between 1 and 100, or "no-limit".', 'glotpress' ),
						array( 'status' => 400 )
					);
				}
				return true;
			},
		);

		unset( $params['search'] );

		return $params;
	}

}
