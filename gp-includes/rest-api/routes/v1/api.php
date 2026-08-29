<?php
/**
 * REST API Update controller
 *
 * Handles requests to the /api endpoint for serving language pack updates.
 *
 * @package GlotPress\RestApi
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Update controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_REST_Api_V1_Controller extends GP_REST_CRUD_Controller {

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
	protected $rest_base = 'api';

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $thing_type = 'language_pack';

	/**
	 * Register the routes for projects.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<project>[^/]+)',
			array(
				'args'   => array(
					'project' => array(
						'description' => __( 'Unique identifier for the resource (ID or slug).', 'glotpress' ),
						'type'        => 'string',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
						'percent_translated' => array(
                            'description' => __( 'Minimum percent translated to include.', 'glotpress' ),
                            'type'        => 'integer',
                            'default'     => 90,
                            'minimum'     => 0,
                            'maximum'     => 100,
                        ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
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
		return gp_rest_get_project( $request );
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

		$translation_sets = GP::$translation_set->by_project_id( $project->id );

		$items = array();
		foreach ( $translation_sets as $set ) {

			$locale = GP_Locales::by_slug( $set->locale );

			if ( ! $locale ) {
				continue;
			}

			$set->wp_locale = $locale ? $locale->wp_locale : '';

			// Skip sets that don't meet the translation threshold.
			if ( $set->percent_translated() < $request['percent_translated'] ) {
				continue;
			}

			$data = $this->prepare_item_for_response( $set, $request );

			$items[$set->wp_locale] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $items );

	}

	/**
	 * Prepare a single translation set output for response.
	 *
	 * @param GP_Translation_Set        $set Translation Set object.
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $set, $request ) {

		$context = $request['context'] ?: 'view';

		$locale = GP_Locales::by_slug( $set->locale );
		
		$data = array(
                'language'      => $set->wp_locale,
                'updated'       => $set->current_count() ? $set->last_modified() : false,
                'english_name'  => $locale->english_name,
                'native_name'   => $locale->native_name,
                'package'       => $this->get_language_pack_url( $this->get_resource( $request ), $set ),
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
		 * @param WP_REST_Response   $response   The response object.
		 * @param GP_Translation_Set $set        Translation set object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $set, $request );
	}


	/**
	 * Prepare links for the request.
	 *
	 * @param GP_Translation_Set $set     Translation set object.
	 * @param WP_REST_Request    $request Request object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $set, $request ) {
		return array();
	}

    /**
     * Get the language pack URL.
     *
     * @since 5.0.0
     *
     * @param GP_Project         $project         Project object.
     * @param GP_Translation_Set $translation_set Translation set object.
     * @param string             $version         Version string.
     * @return string|false Package URL or false if not found.
     */
    protected function get_language_pack_url( $project, $translation_set ) {

        // Default URL structure
        // Example: https://example.com/downloads/my-plugin/1.2.3/my-plugin-1.2.3-es_ES.zip
        $base_url = defined( 'GP_LANGUAGE_PACK_URL' ) ? GP_LANGUAGE_PACK_URL : home_url( '/downloads' );

		$filename = sprintf(
			'%s-%s.zip',
			$project->slug,
			$translation_set->wp_locale
		);

		return sprintf(
			'%s/%s/%s',
			untrailingslashit( $base_url ),
			$project->slug,
			$filename
		);
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
				'translations' => array(
					'description' => __( 'Available translation sets.', 'glotpress' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'language' => array(
								'description' => __( 'WordPress language code.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'updated' => array(
								'description' => __( 'Last update date.', 'glotpress' ),
								'type'        => 'string',
								'format'      => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'english_name' => array(
								'description' => __( 'English name of the language.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'native_name' => array(
								'description' => __( 'Native name of the language.', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'package' => array(
								'description' => __( 'URL to download the language pack.', 'glotpress' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);

		return $this->schema;
	}

}
