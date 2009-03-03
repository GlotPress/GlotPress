<?php
/**
 * Routing functionality. Decides what code should be run, depending on the request URI
 */

function gp_get_routes() {
	$dir = '([^/]+)';
	$path = '(.+?)';
	$locale = $project = $dir;
	// overall structure
	// /project
	return apply_filters( 'routes', array(
		'/' => 'gp_route_index',
		"/$path/import-sources" => 'gp_route_project_import_sources',
		"/$path/$locale/translations/?(\d+)?" => 'gp_route_project_translations',
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
	
	function route() {
		$request_uri = $this->request_uri();
		foreach( $this->urls as $re => $func ) {
			if ( preg_match("|^$re$|", $request_uri, $matches ) ) {
				return call_user_func_array( $func, array_slice( $matches, 1 ) );
			}
		}
		return gp_tmpl_404();
	}	
}