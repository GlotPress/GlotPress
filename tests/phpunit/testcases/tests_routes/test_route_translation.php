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

	/**
	 * A translation whose set is authorized but whose original belongs to another project (a
	 * "phantom" pairing) must not let a bulk action reach that foreign original.
	 */
	function test_bulk_set_priority_cannot_target_a_cross_project_original_via_a_phantom_translation() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$victim_original = $this->factory->original->create( array( 'project_id' => $other_set->project->id, 'status' => '+active', 'singular' => 'Victim', 'priority' => 0 ) );

		// A translation in the authorized set that points at the other project's original.
		$phantom = $this->factory->translation->create( array( 'translation_set_id' => $authorized_set->id, 'original_id' => $victim_original->id ) );

		$user = $this->become_validator_for_set( $authorized_set );
		GP::$permission->create( array( 'user_id' => $user, 'action' => 'write', 'object_type' => 'project', 'object_id' => $authorized_set->project->id ) );

		$_POST['bulk'] = array(
			'action'      => 'set-priority',
			'priority'    => -2,
			'row-ids'     => $victim_original->id . '-' . $phantom->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $authorized_set->project->path, $authorized_set->locale, $authorized_set->slug );

		$this->assertEquals( 0, (int) GP::$original->get( $victim_original->id )->priority, 'A cross-project original must not be reachable through a phantom translation.' );
	}

	/**
	 * Submitting a translation for an original that belongs to another project must not create it,
	 * so a translation's set and original can never span two projects.
	 */
	function test_translations_post_ignores_an_original_from_another_project() {
		$set   = $this->factory->translation_set->create_with_project_and_locale();
		$other = $this->factory->translation_set->create_with_project_and_locale();

		$foreign_original = $this->factory->original->create( array( 'project_id' => $other->project->id, 'status' => '+active', 'singular' => 'Foreign' ) );

		$this->set_normal_user_as_current();

		$_POST['original_id']        = $foreign_original->id;
		$_POST['translation']        = array( $foreign_original->id => array( 'anything' ) );
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-translation_' . $foreign_original->id );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->translations_post( $set->project->path, $set->locale, $set->slug );
			}
		);

		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $foreign_original->id ) ), 'A translation must not be created for another project\'s original.' );
	}

	private function submit_translation( $set, $nonce_original_id, $submitted_original_id, $text ) {
		$_POST['original_id']        = $nonce_original_id;
		$_POST['translation']        = array( $submitted_original_id => array( $text ) );
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-translation_' . $nonce_original_id );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->translations_post( $set->project->path, $set->locale, $set->slug );
			}
		);

		unset( $_POST['original_id'], $_POST['translation'], $_REQUEST['_gp_route_nonce'] );
	}

	/**
	 * A submitted translation is stored for the original the request is authorized for.
	 */
	function test_translations_post_stores_a_translation_for_the_submitted_original() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Plain' ) );

		$this->become_validator_for_set( $set );

		$this->submit_translation( $set, $original->id, $original->id, 'Stored' );

		$translation = GP::$translation->find_one( array( 'original_id' => $original->id ) );
		$this->assertNotFalse( $translation, 'A translation is created for the submitted original.' );
		$this->assertSame( 'current', $translation->status );
	}

	/**
	 * The originals a request may write to are limited to the one it carries a nonce for.
	 */
	function test_translations_post_ignores_an_original_the_request_is_not_authorized_for() {
		$set   = $this->factory->translation_set->create_with_project_and_locale();
		$first = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'First' ) );
		$other = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Other' ) );

		$this->become_validator_for_set( $set );

		$this->submit_translation( $set, $first->id, $other->id, 'Elsewhere' );

		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $other->id ) ), 'Only the original the nonce covers is written to.' );
	}

	/**
	 * Originals that are kept out of the listings stay out of reach of the submit route too.
	 */
	function test_translations_post_ignores_a_hidden_original_without_write_permission() {
		$set    = $this->factory->translation_set->create_with_project_and_locale();
		$hidden = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Not listed', 'priority' => -2 ) );

		$user_id = $this->become_validator_for_set( $set );
		$this->assertFalse( (bool) GP::$permission->user_can( $user_id, 'write', 'project', $set->project->id ) );

		$this->submit_translation( $set, $hidden->id, $hidden->id, 'Should not be stored' );

		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $hidden->id ) ), 'A hidden original is not written to without write permission.' );
	}

	/**
	 * A user with write permission still sees and edits hidden originals.
	 */
	function test_translations_post_stores_a_hidden_original_with_write_permission() {
		$set    = $this->factory->translation_set->create_with_project_and_locale();
		$hidden = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Not listed', 'priority' => -2 ) );

		$user_id = $this->set_normal_user_as_current();
		GP::$permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'write',
				'object_type' => 'project',
				'object_id'   => $set->project->id,
			)
		);

		$this->submit_translation( $set, $hidden->id, $hidden->id, 'Stored by a writer' );

		$this->assertNotFalse( GP::$translation->find_one( array( 'original_id' => $hidden->id ) ), 'A writer can still translate a hidden original.' );
	}

	/**
	 * The tightened filter must still act on a legitimate in-project translated row.
	 */
	function test_bulk_fuzzy_marks_a_translation_of_the_authorized_set() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'current' ) );

		$_POST['bulk'] = array(
			'action'      => 'fuzzy',
			'row-ids'     => $original->id . '-' . $translation->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );

		$this->assertEquals( 'fuzzy', GP::$translation->get( $translation->id )->status, 'A legitimate in-project translated row must still be acted on.' );
	}
}
