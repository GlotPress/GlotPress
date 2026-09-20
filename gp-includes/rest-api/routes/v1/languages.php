<?php
/**
 * REST API Languages controller
 *
 * Handles requests to the /languages endpoint.
 *
 * @package GlotPress\RestApi
 * @since   5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Languages controller class.
 *
 * @package GlotPress\RestApi
 * @extends GP_REST_CRUD_Controller
 */
class GP_REST_Languages_V1_Controller extends GP_REST_CRUD_Controller {

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
	protected $rest_base = 'languages';

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
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>.+)', array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource (slug).', 'glotpress' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'context'         => $this->get_context_param( array( 'default' => 'detail' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

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

		$existing_locales = GP::$translation_set->existing_locales();
		$locales          = array();

		foreach ( $existing_locales as $locale ) {
			$locale = GP_Locales::by_slug( $locale );
			if ( ! $locale ) {
				continue;
			}
			$data = $this->prepare_item_for_response( $locale, $request );
			$locales[] = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $locales );
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

		$locale = GP_Locales::by_slug( sanitize_title( $request['id'] ) );

		if ( ! $locale ) {
			return new WP_Error( 'gp_rest_language_not_found', __( 'Language not found.', 'glotpress' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $locale, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Prepare a single locale output for response.
	 *
	 * @param GP_Project        $locale Project object.
	 * @param WP_REST_Request   $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $locale, $request ) {

		$context = $request['context'] ?: 'view';

		$data = array(
			'slug'         => $locale->slug,
			'name'         => $locale->english_name,
			'native_name'  => $locale->native_name,
			'country_code' => $locale->country_code,
			'wp_locale'    => $locale->wp_locale,
		);

		if ( 'detail' === $context ) {
			$data['projects'] = $this->prepare_projects( $locale->slug, $request );
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $locale, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->thing_type, refers to thing_type of the item being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param array         $data    Data object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "gp_rest_prepare_{$this->thing_type}", $response, $data, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param GP_Locale       $locale Locale object.
	 * @param WP_REST_Request $request Request object.
	 * @return array Links for the given locale.
	 */
	protected function prepare_links( $thing, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $thing->slug ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Prepare projects and translation sets for REST output.
	 *
	 * @param string          $locale_slug Locale slug.
	 * @param WP_REST_Request $request Request object.
	 * @return array
	 */
	protected function prepare_projects( $locale_slug, $request ) {

		$sets = GP::$translation_set->by_locale( $locale_slug, 'project_id' );

		$projects          = array(); // Cached GP_Project objects.
		$prepared_projects = array(); // Final REST output.
		$project_sets      = array(); // Sets grouped by root project.
		$project_stats     = array(); // Aggregated stats per root project.

		foreach ( $sets as $set ) {

			$project = $projects[ $set->project_id ]
				?? $projects[ $set->project_id ] = GP::$project->get( $set->project_id );

			// Skip inactive projects.
			if ( empty( $project ) || empty( $project->active ) ) {
				continue;
			}

			// Resolve root project.
			$root_id = $project->parent_project_id ?: $project->id;

			if ( ! isset( $projects[ $root_id ] ) ) {
				$projects[ $root_id ] = GP::$project->get( $root_id );
			}

			// Initialize containers.
			$project_sets[ $root_id ]  ??= array();
			$project_stats[ $root_id ] ??= array(
				'all_count'        => 0,
				'translated_count' => 0,
				'fuzzy_count'      => 0,
				'waiting_count'    => 0,	
			);

			// Prepare set data (schema-aligned).
			$project_sets[ $root_id ][] = array(
				'project_id'         => $project->id,
				'project_path'       => $project->path,
				'slug'               => $set->slug,
				'all_count'          => $set->all_count(),
				'translated_count'   => $set->current_count(),
				'untranslated_count' => $set->untranslated_count(),
				'fuzzy_count'        => $set->fuzzy_count(),
				'waiting_count'      => $set->waiting_count(),
				'percent_translated' => (int) $set->percent_translated(),
				'last_modified'      => $set->current_count() ? $set->last_modified() : false,
			);

			// Accumulate stats.
			$project_stats[ $root_id ]['all_count']        += $set->all_count();
			$project_stats[ $root_id ]['translated_count'] += $set->current_count();
			$project_stats[ $root_id ]['fuzzy_count']      += $set->fuzzy_count();
			$project_stats[ $root_id ]['waiting_count']    += $set->waiting_count();			
		}

		// Build final response.
		foreach ( $project_sets as $project_id => $sets ) {

			$totals = $project_stats[ $project_id ];

			$percentage = $totals['all_count'] > 0
				? (int) number_format_i18n( ( $totals['translated_count'] / $totals['all_count'] ) * 100, 0 )
				: '0';

			$prepared_sets = array();

			foreach ( $sets as $set ) {
				$prepared_set = $set;
				$prepared_set['_links'] = array(
					'self' => array(
						array(
							'href' => rest_url(
								sprintf(
									'%s/translations/%s/%s/%s',
									$this->namespace,
									rawurlencode( $projects[ $project_id ]->slug ),
									rawurlencode( $locale_slug ),
									rawurlencode( $set['slug'] )
								)
							),
						),
					),
				);
				$prepared_sets[] = $prepared_set;
			}

			$prepared_project = array(
				'name'  => $projects[ $project_id ]->name,
				'slug'  => $projects[ $project_id ]->path,
				'stats' => array(
					'total'              => $totals['all_count'],
					'percent_translated' => $percentage,
				),
				'sets'  => $prepared_sets,
			);

			$prepared_project['_links'] = array(
				'self' => array(
					array(
						'href' => rest_url(
							sprintf(
								'%s/projects/%s',
								$this->namespace,
								rawurlencode( $projects[ $project_id ]->slug )
							)
						),
					),
				),
			);

			$prepared_projects[] = $prepared_project;
		}

		return $prepared_projects;
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
				'slug' => array(
					'description' => __( 'Slug', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name' => array(
					'description' => __( 'Name (in English).', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'native_name' => array(
					'description' => __( 'Native name', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'country_code' => array(
					'description' => __( 'Language code', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'wp_locale' => array(
					'description' => __( 'WordPress locale', 'glotpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'projects' => array(
					'description' => __( 'Active projects', 'glotpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'detail' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'name' => array(
								'description' => __( 'Project name', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'detail' ),
							),
							'stats' => array(
								'description' => __( 'Project / Stats', 'glotpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'detail' ),
								'type'       => 'object',
								'properties' => array(
									'total' => array(
										'description' => __( 'Total strings', 'glotpress' ),
										'type'        => 'int',
										'context'     => array( 'view', 'detail' ),
									),
									'percent_translated' => array(
										'description' => __( 'Percent translated', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'detail' ),
									),
								),
							),
							'sets' => array(
								'description' => __( 'Set / Sub Project', 'glotpress' ),
								'type'       => 'object',
								'properties' => array(
									'project_id' => array(
										'description' => __( 'Project ID.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'view' ),
									),
									'project_slug' => array(
										'description' => __( 'Project slug.', 'glotpress' ),
										'type'        => 'string',
										'context'     => array( 'view' ),
									),
									'slug' => array(
										'description' => __( 'Set slug.', 'glotpress' ),
										'type'        => 'string',
										'context'     => array( 'view' ),
									),
									'all_count' => array(
										'description' => __( 'Number of all translatable strings.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'translated_count' => array(
										'description' => __( 'Number of translated strings.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'untranslated_count' => array(
										'description' => __( 'Number of untranslated strings.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'fuzzy_count' => array(
										'description' => __( 'Number of fuzzy strings.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'waiting_count' => array(
										'description' => __( 'Number of waiting strings.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'percent_translated' => array(
										'description' => __( 'Percent translated.', 'glotpress' ),
										'type'        => 'integer',
										'context'     => array( 'detail' ),
									),
									'last_modified' => array(
										'description' => __( 'Date set last modified.', 'glotpress' ),
										'type'        => 'datetime',
										'context'     => array( 'detail' ),
									),
								),
							)
						),
					),
				),
			),
		);
	}

}
