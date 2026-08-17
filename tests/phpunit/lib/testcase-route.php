<?php

class GP_UnitTestCase_Route extends GP_UnitTestCase {
	var $route;
	var $route_class;

	function setUp(): void {
		parent::setUp();
		$this->route = new $this->route_class;
		$this->route->fake_request = true;
		$this->route->errors = array();
		$this->route->notices = array();
	}

	/**
	 * Runs a route request and catches the exit a faked request throws when the
	 * route ends the request, so the response can be asserted afterwards.
	 *
	 * @param callable $request Callback that invokes the route method under test.
	 */
	function do_route_request( callable $request ) {
		try {
			$request();
		} catch ( GP_Route_Exit_Exception $e ) {
			// The route ended the request; its state is recorded on $this->route.
		}
	}

	/**
	 * Creates a user with validator (approve) permission on a translation set and makes them the current user.
	 *
	 * @param GP_Translation_Set $set A set with its project/locale/slug available.
	 * @return int The created user id.
	 */
	function become_validator_for_set( $set ) {
		$user = $this->factory->user->create();
		GP::$validator_permission->create(
			array(
				'user_id'     => $user,
				'action'      => 'approve',
				'project_id'  => $set->project->id,
				'locale_slug' => $set->locale,
				'set_slug'    => $set->slug,
			)
		);
		wp_set_current_user( $user );

		return $user;
	}

	function assertRedirected() {
		$this->assertTrue( $this->route->redirected, "Wasn't redirected" );
	}

	function assertRedirectURLContains( $text ) {
		$this->assertRedirected();
		$this->assertStringContainsString( $text, $this->route->redirected_to );
	}

	function assertThereIsAnErrorContaining( $text ) {
		$this->assertThereIsAnArrayElementContaining( $text, $this->route->errors, "No error contains '$text'" );
	}

	function assertThereIsANoticeContaining( $text ) {
		$this->assertThereIsAnArrayElementContaining( $text, $this->route->notices, "No notice contains '$text'" );
	}

	function assertThereIsAnArrayElementContaining( $text, $array, $message = null ) {
		$this->assertGreaterThan( 0, count( $array ), 'The array is empty.' );
		$message = $message? $message : "No array element contains '$text'";

		$this->assertTrue( gp_array_any( function( $e ) use ( $text) { return gp_in( $text, $e ); }, $array ), $message );
	}

	function assertNotAllowedRedirect() {
		$this->assertRedirected();
		$this->assertThereIsAnErrorContaining( 'allowed' );
	}

	function assertInvalidNonceRedirect() {
		$this->assertRedirected();
		$this->assertThereIsAnErrorContaining( 'occurred' );
	}

	function assertInvalidRedirect() {
		$this->assertRedirected();
		$this->assertThereIsAnErrorContaining( 'invalid' );
	}

	function assertErrorRedirect() {
		$this->assertRedirected();
		$this->assertThereIsAnErrorContaining( 'Error' );
	}

	function assertTemplateLoadedIs( $template ) {
		$this->assertTrue( $this->route->rendered_template, "No template was rendered" );
		$this->assertEquals( $template, $this->route->loaded_template );
	}

	function assertTemplateOutputNotEmpty() {
		$this->assertFalse( empty( $this->route->template_output ), "Template output is empty" );
	}

	function assert404() {
		$this->assertTemplateLoadedIs( '404' );
	}

  /**
   * Parses and returns the API response.
   */
  function api_response() {
    return json_decode( $this->route->template_output );
  }

}
