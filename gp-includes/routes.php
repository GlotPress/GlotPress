<?php
/**
 * Routing functionality. Decides what code should be run, depending on the request URI
 */

class GP_Router {
	
	var $api_prefix = 'api';
	
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
			return urldecode( $match[1] );
		return false;
	}
	
	function request_method() {
		return gp_array_get( $_SERVER, 'REQUEST_METHOD', 'GET' );
	}
	
	function routes() {
		$dir = '([^_/][^/]*)';
		$path = '(.+?)';
		$projects = 'projects';
		$project = $projects.'/'.$path;
		$id = '(\d+)';
		$locale = '('.implode('|', array_map( create_function( '$x', 'return $x->slug;' ), GP_Locales::locales() ) ).')';
		// overall structure
		return apply_filters( 'routes', array(
			'/' => array('GP_Route_Index', 'index'),
			'get:/login' => array('GP_Route_Login', 'login_get'),
			'post:/login' => array('GP_Route_Login', 'login_post'),
			'get:/logout' => array('GP_Route_Login', 'logout'),

			"get:/$project/import-originals" => array('GP_Route_Project', 'import_originals_get'),
			"post:/$project/import-originals" => array('GP_Route_Project', 'import_originals_post'),

			"get:/$project/_edit" => array('GP_Route_Project', 'edit_get'),
			"post:/$project/_edit" => array('GP_Route_Project', 'edit_post'),

			"get:/$project/_delete" => array('GP_Route_Project', 'delete_get'),
			"post:/$project/_delete" => array('GP_Route_Project', 'delete_post'),

			"post:/$project/_personal" => array('GP_Route_Project', 'personal_options_post'),

			"get:/$projects" => array('GP_Route_Project', 'index'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"post:/$projects/_new" => array('GP_Route_Project', 'new_post'),

			"post:/$project/$locale/$dir/_bulk" => array('GP_Route_Translation', 'bulk_post'),
			"get:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_get'),
			"post:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_post'),
			"get:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_get'),
			"post:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_post'),
			"get:/$project/$locale/$dir/_permissions" => array('GP_Route_Translation', 'permissions_get'),
			"post:/$project/$locale/$dir/_permissions" => array('GP_Route_Translation', 'permissions_post'),
			"get:/$project/$locale/$dir/_permissions/_delete/$dir" => array('GP_Route_Translation', 'permissions_delete'),
			"post:/$project/$locale/$dir/_discard-warning" => array('GP_Route_Translation', 'discard_warning'),
			"/$project/$locale/$dir/export-translations" => array('GP_Route_Translation', 'export_translations_get'),
			// keep this one at the bottom of the project, because it will catch anything starting with project
			"/$project" => array('GP_Route_Project', 'single'),

			"get:/sets/_new" => array('GP_Route_Translation_Set', 'new_get'),
			"post:/sets/_new" => array('GP_Route_Translation_Set', 'new_post'),
			
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
						// make sure after_request() is called even if we exit() in the request
						register_shutdown_function( array( &$route, 'after_request'));
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

class GP_Route {
	
	var $errors = array();
	var $notices = array();
	var $request_running = false;
	
	function die_with_error( $message, $status = 500 ) {
		status_header( $status );
		exit( $message );
	}
	
	function before_request() {
		do_action( 'before_request', $this->class_name, $this->last_method_called );
	}

	function after_request() {
		// we can't unregister a shutdown function
		// this check prevents this method from being run twice
		if ( !$this->request_running ) return;
		// set errors and notices
		if ( !headers_sent() ) {
			foreach( $this->notices as $notice ) {
				gp_notice_set( $notice );
			}
			foreach( $this->errors as $error ) {
				gp_notice_set( $error, 'error' );
			}
		}
		do_action( 'after_request', $this->class_name, $this->last_method_called );
	}

	/**
	 * Validates a thing and add its errors to the route's errors.
	 * 
	 * @param object $thing a GP_Thing instance to validate
	 * @return bool whether the thing is valid
	 */
	function validate( $thing ) {
		$verdict = $thing->validate();
		$this->errors = array_merge( $this->errors, $thing->errors );
		return $verdict;
	}
	
	/**
	 * Same as validate(), but redirects to $url if the thing isn't valid.
	 * 
	 * Note: this method calls exit() after the redirect and the code after it won't
	 * be executed.
	 * 
	 * @param object $thing a GP_Thing instance to validate
	 * @param string $url where to redirect if the thing doesn't validate
	 * @return bool whether the thing is valid
	 */
	function validate_or_redirect( $thing, $url = null ) {
		$verdict = $this->validate( $thing );
		if ( !$verdict ) {
			$this->_redirect( $url );
		}
		return $verdict;
	}
	
	function can( $action, $object_type = null, $object_id = null ) {
		return GP::$user->current()->can( $action, $object_type, $object_id );
	}
	
	function can_or_redirect( $action, $object_type = null, $object_id = null, $url = null ) {
		$can = $this->can( $action, $object_type, $object_id );
		if ( !$can ) {
			$this->redirect_with_error( __('You are not allowed to do that!'), $url );
		}
	}

	function can_or_forbidden( $action, $object_type = null, $object_id = null, $message = 'You are not allowed to do that!' ) {
		$can = $this->can( $action, $object_type, $object_id );
		if ( !$can ) {
			$this->die_with_error( $message, 403 );
		}
	}

	function logged_in_or_forbidden() {
		if ( !GP::$user->logged_in() ) {
			$this->die_with_error( 'Forbidden', 403 );
		}
	}
	
	function redirect_with_error( $message, $url = null ) {
		$this->errors[] = $message;
		$this->_redirect( $url );
	}
	
	function _redirect( $url = null ) {
		// TODO: do not redirect to projects, but to /
		// currently it goes to /projects, because / redirects too and the notice is gone
		if ( is_null( $url ) )  $url = isset( $_SERVER['HTTP_REFERER'] )? $_SERVER['HTTP_REFERER'] : gp_url( '/projects' );
		gp_redirect( $url );
		exit();
	}
	
	function headers_for_download( $filename ) {
		header('Content-Description: File Transfer');
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Content-Disposition: attachment; filename=$filename");
		header("Content-Type: application/octet-stream", true);
		header('Connection: close');
	}
	
	/**
	 * Loads a template.
	 * 
	 * @param string $template template name to load
	 * @param array $args Associative array with arguements, which will be exported in the template PHP file
	 * @param bool|string $honor_api If this is true or 'api' and the route is processing an API request
	 * 		the template name will be suffixed with .api. The actual file loaded will be template.api.php
	 */
	function tmpl( $template, $args = array(), $honor_api = true ) {
		if ( $this->api && $honor_api !== false && 'no-api' !== $honor_api ) {
			$template = $template.'.api';
			header('Content-Type: application/json');
		} else {
			header('Content-Type: text/html; charset=utf-8');
		}
		return gp_tmpl_load( $template, $args );
	}
}