<?php
/**
 * Routing functionality. Decides what code should be run, depending on the request URI
 */

class GP_Router {
	
	function GP_Router( $urls = null ) {
		if ( is_null( $urls ) )
			$this->urls = $this->routes();
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
			return $match[1];
		return false;
	}
	
	function request_method() {
		return gp_array_get( $_SERVER, 'REQUEST_METHOD', 'GET' );
	}
	
	function routes() {
		$dir = '([^/]+)';
		$path = '(.+?)';
		$projects = 'projects';
		$project = $projects.'/'.$path;
		$locale = '('.implode('|', array_map( create_function( '$x', 'return $x->slug;' ), GP_Locales::locales() ) ).')';
		// overall structure
		return apply_filters( 'routes', array(
			'/' => 'gp_route_index',
			'get:/login' => array('GP_Route_Login', 'login_get'),
			'post:/login' => array('GP_Route_Login', 'login_post'),
			'get:/logout' => array('GP_Route_Login', 'logout'),

			"get:/$project/import-originals" => array('GP_Route_Project', 'import_originals_get'),
			"post:/$project/import-originals" => array('GP_Route_Project', 'import_originals_post'),

			"get:/$project/_edit" => array('GP_Route_Project', 'edit_get'),
			"post:/$project/_edit" => array('GP_Route_Project', 'edit_post'),

			"get:/$project/_delete" => array('GP_Route_Project', 'delete_get'),
			"post:/$project/_delete" => array('GP_Route_Project', 'delete_post'),

			"get:/$projects" => array('GP_Route_Project', 'index'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"post:/$projects/_new" => array('GP_Route_Project', 'new_post'),

			"get:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_get'),
			"post:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_post'),
			"get:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_get'),
			"post:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_post'),
			"/$project/$locale/$dir/export-translations" => array('GP_Route_Translation', 'export_translations_get'),
			// keep this one at the bottom of the project, because it will catch anything starting with project
			"/$project" => array('GP_Route_Project', 'single'),

			"get:/sets/_new" => array('GP_Route_Translation_Set', 'new_get'),
			"post:/sets/_new" => array('GP_Route_Translation_Set', 'new_post'),


		) );
	}

	
	function route() {
		$request_uri = $this->request_uri();
		$request_method = strtolower( $this->request_method() );
		foreach( $this->urls as $re => $func ) {
			foreach (array('get', 'post', 'head', 'put', 'delete') as $method) {
				if ( gp_startswith( $re, $method.':' ) ) {
					if ( $method != $request_method ) continue;
					$re = substr( $re, strlen( $method.':' ));
					break;
				}
			}
			if ( preg_match("@^$re$@", $request_uri, $matches ) ) {
				if ( is_array( $func ) ) {
					$class = new ReflectionClass( $func[0] );
					$route = $class->newInstance();
					$route->before_request();
					call_user_func_array( array( $route, $func[1] ), array_slice( $matches, 1 ) );
					$route->after_request();
				} else {
					call_user_func_array( $func, array_slice( $matches, 1 ) );
				}
				return;
			}
		}
		return gp_tmpl_404();
	}
}

class GP_Route {
	
	var $errors = array();
	var $notices = array();
	
	function before_request() {
	}
	
	function after_request() {
		// set errors and notices
		foreach( $this->notices as $notice ) {
			gp_notice_set( $notice );
		}
	}
}

function gp_route_index() {
	wp_redirect( gp_url_project( '' ) );
}