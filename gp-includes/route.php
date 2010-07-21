<?php
/**
 * Base controller class
 */
class GP_Route {
	
	var $api = false;
	
	var $errors = array();
	var $notices = array();
	var $request_running = false;
	var $template_path = null;
	
	var $fake_request = false;
	var $exited = false;
	var $exit_message;
	var $redirected = false;
	var $redirected_to = null;
	var $rendered_template = false;
	var $loaded_template = null;
	var $template_output = null;
	var $headers = array();
		
	function __construct() {
		
	}
	
	function die_with_error( $message, $status = 500 ) {
		$this->status_header( $status );
		$this->exit_( $message );
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
			$this->set_notices_and_errors();
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
	 * Note: this method calls $this->exit_() after the redirect and the code after it won't
	 * be executed.
	 * 
	 * @param object $thing a GP_Thing instance to validate
	 * @param string $url where to redirect if the thing doesn't validate
	 * @return bool whether the thing is valid
	 */
	function invalid_and_redirect( $thing, $url = null ) {
		$valid = $this->validate( $thing );
		if ( !$valid ) {
			$this->redirect( $url );
			return true;
		}
		return false;
	}
	
	function can( $action, $object_type = null, $object_id = null ) {
		return GP::$user->current()->can( $action, $object_type, $object_id );
	}
	
	/**
	 * If the current user isn't allowed to do an action, redirect and exit the current request
	 * 
	 * @param string $action
	 * @param`string $object_type
	 * @param string $object_id
	 * @param string $url	The URL to redirect. Default value: referrer or index page, if referrer is missing
	 */
	function cannot_and_redirect( $action, $object_type = null, $object_id = null, $url = null ) {
		$can = $this->can( $action, $object_type, $object_id );
		if ( !$can ) {
			$this->redirect_with_error( __('You are not allowed to do that!'), $url );
			return true;
		}
		return false;
	}

	function can_or_forbidden( $action, $object_type = null, $object_id = null, $message = 'You are not allowed to do that!' ) {
		$can = $this->can( $action, $object_type, $object_id );
		if ( !$can ) {
			$this->die_with_error( $message, 403 );
		}
		return false;
	}

	function logged_in_or_forbidden() {
		if ( !GP::$user->logged_in() ) {
			$this->die_with_error( 'Forbidden', 403 );
		}
	}
	
	function redirect_with_error( $message, $url = null ) {
		$this->errors[] = $message;
		$this->redirect( $url );
	}
	
	function redirect( $url = null ) {
		if ( $this->fake_request ) {
			$this->redirected = true;
			$this->redirected_to = $url;
			return;
		}

		$this->set_notices_and_errors();
		// TODO: do not redirect to projects, but to /
		// currently it goes to /projects, because / redirects too and the notice is gone
		if ( is_null( $url ) )  $url = isset( $_SERVER['HTTP_REFERER'] )? $_SERVER['HTTP_REFERER'] : gp_url( '/projects' );
		gp_redirect( $url );
		$this->tmpl( 'redirect', compact( 'url' ) );
	}
	
	function headers_for_download( $filename ) {
		$this->header('Content-Description: File Transfer');
		$this->header('Pragma: public');
		$this->header('Expires: 0');
		$this->header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		$this->header("Content-Disposition: attachment; filename=$filename");
		$this->header("Content-Type: application/octet-stream", true);
		$this->header('Connection: close');
	}

	function set_notices_and_errors() {
		if ( $this->fake_request ) return;
		
		foreach( $this->notices as $notice ) {
			gp_notice_set( $notice );
		}
		$this->notices = array();
		
		foreach( $this->errors as $error ) {
			gp_notice_set( $error, 'error' );
		}
		$this->errors = array();
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
		if ( $this->fake_request ) {
			$this->rendered_template = true;
			$this->loaded_template = $template;
		}
		$this->set_notices_and_errors();
		if ( $this->api && $honor_api !== false && 'no-api' !== $honor_api ) {
			$template = $template.'.api';
			$this->header('Content-Type: application/json');
		} else {
			$this->header('Content-Type: text/html; charset=utf-8');
		}
		if ( $this->fake_request ) {
			$this->template_output = gp_tmpl_get_output( $template, $args, $this->template_path );
			return true;
		}
		
		return gp_tmpl_load( $template, $args, $this->template_path );
	}
	
	function tmpl_404( $args ) {
		$this->tmpl( '404', $args + array('title' => __('Not Found'), 'http_status' => 404 ) );
	}
	
	function exit_( $message = 0 ) {
		if ( $this->fake_request ) {
			$this->exited = true;
			$this->exit_message = $message;
		}
		exit( $message );
	}
	
	function header( $string ) {
		if ( $this->fake_request ) {
			list( $header, $value ) = explode( ':', $string, 2 );
			$this->headers[$header] = $value;
		} else {
			header( $string );
		}		
	}
	
	function status_header( $status ) {
		if ( $this->fake_request ) {
			$this->http_status = $status;
			return;
		}
		return status_header( $status );
	}
}