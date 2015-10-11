<?php

class GP_UnitTestCase_Route extends GP_UnitTestCase {
	var $route;
	var $route_class;

	function setUp() {
		parent::setUp();
		$this->route = new $this->route_class;
		$this->route->fake_request = true;
		$this->route->errors = array();
		$this->route->notices = array();
	}

	function assertRedirected() {
		$this->assertTrue( $this->route->redirected, "Wasn't redirected" );
	}

	function assertRedirectURLContains( $text ) {
		$this->assertRedirected();
		$this->assertContains( $text, $this->route->redirected_to );
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
