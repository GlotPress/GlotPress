<?php

class GP_Test_Route_Glossary extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary';

	private function load_edit_form( $glossary_id ) {
		$this->route->loaded_template = null;

		$this->do_route_request(
			function () use ( $glossary_id ) {
				$this->route->edit_get( $glossary_id );
			}
		);

		return $this->route->loaded_template;
	}

	/**
	 * The edit form is offered to the users who may act on it.
	 */
	function test_edit_form_is_shown_to_a_validator_of_the_glossary_set() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		$this->become_validator_for_set( $set );

		$this->assertSame( 'glossary-edit', $this->load_edit_form( $glossary->id ) );
	}

	/**
	 * A validator of an unrelated set is not offered the form.
	 */
	function test_edit_form_is_not_shown_to_a_validator_of_another_set() {
		$set       = $this->factory->translation_set->create_with_project_and_locale();
		$other_set = $this->factory->translation_set->create_with_project_and_locale();
		$glossary  = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		$this->become_validator_for_set( $other_set );

		$this->assertNull( $this->load_edit_form( $glossary->id ) );
	}

	/**
	 * A glossary reached from a sub-project set still belongs to the parent set,
	 * so the form follows that set rather than the one in the URL.
	 */
	function test_edit_form_is_not_shown_to_a_validator_of_a_sub_project_set() {
		$parent_set      = $this->factory->translation_set->create_with_project_and_locale();
		$parent_glossary = GP::$glossary->create( array( 'translation_set_id' => $parent_set->id ) );

		$child_project = $this->factory->project->create( array( 'parent_project_id' => $parent_set->project->id ) );
		$child_set     = $this->factory->translation_set->create(
			array(
				'project_id' => $child_project->id,
				'locale'     => $parent_set->locale,
				'slug'       => $parent_set->slug,
			)
		);
		$child_set->project = $child_project;
		$child_set->locale  = $parent_set->locale;
		$child_set->slug    = $parent_set->slug;

		$this->become_validator_for_set( $child_set );

		$this->assertNull( $this->load_edit_form( $parent_glossary->id ) );
	}

	/**
	 * Visitors without a session are not offered the form either.
	 */
	function test_edit_form_is_not_shown_to_a_visitor_without_a_session() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );

		wp_set_current_user( 0 );

		$this->assertNull( $this->load_edit_form( $glossary->id ) );
	}

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
