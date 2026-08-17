<?php

class GP_Test_Route_Glossary extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary';

	/**
	 * Editing a glossary must not move it into another translation set via a
	 * client-supplied translation_set_id.
	 */
	function test_edit_cannot_move_glossary_into_another_set() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$glossary = GP::$glossary->create( array( 'translation_set_id' => $authorized_set->id ) );

		$user = $this->become_validator_for_set( $authorized_set );

		$_POST['glossary'] = array(
			'id'                 => $glossary->id,
			'translation_set_id' => $other_set->id, // Attempt to move the glossary to a set the user cannot approve.
			'description'        => 'edited description',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-glossary_' . $glossary->id );

		$this->route->edit_post( $glossary->id );

		$reloaded = GP::$glossary->get( $glossary->id );

		// The glossary stays in its original set...
		$this->assertEquals( $authorized_set->id, (int) $reloaded->translation_set_id );
		// ...while the legitimate part of the edit still applies.
		$this->assertEquals( 'edited description', $reloaded->description );
	}

	/**
	 * Editing a glossary that no longer exists must fail gracefully instead of
	 * dereferencing a missing glossary.
	 */
	function test_edit_of_missing_glossary_fails_gracefully() {
		$this->set_admin_user_as_current();

		$missing_id = 999999;

		$_POST['glossary']           = array( 'description' => 'x', 'translation_set_id' => 1 );
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-glossary_' . $missing_id );

		$this->route->edit_post( $missing_id );

		$this->assertThereIsAnErrorContaining( 'Cannot find glossary' );
	}
}
