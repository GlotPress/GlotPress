<?php

class GP_Test_Route_Translation extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Translation';

	function test_discard_warning_edit_function() {
		$this->set_admin_user_as_current();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba' ) );

		// Create a translation with two warnings.
		$warnings = array(
			0 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ),
			1 => array( 'should_begin_on_newline' => 'Original and translation should both begin on newline.' ),
		);
		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
			'warnings'           => $warnings,
		) );
		$translation->set_as_current();

		$translations = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current' ) );
		$this->assertCount( 2, $translations[0]->warnings );

		// Discard first warning.
		$_POST['translation_id'] = $translation->id;
		$_POST['index'] = 0;
		$_POST['key'] = 'placeholder';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'discard-warning_0placeholder' );
		$this->route->discard_warning( $set->project->path, $set->locale, $set->slug );

		$translations = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current' ) );
		$this->assertCount( 1, $translations[0]->warnings );

		// Discard second warning.
		$_POST['translation_id'] = $translation->id;
		$_POST['index'] = 1;
		$_POST['key'] = 'should_begin_on_newline';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'discard-warning_1should_begin_on_newline' );
		$this->route->discard_warning( $set->project->path, $set->locale, $set->slug );

		$translations = GP::$translation->for_export( $set->project, $set, array( 'status' => 'current' ) );
		$this->assertSame( null, $translations[0]->warnings );
	}

	/**
	 * A bulk "fuzzy" action must only affect translations of the set the request
	 * is authorized for, not translations named by row-ids from another set.
	 */
	function test_bulk_fuzzy_cannot_affect_translations_of_another_set() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$other_original    = $this->factory->original->create( array( 'project_id' => $other_set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );
		$other_translation = $this->factory->translation->create( array( 'translation_set_id' => $other_set->id, 'original_id' => $other_original->id, 'status' => 'current' ) );

		$this->become_validator_for_set( $authorized_set );

		$_POST['bulk'] = array(
			'action'      => 'fuzzy',
			'row-ids'     => $other_original->id . '-' . $other_translation->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $authorized_set->project->path, $authorized_set->locale, $authorized_set->slug );

		$this->assertEquals( 'current', GP::$translation->get( $other_translation->id )->status );
	}

	/**
	 * A bulk "set-priority" action must only affect originals of the project the
	 * request is authorized for, not originals named by row-ids from another project.
	 */
	function test_bulk_set_priority_cannot_affect_originals_of_another_project() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$other_original = $this->factory->original->create( array( 'project_id' => $other_set->project->id, 'status' => '+active', 'singular' => 'Hello', 'priority' => 0 ) );

		$user = $this->become_validator_for_set( $authorized_set );
		GP::$permission->create( array( 'user_id' => $user, 'action' => 'write', 'object_type' => 'project', 'object_id' => $authorized_set->project->id ) );

		$_POST['bulk'] = array(
			'action'      => 'set-priority',
			'priority'    => 1,
			'row-ids'     => $other_original->id . '-0',
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $authorized_set->project->path, $authorized_set->locale, $authorized_set->slug );

		$this->assertEquals( 0, (int) GP::$original->get( $other_original->id )->priority );
	}

	/**
	 * A bulk row must be a genuine (original, translation) pair: a row pairing an
	 * original with a translation that belongs to a different original is dropped.
	 */
	function test_bulk_filter_drops_rows_with_a_mismatched_original_and_translation() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original_a    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'A' ) );
		$original_b     = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'B' ) );
		$translation_b = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original_b->id, 'status' => 'current' ) );

		// The translation belongs to original_b, but the row pairs it with original_a.
		$_POST['bulk'] = array(
			'action'      => 'fuzzy',
			'row-ids'     => $original_a->id . '-' . $translation_b->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );

		$this->assertEquals( 'current', GP::$translation->get( $translation_b->id )->status );
	}
}
