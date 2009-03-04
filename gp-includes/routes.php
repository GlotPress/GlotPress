<?php
/**
 * Routing functionality. Decides what code should be run, depending on the request URI
 */

function gp_get_routes() {
	$dir = '([^/]+)';
	$path = '(.+?)';
	$project = $path;
	// overall structure
	// /project
	return apply_filters( 'routes', array(
		'/' => 'gp_route_index',
		"get:/$project/import-originals" => 'gp_route_project_import_originals_get',
		"post:/$project/import-originals" => 'gp_route_project_import_originals_post',
		// keep this one at the bottom, because it will catch anything
		"/$path" => 'gp_route_project',
	) );
}


class GP_Router {
	
	function GP_Router( $urls ) {
		$this->urls = $urls;
	}
	
	/**
	* Returns the current request URI path, relative to
	* the application URI and without the query string
	*/
	function request_uri() {
		$subdir = rtrim( gp_url_path(), '/' );
		if ( preg_match( "|^$subdir(.*?)(\?.*)?$|", $_SERVER['REQUEST_URI'], $match ) )
			return $match[1];
		return false;
	}
	
	function request_method() {
		return gp_array_get( $_SERVER, 'REQUEST_METHOD', 'GET' );
	}
	
	function route() {
		$request_uri = $this->request_uri();
		$request_method = strtolower( $this->request_method() );
		foreach( $this->urls as $re => $func ) {
			foreach (array('get', 'post', 'head', 'put', 'delete') as $method) {
				if ( gp_startswith( $re, $method.':' ) ) {
					if ( $method != $request_method ) continue;
					$re = substr( $re, strlen( $method.':' ));
				}
			}
			if ( preg_match("|^$re$|", $request_uri, $matches ) ) {
				return call_user_func_array( $func, array_slice( $matches, 1 ) );
			}
		}
		return gp_tmpl_404();
	}	
}