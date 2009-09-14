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

			"get:/$projects" => array('GP_Route_Project', 'index'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"get:/$projects/_new" => array('GP_Route_Project', 'new_get'),
			"post:/$projects/_new" => array('GP_Route_Project', 'new_post'),

			"post:/$project/$locale/$dir/_approve" => array('GP_Route_Translation', 'approve_post'),
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
					$route->request_running = true;
					// make sure after_request() is called even if we exit() in the request
					register_shutdown_function( array( &$route, 'after_request'));
					call_user_func_array( array( $route, $func[1] ), array_slice( $matches, 1 ) );
					$route->after_request();
					$route->request_running = false;
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
	var $request_running = false;
	
	function before_request() {
	}

	function after_request() {
		// we can't unregister this as a shutdown function
		// this prevents the method from being run twice
		if ( !$this->request_running ) return;
		// set errors and notices
		foreach( $this->notices as $notice ) {
			gp_notice_set( $notice );
		}
		foreach( $this->errors as $error ) {
			gp_notice_set( $error, 'error' );
		}
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
	function validate_or_redirect( $thing, $url ) {
		$verdict = $this->validate( $thing );
		if ( !$verdict ) {
			wp_redirect( $url );
			exit();
		}
		return $verdict;
	}
	
	function can( $action, $object_type = null, $object_id = null ) {
		return GP::$user->current()->can( $action, $object_type, $object_id );
	}
	
	function can_or_redirect( $action, $object_type = null, $object_id = null, $url = null ) {
		if ( is_null( $url ) )  $url = isset( $_SERVER['HTTP_REFERER'] )? $_SERVER['HTTP_REFERER'] : gp_url( '/projects' );
		$can = $this->can( $action, $object_type, $object_id );
		if ( !$can ) {
			$this->redirect_with_error( $url, __('You are not allowed to do that!') );
		}
	}
	
	function redirect_with_error( $url, $message ) {
		$this->errors[] = $message;
		wp_redirect( $url );
		exit();
	}
}