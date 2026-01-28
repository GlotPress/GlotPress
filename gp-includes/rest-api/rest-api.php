<?php
/**
 * Initialize this version of the REST API.
 *
 * @package GlotPress\RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class GP_REST_API {

	/**
	 * REST API namespaces and endpoints.
	 *
	 * @var array
	 */
	protected $controllers = array();

	/**
	 * Hook into WordPress ready to init the REST API as needed.
	 */
	public function __construct() { // phpcs:ignore WooCommerce.Functions.InternalInjectionMethod -- Not an injection method.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class;
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	/**
	 * Get API namespaces - new namespaces should be registered here.
	 *
	 * @return array List of Namespaces and Main controller classes.
	 */
	protected function get_rest_namespaces() {
		$namespaces = array(
			'gp/v1'        => $this->get_v1_controllers(),
		);

        return $namespaces;
	}

	/**
	 * List of controllers in the gp/v1 namespace.
	 *
	 * @return array
	 */
	protected function get_v1_controllers() {
		return array(
			'projects'         => 'GP_REST_Projects_V1_Controller',
			'import'           => 'GP_REST_Import_V1_Controller',
			'languages'        => 'GP_REST_Languages_V1_Controller',
			'translation_sets' => 'GP_REST_Translation_Sets_V1_Controller',
		);
	}

}
