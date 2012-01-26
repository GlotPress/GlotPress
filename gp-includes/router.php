<?php

class GP_Router {

	var $api_prefix = 'api';
	
	function __construct( $urls = null ) {
		if ( is_null( $urls ) )
			$this->urls = $this->default_routes();
		else
			$this->urls = $urls;
	}
	
	/**
	* Returns the current request URI path, relative to
	* the application URI and without the query string
	*/
	function request_uri() {
		$subdir = rtrim( gp_url_path(), '/' );
		if ( preg_match( "@^$subdir(.*?)(\?.*)?$@", $_SERVER['REQUEST_URI'], $match ) )
			return urldecode( $match[1] );
		return false;
	}
	
	function request_method() {
		return gp_array_get( $_SERVER, 'REQUEST_METHOD', 'GET' );
	}
		
	function add( $re, $function, $method = 'get' ) {
		$this->urls["$method:$re"] = $function;
	}
	
	function default_routes() {
		$dir = '([^_/][^/]*)';
		$path = '(.+?)';
		$projects = 'projects';
		$project = $projects.'/'.$path;
		$id = '(\d+)';
		$locale = '('.implode('|', array_map( create_function( '$x', 'return $x->slug;' ), GP_Locales::locales() ) ).')';
		$set = "$project/$locale/$dir";
		// overall structure
		return apply_filters( 'routes', array(
			'/' => array('GP_Route_Index', 'index'),
			'get:/login' => array('GP_Route_Login', 'login_get'),
			'post:/login' => array('GP_Route_Login', 'login_post'),
			'get:/logout' => array('GP_Route_Login', 'logout'),

			"get:/$project/import-originals" => array('GP_Route_Project', 'import_originals_get'),
			"post:/$project/import-originals" => array('GP_Route_Project', 'import_originals_post'),

			"get:/$project/-edit" => array('GP_Route_Project', 'edit_get'),
			"post:/$project/-edit" => array('GP_Route_Project', 'edit_post'),

			"get:/$project/-delete" => array('GP_Route_Project', 'delete_get'),
			"post:/$project/-delete" => array('GP_Route_Project', 'delete_post'),

			"post:/$project/-personal" => array('GP_Route_Project', 'personal_options_post'),
			
			"get:/$project/-permissions" => array('GP_Route_Project', 'permissions_get'),
			"post:/$project/-permissions" => array('GP_Route_Project', 'permissions_post'),
			"get:/$project/-permissions/-delete/$dir" => array('GP_Route_Project', 'permissions_delete'),
			
			"get:/$project/-mass-create-sets" => array('GP_Route_Project', 'mass_create_sets_get'),
			"post:/$project/-mass-create-sets" => array('GP_Route_Project', 'mass_create_sets_post'),
			"post:/$project/-mass-create-sets/preview" => array('GP_Route_Project', 'mass_create_sets_preview_post'),
			

			"get:/$projects" => array('GP_Route_Project', 'index'),
			"get:/$projects/-new" => array('GP_Route_Project', 'new_get'),
			"post:/$projects/-new" => array('GP_Route_Project', 'new_post'),

			"post:/$set/-bulk" => array('GP_Route_Translation', 'bulk_post'),
			"get:/$set/import-translations" => array('GP_Route_Translation', 'import_translations_get'),
			"post:/$set/import-translations" => array('GP_Route_Translation', 'import_translations_post'),
			"post:/$set/-discard-warning" => array('GP_Route_Translation', 'discard_warning'),
			"post:/$set/-set-status" => array('GP_Route_Translation', 'set_status'),
			"/$set/export-translations" => array('GP_Route_Translation', 'export_translations_get'),
			// keep this below all URLs ending with a literal string, because it may catch one of them
			"get:/$set" => array('GP_Route_Translation', 'translations_get'),
			"post:/$set" => array('GP_Route_Translation', 'translations_post'),
			// keep this one at the bottom of the project, because it will catch anything starting with project
			"/$project" => array('GP_Route_Project', 'single'),

			"get:/sets/-new" => array('GP_Route_Translation_Set', 'new_get'),
			"post:/sets/-new" => array('GP_Route_Translation_Set', 'new_post'),
			"get:/sets/$id" => array('GP_Route_Translation_Set', 'single'),
			"get:/sets/$id/-edit" => array('GP_Route_Translation_Set', 'edit_get'),
			"post:/sets/$id/-edit" => array('GP_Route_Translation_Set', 'edit_post'),
			
			"post:/originals/$id/set_priority" => array('GP_Route_Original', 'set_priority'),
		) );
	}

	
	function route() {
		$real_request_uri = $this->request_uri();
		$api_request_uri = $real_request_uri;
		$request_method = strtolower( $this->request_method() );
		$api = gp_startswith( $real_request_uri, '/'.$this->api_prefix.'/' );
		if ( $api ) {
			$real_request_uri = substr( $real_request_uri, strlen( $this->api_prefix ) + 1 );
		}
		foreach( array( $api_request_uri, $real_request_uri ) as $request_uri ) {
			foreach( $this->urls as $re => $func ) {
				foreach (array('get', 'post', 'head', 'put', 'delete') as $http_method) {
					if ( gp_startswith( $re, $http_method.':' ) ) {
						
						if ( $http_method != $request_method ) continue;
						$re = substr( $re, strlen( $http_method . ':' ));
						break;
					}
				}
				if ( preg_match("@^$re$@", $request_uri, $matches ) ) {
					if ( is_array( $func ) ) {
						list( $class, $method ) = $func;
						$route = new $class;
						$route->api = $api;
						$route->last_method_called = $method;
						$route->class_name = $class;
						GP::$current_route = &$route;
						$route->before_request();
						$route->request_running = true;
						// make sure after_request() is called even if we $this->exit_() in the request
						register_shutdown_function( array( &$route, 'after_request') );
						call_user_func_array( array( $route, $method ), array_slice( $matches, 1 ) );
						$route->after_request();
						do_action( 'after_request', $class, $method );
						$route->request_running = false;
					} else {
						call_user_func_array( $func, array_slice( $matches, 1 ) );
					}
					return;
				}
			}
		}
		return gp_tmpl_404();
	}
}
