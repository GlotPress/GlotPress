<?php

class GP_Router {

	public $api_prefix = 'api';
	private $urls      = array();

	public function __construct( $urls = array() ) {
		$this->urls = $urls;
	}

	/**
	 * Sets the default routes that GlotPress needs.
	 */
	public function set_default_routes() {
		$this->urls = array_merge( $this->urls, $this->default_routes() );

		/**
		 * Fires after default routes have been set.
		 *
		 * @since 1.0.0
		 *
		 * @param GP_Router $router The router object.
		 */
		do_action( 'gp_router_default_routes_set', $this );
	}

	/**
	 * Returns the current request URI path, relative to
	 * the application URI and without the query string
	 */
	public function request_uri() {
		global $wp;
		$gp_route = '';
		if ( isset( $wp->query_vars['gp_route'] ) ) {
			$gp_route = $wp->query_vars['gp_route'];
		}
		return urldecode( '/' . rtrim( $gp_route, '/' ) );
	}

	public function request_method() {
		return wp_unslash( gp_array_get( $_SERVER, 'REQUEST_METHOD', 'GET' ) );
	}

	public function add( $re, $function, $method = 'get' ) {
		$this->urls[ "$method:$re" ] = $function;
	}

	public function prepend( $re, $function, $method = 'get' ) {
		$this->urls = array( "$method:$re" => $function ) + $this->urls;
	}

	public function remove( $re, $method = 'get' ) {
		if ( isset( $this->urls[ "$method:$re" ] ) ) {
			unset( $this->urls[ "$method:$re" ] );
			return true;
		}

		return false;
	}

	private function default_routes() {
		$dir      = '([^_/][^/]*)';
		$path     = '(.+?)';
		$projects = 'projects';
		$project  = $projects . '/' . $path;
		$id       = '(\d+)';
		$locale   = '(' . implode( '|', wp_list_pluck( GP_Locales::locales(), 'slug' ) ) . ')';
		$set      = "$project/$locale/$dir";

		// overall structure
		return array(
			'/'                                               => array( 'GP_Route_Index', 'index' ),

			'get:/profile'                                    => array( 'GP_Route_Profile', 'profile_view' ),
			"get:/profile/$path"                              => array( 'GP_Route_Profile', 'profile_view' ),

			'get:/settings'                                   => array( 'GP_Route_Settings', 'settings_get' ),
			'post:/settings'                                  => array( 'GP_Route_Settings', 'settings_post' ),

			"get:(/languages)/$locale/$dir/glossary"          => array( 'GP_Route_Glossary_Entry', 'glossary_entries_get' ),
			"post:(/languages)/$locale/$dir/glossary"         => array( 'GP_Route_Glossary_Entry', 'glossary_entries_post' ),
			"post:(/languages)/$locale/$dir/glossary/-new"    => array( 'GP_Route_Glossary_Entry', 'glossary_entry_add_post' ),
			"post:(/languages)/$locale/$dir/glossary/-delete" => array( 'GP_Route_Glossary_Entry', 'glossary_entry_delete_post' ),
			"get:(/languages)/$locale/$dir/glossary/-export"  => array( 'GP_Route_Glossary_Entry', 'export_glossary_entries_get' ),
			"get:(/languages)/$locale/$dir/glossary/-import"  => array( 'GP_Route_Glossary_Entry', 'import_glossary_entries_get' ),
			"post:(/languages)/$locale/$dir/glossary/-import" => array( 'GP_Route_Glossary_Entry', 'import_glossary_entries_post' ),

			'get:/languages'                                  => array( 'GP_Route_Locale', 'locales_get' ),
			"get:/languages/$locale/$path"                    => array( 'GP_Route_Locale', 'single' ),
			"get:/languages/$locale"                          => array( 'GP_Route_Locale', 'single' ),

			"get:/local/$path"                                => array( 'GP_Route_Local', 'import' ),

			"get:/$set/glossary"                              => array( 'GP_Route_Glossary_Entry', 'glossary_entries_get' ),
			"post:/$set/glossary"                             => array( 'GP_Route_Glossary_Entry', 'glossary_entries_post' ),
			"post:/$set/glossary/-new"                        => array( 'GP_Route_Glossary_Entry', 'glossary_entry_add_post' ),
			"post:/$set/glossary/-delete"                     => array( 'GP_Route_Glossary_Entry', 'glossary_entry_delete_post' ),
			"get:/$set/glossary/-export"                      => array( 'GP_Route_Glossary_Entry', 'export_glossary_entries_get' ),
			"get:/$set/glossary/-import"                      => array( 'GP_Route_Glossary_Entry', 'import_glossary_entries_get' ),
			"post:/$set/glossary/-import"                     => array( 'GP_Route_Glossary_Entry', 'import_glossary_entries_post' ),

			"get:/$project/import-originals"                  => array( 'GP_Route_Project', 'import_originals_get' ),
			"post:/$project/import-originals"                 => array( 'GP_Route_Project', 'import_originals_post' ),

			"get:/$project/-edit"                             => array( 'GP_Route_Project', 'edit_get' ),
			"post:/$project/-edit"                            => array( 'GP_Route_Project', 'edit_post' ),

			"get:/$project/-delete"                           => array( 'GP_Route_Project', 'delete_get' ),
			"post:/$project/-delete"                          => array( 'GP_Route_Project', 'delete_post' ),

			"post:/$project/-personal"                        => array( 'GP_Route_Project', 'personal_options_post' ),

			"get:/$project/-permissions"                      => array( 'GP_Route_Project', 'permissions_get' ),
			"post:/$project/-permissions"                     => array( 'GP_Route_Project', 'permissions_post' ),
			"get:/$project/-permissions/-delete/$dir"         => array( 'GP_Route_Project', 'permissions_delete' ),

			"get:/$project/-mass-create-sets"                 => array( 'GP_Route_Project', 'mass_create_sets_get' ),
			"post:/$project/-mass-create-sets"                => array( 'GP_Route_Project', 'mass_create_sets_post' ),
			"post:/$project/-mass-create-sets/preview"        => array( 'GP_Route_Project', 'mass_create_sets_preview_post' ),

			"get:/$project/-branch"                           => array( 'GP_Route_Project', 'branch_project_get' ),
			"post:/$project/-branch"                          => array( 'GP_Route_Project', 'branch_project_post' ),

			"get:/$projects"                                  => array( 'GP_Route_Project', 'index' ),
			"get:/$projects/-new"                             => array( 'GP_Route_Project', 'new_get' ),
			"post:/$projects/-new"                            => array( 'GP_Route_Project', 'new_post' ),

			"post:/$set/-bulk"                                => array( 'GP_Route_Translation', 'bulk_post' ),
			"get:/$set/import-translations"                   => array( 'GP_Route_Translation', 'import_translations_get' ),
			"post:/$set/import-translations"                  => array( 'GP_Route_Translation', 'import_translations_post' ),
			"post:/$set/-discard-warning"                     => array( 'GP_Route_Translation', 'discard_warning' ),
			"post:/$set/-set-status"                          => array( 'GP_Route_Translation', 'set_status' ),

			"/$set/export-translations"                       => array( 'GP_Route_Translation', 'export_translations_get' ),
			// Keep this below all URLs ending with a literal string, because it may catch one of them.
			"get:/$set"                                       => array( 'GP_Route_Translation', 'translations_get' ),
			"post:/$set"                                      => array( 'GP_Route_Translation', 'translations_post' ),

			// Keep this one at the bottom of the project, because it will catch anything starting with project.
			"/$project"                                       => array( 'GP_Route_Project', 'single' ),

			'get:/sets/-new'                                  => array( 'GP_Route_Translation_Set', 'new_get' ),
			'post:/sets/-new'                                 => array( 'GP_Route_Translation_Set', 'new_post' ),
			"get:/sets/$id"                                   => array( 'GP_Route_Translation_Set', 'single' ),
			"get:/sets/$id/-edit"                             => array( 'GP_Route_Translation_Set', 'edit_get' ),
			"post:/sets/$id/-edit"                            => array( 'GP_Route_Translation_Set', 'edit_post' ),
			"get:/sets/$id/-delete"                           => array( 'GP_Route_Translation_Set', 'delete_get' ),
			"post:/sets/$id/-delete"                          => array( 'GP_Route_Translation_Set', 'delete_post' ),

			'get:/glossaries/-new'                            => array( 'GP_Route_Glossary', 'new_get' ),
			'post:/glossaries/-new'                           => array( 'GP_Route_Glossary', 'new_post' ),
			"get:/glossaries/$id/-edit"                       => array( 'GP_Route_Glossary', 'edit_get' ),
			"post:/glossaries/$id/-edit"                      => array( 'GP_Route_Glossary', 'edit_post' ),
			"get:/glossaries/$id/-delete"                     => array( 'GP_Route_Glossary', 'delete_get' ),
			"post:/glossaries/$id/-delete"                    => array( 'GP_Route_Glossary', 'delete_post' ),

			"post:/originals/$id/set_priority"                => array( 'GP_Route_Original', 'set_priority' ),
		);
	}


	public function route() {
		global $wp_query;

		$real_request_uri = $this->request_uri();
		$api_request_uri  = $real_request_uri;
		$request_method   = strtolower( $this->request_method() );
		$api              = gp_startswith( $real_request_uri, '/' . $this->api_prefix . '/' );

		/**
		 * Filter the list of HTTP methods allowed
		 *
		 * @since 2.1
		 *
		 * @param array $http_methods Allowed http methods
		 */
		$http_methods = apply_filters( 'gp_router_http_methods', array( 'get', 'post', 'head', 'put', 'delete' ) );

		if ( $api ) {
			$real_request_uri = substr( $real_request_uri, strlen( $this->api_prefix ) + 1 );
		}

		$url_path = gp_url_path( gp_url_public_root() );

		// If the request URL doesn't match our base URL, don't bother trying to match
		if ( $url_path && ! gp_startswith( wp_unslash( trailingslashit( $_SERVER['REQUEST_URI'] ) ), $url_path ) ) {
			return;
		}

		foreach ( array( $api_request_uri, $real_request_uri ) as $request_uri ) {
			foreach ( $this->urls as $re => $func ) {
				foreach ( $http_methods as $http_method ) {
					if ( gp_startswith( $re, $http_method . ':' ) ) {
						if ( $http_method != $request_method ) {
							continue;
						}
						$re = substr( $re, strlen( $http_method . ':' ) );
						break;
					}
				}

				if ( preg_match( "@^$re$@", $request_uri, $matches ) ) {
					/*
					 * WordPress will be returning a 404 status header by default for GlotPress pages
					 * as nothing is found by WP_Query.
					 * This overrides the status header and the `$is_404` property of WP_Query if we've matched
					 * something here. Route controllers still can return a 404 status header.
					 */
					status_header( '200' );
					$wp_query->is_404 = false;

					if ( is_array( $func ) ) {
						list( $class, $method )    = $func;
						$route                     = new $class();
						$route->api                = $api;
						$route->last_method_called = $method;
						$route->class_name         = $class;
						GP::$current_route         = &$route;
						$route->before_request();
						$route->request_running = true;
						// Make sure after_request() is called even if we $this->exit_() in the request.
						register_shutdown_function( array( &$route, 'after_request' ) );
						$route->$method( ...array_slice( $matches, 1 ) );
						$route->after_request();
						$route->request_running = false;
					} else {
						$func( ...array_slice( $matches, 1 ) );
					}
					exit;
				}
			}
		}

		gp_tmpl_404();
	}

}
