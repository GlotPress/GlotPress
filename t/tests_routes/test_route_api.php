<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Projects_API extends GP_UnitTestCase_Route {
  var $route_class = 'GP_Route_Project';

  function setUp() {
    parent::setUp();
    $this->set = $this->factory->translation_set->create_with_project_and_locale();
    $this->route->api = true;
  }

  function test_api_projects_list_count() {
    $this->route->index();
    $response = $this->api_response();

    $this->assertEquals( count( GP::$project->all() ), count( $response ) );
  }

  function test_api_projects_list_contains_expected_project() {
    $this->route->index();
    $response = $this->api_response();
    $first_project = reset( $response );

    $this->assertEquals( $this->set->project->id, $first_project->id );
    $this->assertEquals( $this->set->project->name, $first_project->name );
  }

  function test_api_project_has_translation_sets() {
    $this->route->single($this->set->project->path);
    $response = $this->api_response();

    $this->assertObjectHasAttribute( 'translation_sets', $response );
    $this->assertFalse( empty( $response->translation_sets ) );
  }

  function test_api_project_translation_set_exposes_keys_with_counts() {
    $this->route->single($this->set->project->path);
    $response = $this->api_response();

    $first_set = reset( $response->translation_sets );

    $this->assertObjectHasAttribute( 'current_count', $first_set );
    $this->assertObjectHasAttribute( 'untranslated_count', $first_set );
    $this->assertObjectHasAttribute( 'waiting_count', $first_set );
  }
}
