<?php
/**
 * Routing functionality. Decides what code should be run, depending on the request URI
 */

function gp_get_routes() {
	$dir = '([^/]+)';
	$path = '(.+?)';
	$project = 'project/'.$path;
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
		"post:/$project" => array('GP_Route_Project', 'edit_post'),
		
		"get:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_get'),
		"post:/$project/$locale/$dir" => array('GP_Route_Translation', 'translations_post'),
		"get:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_get'),
		"post:/$project/$locale/$dir/import-translations" => array('GP_Route_Translation', 'import_translations_post'),
		"/$project/$locale/$dir/export-translations" => array('GP_Route_Translation', 'export_translations_get'),
		// keep this one at the bottom of the project, because it will catch anything starting with project
		"/$project" => array('GP_Route_Project', 'index'),
		
		
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
		if ( preg_match( "@^$subdir(.*?)(\?.*)?$@", $_SERVER['REQUEST_URI'], $match ) )
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
			if ( preg_match("@^$re$@", $request_uri, $matches ) ) {
				return call_user_func_array( $func, array_slice( $matches, 1 ) );
			}
		}
		return gp_tmpl_404();
	}
}