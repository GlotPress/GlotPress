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
 * REST API Import controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_REST_Import_V1_Controller extends GP_REST_CRUD_Controller {

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
	 * Register the routes for import.
	 */
	public function register_routes() {
		register_rest_route( 
			$this->namespace,
			'/' . $this->rest_base .
			'/(?P<project>[^/]+)/import',
			array(
				'args' => array(
					'project' => array(
						'description' => __( 'Unique identifier for the project (ID or slug)', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
					'file' => array(
						'description' => __( 'The import file', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to update an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function import_permissions_check( $request ) {
		$project = gp_rest_get_project( $request );

		if ( ! is_wp_error( $project ) && ! GP::$permission->current_user_can( 'write', $this->thing_type, $project->id ) ) {
			return new WP_Error( 'gp_rest_cannot_import', __( 'Sorry, you are not allowed to import strings for this resource.', 'glotpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
	
	/**
	 * Prepare a single project output for response.
	 *
	 * @param array           $results Import results.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $results, $request ) {

		list( $originals_added, $originals_existing, $originals_fuzzied, $originals_obsoleted, $originals_error ) = $results;

		$data = array(
			'originals_added'     => (int) $originals_added,
			'originals_existing'  => (int) $originals_existing,
			'originals_fuzzied'   => (int) $originals_fuzzied,
			'originals_obsoleted' => (int) $originals_obsoleted,
			'originals_error'     => (int) $originals_error,
		);

		$context  = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
	//	$response->add_links( $this->prepare_links( $project, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param array              $results    Original strings processing results.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "gp_rest_prepare_import", $response, $results, $request );
	}

	/**
	 * Import originals for a single project.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function import( $request ) {

		$project = gp_rest_get_project( $request );

		if ( is_wp_error( $project ) ) {
			return $project;
		}

		$files = $request->get_file_params();
  
		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'gp_rest_missing_file', __( 'No file uploaded.', 'glotpress' ), array( 'status' => 400 ) );
		}

		$format = gp_get_import_file_format( gp_post( 'format', 'po' ), $files['file']['name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $format ) {
			return new WP_Error( 'gp_rest_unsupported_format', __( 'File format not supported.', 'glotpress' ), array( 'status' => 400 ) );
		}

		$translations = $format->read_originals_from_file( $files['file']['tmp_name'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! $translations ) {
			return new WP_Error( 'gp_rest_unsupported_format', __( 'Couldn&#8217;t load translations from file!', 'glotpress' ), array( 'status' => 400 ) );
			return;
		}

		$result = GP::$original->import_for_project( $project, $translations );

		/**
		 * Fires after a single file is uploaded via the REST API.
		 *
		 * @param array           $result    Results of import.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "gp_rest_originals_imported", $result, $request, false );
		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $result, $request );
		return rest_ensure_response( $response );

	}


	/**
	 * Get the projects's schema, conforming to JSON Schema.
	 * 
	 * @since 5.0.0
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->thing_type,
			'type'       => 'object',
			'properties' => array(
				'project' => array(
					'description' => __( 'Unique identifier for the project.', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Project name.', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug' => array(
					'description' => __( 'Project slug', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'active' => array(
					'description' => __( 'Active status', 'glotpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Description', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_project_id' => array(
					'description' => __( 'Parent project ID', 'glotpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
	}

}
