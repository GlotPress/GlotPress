<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Route_Translation_Set extends GP_UnitTestCase_Route {
	var $route_class = 'GP_Route_Translation_Set';
	
	function setUp() {
		parent::setUp();
		$this->set = $this->factory->translation_set->create_with_project_and_locale();
	}
	
	function test_single_with_a_non_existent_set_gives_404() {
		$this->route->single( 11 );
	}
	
	function test_single_redirects_to_existing_set() {
		$set = $this->factory->translation_set->create_with_project_and_locale( array( 'slug' => 'baba' ) );
		$this->route->single( $set->id );
		$this->assertRedirectURLContains( 'baba' );
	}
	
	function test_new_get_forbidden_redirect_if_not_logged_in() {
		$this->route->new_get();
		$this->assertNotAllowedRedirect();
	}
		
	function test_new_get_forbidden_redirect_if_logged_in_but_without_sufficient_permissions() {
		$this->set_normal_user_as_current();
		$this->route->new_get();
		$this->assertNotAllowedRedirect();
	}
	
	function test_new_get_admin_can_view_the_form() {
		$this->set_admin_user_as_current();
		$this->route->new_get();
		$this->assertTemplateLoadedIs( 'translation-set-new' );
		$this->assertTemplateOutputNotEmpty();
	}
	
	function test_new_post_forbidden_redirect_if_not_logged_in() {
		$this->route->new_post();
		$this->assertNotAllowedRedirect();
	}
	
	function test_new_post_forbidden_redirect_if_logged_in_but_without_sufficient_permissions() {
		$this->set_normal_user_as_current();
		$this->route->new_post();
		$this->assertNotAllowedRedirect();
	}
	
	function test_new_post_invalid_redirect_if_empty_set() {
		$this->set_admin_user_as_current();
		$this->route->new_post();
		$this->assertInvalidRedirect();
	}

	function test_new_post_error_redirect_if_create_was_unsuccessful() {
		$this->set_admin_user_as_current();
		GP::$translation_set = $this->getMock( 'GP_Translation_Set', array( 'create_and_select' ) );
		GP::$translation_set->expects( $this->any() )->method( 'create_and_select' );
		$_POST['set'] = $this->factory->translation_set->generate_args();
		$this->route->new_post();
		$this->assertErrorRedirect();
	}
	
	function test_new_post_should_add_notice_if_create_was_successful() {
		$this->set_admin_user_as_current();		
		$_POST['set'] = $this->factory->translation_set->generate_args();
		$this->route->new_post();
		$this->assertThereIsANoticeContaining( 'created' );
	}
		
	function test_edit_get_forbidden_redirect_if_not_logged_in() {
		$this->route->edit_get( $this->set->id );
		$this->assertNotAllowedRedirect();
	}
	
	function test_edit_get_forbidden_redirect_if_logged_in_but_without_sufficient_permissions() {
		$this->set_normal_user_as_current();
		$this->route->edit_get( $this->set->id );
		$this->assertNotAllowedRedirect();
	}
	
	function test_edit_get_admin_can_view_the_form() {
		$this->set_admin_user_as_current();
		$this->route->edit_get( $this->set->id );
		$this->assertTemplateLoadedIs( 'translation-set-edit' );
		$this->assertTemplateOutputNotEmpty();
	}
	
	function test_edit_post_with_a_non_existent_set_gives_404() {
		$this->route->edit_post( 11 );
	}
	
	function test_edit_post_forbidden_redirect_if_not_logged_in() {
		$this->route->edit_post( $this->set->id );
		$this->assertNotAllowedRedirect();
	}
	
	function test_edit_post_forbidden_redirect_if_logged_in_but_without_sufficient_permissions() {
		$this->set_normal_user_as_current();
		$this->route->edit_post( $this->set->id );
		$this->assertNotAllowedRedirect();
	}
	
	function test_edit_post_invalid_redirect_if_empty_set() {
		$this->set_admin_user_as_current();
		$this->route->edit_post( $this->set->id );
		$this->assertInvalidRedirect();
	}
	
	function test_edit_post_invalid_redirect_if_missing_name() {
		$this->set_admin_user_as_current();
		$_POST['set'] = $this->factory->translation_set->generate_args();
		unset( $_POST['set']['name'] );
		$this->route->edit_post( $this->set->id );
		$this->assertInvalidRedirect();
	}

	function test_edit_post_should_add_notice_if_update_was_successful() {
		$this->set_admin_user_as_current();
		$_POST['set'] = $this->factory->translation_set->generate_args();
		$this->route->edit_post( $this->set->id );
		$this->assertThereIsANoticeContaining( 'updated' );
	}
	
	function test_edit_post_error_redirect_if_create_was_unsuccessful() {
		$this->set_admin_user_as_current();
		GP::$translation_set = $this->getMock( 'GP_Translation_Set', array( 'update' ) );
		$_POST['set'] = $this->factory->translation_set->generate_args();
		$this->route->edit_post( $this->set->id );
		$this->assertErrorRedirect();
	}

	function test_edit_post_the_set_name_should_be_updated() {
		$this->set_admin_user_as_current();
		$_POST['set'] = $this->factory->translation_set->generate_args();
		$this->route->edit_post( $this->set->id );
		$this->set->reload();
		$this->assertEquals( $_POST['set']['name'], $this->set->name );
	}
}
