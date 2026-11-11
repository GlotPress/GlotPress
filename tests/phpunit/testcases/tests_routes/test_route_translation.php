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

	/**
	 * A validator can save a translation as waiting, so someone else reviews it, and the current translation stays.
	 */
	function test_translations_post_saves_as_waiting_for_a_validator_who_asks_for_it() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );
		$current  = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Hallo', 'status' => 'current' ) );

		$_POST['status'] = 'waiting';
		$this->submit_translation( $set, $original->id, $original->id, 'Servus' );

		$this->assertSame( 'waiting', GP::$translation->find_one( array( 'original_id' => $original->id, 'translation_0' => 'Servus' ) )->status );
		$this->assertSame( 'current', GP::$translation->get( $current->id )->status );
	}

	/**
	 * Without asking for it, a validator's translation becomes current as before.
	 */
	function test_translations_post_approves_a_validators_translation_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );

		$this->submit_translation( $set, $original->id, $original->id, 'Hallo' );

		$this->assertSame( 'current', GP::$translation->find_one( array( 'original_id' => $original->id ) )->status );
	}

	/**
	 * The status in the request can't approve the translation of a user who isn't a validator.
	 */
	function test_translations_post_ignores_a_status_from_a_user_who_cannot_approve() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->set_normal_user_as_current();

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );

		$_POST['status'] = 'current';
		$this->submit_translation( $set, $original->id, $original->id, 'Hallo' );

		$this->assertSame( 'waiting', GP::$translation->find_one( array( 'original_id' => $original->id ) )->status );
	}

	/**
	 * A validator saving a waiting or fuzzy translation unchanged approves it, and its translator stays its author.
	 */
	function test_translations_post_approves_an_identical_waiting_or_fuzzy_translation_for_a_validator() {
		foreach ( array( 'waiting', 'fuzzy' ) as $status ) {
			$set        = $this->factory->translation_set->create_with_project_and_locale();
			$translator = $this->factory->user->create();
			$this->become_validator_for_set( $set );

			$original   = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );
			$current    = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Speichern', 'status' => 'current' ) );
			$suggestion = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Änderungen speichern', 'status' => $status, 'user_id' => $translator ) );

			$this->submit_translation( $set, $original->id, $original->id, 'Änderungen speichern' );

			$suggestion = GP::$translation->get( $suggestion->id );
			$this->assertSame( 'current', $suggestion->status, $status );
			$this->assertSame( $translator, (int) $suggestion->user_id, $status );
			$this->assertSame( 'old', GP::$translation->get( $current->id )->status, $status );
			$this->assertCount( 2, GP::$translation->find_many( array( 'original_id' => $original->id ) ), $status );
		}
	}

	/**
	 * Saving doesn't approve an identical waiting translation the validator may not approve, and doesn't replace an identical current one.
	 */
	function test_translations_post_refuses_an_identical_translation_it_cannot_or_need_not_approve() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );
		$waiting  = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Änderungen speichern', 'status' => 'waiting' ) );

		add_filter( 'gp_pre_can_set_translation_status', '__return_false' );
		$this->submit_translation( $set, $original->id, $original->id, 'Änderungen speichern' );
		remove_filter( 'gp_pre_can_set_translation_status', '__return_false' );

		$this->assertSame( 'waiting', GP::$translation->get( $waiting->id )->status );
		$this->assertStringContainsString( 'Identical waiting translation already exists.', $this->route->template_output );

		$current = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Änderungen speichern', 'status' => 'current' ) );

		$this->submit_translation( $set, $original->id, $original->id, 'Änderungen speichern' );

		$this->assertSame( 'current', GP::$translation->get( $current->id )->status );
		$this->assertSame( 'waiting', GP::$translation->get( $waiting->id )->status );
		$this->assertStringContainsString( 'Identical current translation already exists.', $this->route->template_output );
	}

	/**
	 * Saving as waiting doesn't approve an identical waiting translation.
	 */
	function test_translations_post_refuses_an_identical_waiting_translation_when_saving_as_waiting() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );
		$waiting  = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Änderungen speichern', 'status' => 'waiting' ) );

		$_POST['status'] = 'waiting';
		$this->submit_translation( $set, $original->id, $original->id, 'Änderungen speichern' );

		$this->assertSame( 'waiting', GP::$translation->get( $waiting->id )->status );
		$this->assertStringContainsString( 'Identical waiting translation already exists.', $this->route->template_output );
	}

	/**
	 * Saving a fuzzy translation unchanged without the right to approve doesn't copy it under another user's name.
	 */
	function test_translations_post_refuses_a_translation_identical_to_a_fuzzy_one_from_a_user_who_cannot_approve() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->set_normal_user_as_current();

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );
		$fuzzy    = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'translation_0' => 'Änderungen speichern', 'status' => 'fuzzy' ) );

		$this->submit_translation( $set, $original->id, $original->id, 'Änderungen speichern' );

		$this->assertCount( 1, GP::$translation->find_many( array( 'original_id' => $original->id ) ) );
		$this->assertSame( 'fuzzy', GP::$translation->get( $fuzzy->id )->status );
		$this->assertStringContainsString( 'Identical fuzzy translation already exists.', $this->route->template_output );
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

	function test_set_status_names_the_errors_that_prevent_an_approval() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => '%s items' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting', 'translation_0' => '%s Elemente in %s' ) );

		$_POST['status']             = 'current';
		$_POST['translation_id']     = $translation->id;
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'update-translation-status-current_' . $translation->id );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->set_status( $set->project->path, $set->locale, $set->slug );
			}
		);

		$this->assertSame( 'waiting', GP::$translation->get( $translation->id )->status );
		$this->assertStringContainsString( 'The translation contains more placeholders than the original: %s', $this->route->template_output );
	}

	function test_bulk_approve_reports_translations_refused_for_errors() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original_with_errors = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => '%s items' ) );
		$translation_with_errors = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original_with_errors->id, 'status' => 'waiting', 'translation_0' => '%s Elemente in %s' ) );
		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting', 'translation_0' => 'Hallo' ) );

		$_POST['bulk'] = array(
			'action'      => 'approve',
			'row-ids'     => $original_with_errors->id . '-' . $translation_with_errors->id . ',' . $original->id . '-' . $translation->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );

		$this->assertSame( 'current', GP::$translation->get( $translation->id )->status );
		$this->assertSame( 'waiting', GP::$translation->get( $translation_with_errors->id )->status );
		$this->assertThereIsASuccessContaining( '1 translation was approved.' );
		$this->assertThereIsAnErrorContaining( '1 translation was not approved because it has translation errors.' );
		$this->assertCount( 1, $this->route->errors );
	}

	function test_bulk_approve_reports_refused_translations_next_to_other_failures() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original_with_errors    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => '%s items' ) );
		$translation_with_errors = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original_with_errors->id, 'status' => 'waiting', 'translation_0' => '%s Elemente in %s' ) );
		$original                = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Hello' ) );
		$translation             = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'waiting', 'translation_0' => 'Hallo' ) );

		$deny = function ( $verdict, $translation_to_check ) use ( $translation ) {
			return (int) $translation_to_check->id === (int) $translation->id ? false : $verdict;
		};
		add_filter( 'gp_pre_can_set_translation_status', $deny, 10, 2 );

		$_POST['bulk'] = array(
			'action'      => 'approve',
			'row-ids'     => $original_with_errors->id . '-' . $translation_with_errors->id . ',' . $original->id . '-' . $translation->id,
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );
		remove_filter( 'gp_pre_can_set_translation_status', $deny );

		$this->assertThereIsAnErrorContaining( 'Error with approving 1 translation.' );
		$this->assertThereIsAnErrorContaining( '1 translation was not approved because it has translation errors.' );
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

	function test_bulk_copy_original_adds_the_original_as_current_translation() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$singular = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Lisbon' ) );
		$plural   = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => '%s colour', 'plural' => '%s colours' ) );

		$this->bulk_copy_original( $set, array( $singular->id, $plural->id ) );

		$singular_translation = GP::$translation->find_one( array( 'original_id' => $singular->id, 'translation_set_id' => $set->id ) );
		$this->assertSame( 'current', $singular_translation->status );
		$this->assertSame( 'Lisbon', $singular_translation->translation_0 );
		$this->assertSame( get_current_user_id(), $singular_translation->user_id );

		$plural_translation = GP::$translation->find_one( array( 'original_id' => $plural->id, 'translation_set_id' => $set->id ) );
		$this->assertSame( 'current', $plural_translation->status );
		$this->assertSame( array( '%s colour', '%s colours' ), array( $plural_translation->translation_0, $plural_translation->translation_1 ) );

		$this->assertThereIsANoticeContaining( '2 translations were copied from the original.' );
	}

	function test_bulk_copy_original_skips_originals_that_already_have_a_translation() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$translated = array();
		foreach ( array( 'current', 'waiting', 'fuzzy', 'changesrequested' ) as $status ) {
			$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
			$this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => $status ) );
			$translated[ $original->id ] = $status;
		}
		$rejected = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Porto' ) );
		$this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $rejected->id, 'status' => 'rejected' ) );

		// Rows of untranslated originals carry no translation ID, even when a stale page offers one for an original translated since.
		$this->bulk_copy_original( $set, array_merge( array_keys( $translated ), array( $rejected->id ) ) );

		foreach ( $translated as $original_id => $status ) {
			$translations = GP::$translation->find_many( array( 'original_id' => $original_id, 'translation_set_id' => $set->id ) );
			$this->assertCount( 1, $translations );
			$this->assertSame( $status, $translations[0]->status );
		}
		$this->assertSame( 'Porto', GP::$translation->find_one( array( 'original_id' => $rejected->id, 'status' => 'current' ) )->translation_0 );

		// Notices reach the next page through a single cookie, so both messages share one.
		$this->assertCount( 1, $this->route->notices );
		$this->assertThereIsANoticeContaining( '1 translation was copied from the original. 4 strings were skipped because they already have a translation.' );
	}

	function test_bulk_copy_original_ignores_obsolete_originals() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$obsolete = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '-obsolete' ) );

		$this->bulk_copy_original( $set, array( $obsolete->id ) );

		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $obsolete->id ) ) );
	}

	function test_bulk_copy_original_keeps_the_copy_as_suggestion_when_it_cannot_be_approved() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->become_validator_for_set( $set );

		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Lisbon' ) );

		add_filter( 'gp_pre_can_set_translation_status', '__return_false' );
		$this->bulk_copy_original( $set, array( $original->id ) );
		remove_filter( 'gp_pre_can_set_translation_status', '__return_false' );

		$translation = GP::$translation->find_one( array( 'original_id' => $original->id, 'translation_set_id' => $set->id ) );
		$this->assertSame( 'waiting', $translation->status );
		$this->assertSame( 'Lisbon', $translation->translation_0 );
		$this->assertThereIsANoticeContaining( '1 translation was copied from the original as suggestion, as it could not be approved.' );
	}

	private function bulk_copy_original( $set, $original_ids ) {
		$_POST['bulk'] = array(
			'action'      => 'copy-original',
			'row-ids'     => implode( ',', $original_ids ),
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );
	}

	function test_export_strips_control_characters() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Save changes' ) );

		// Created directly, the way an import does, so no blocking error runs.
		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
			'translation_0'      => "Guardar\x04alteracoes",
		) );
		$translation->set_as_current();

		ob_start();
		$this->route->export_translations_get( $set->project->path, $set->locale, $set->slug );
		$po = ob_get_clean();

		$this->assertStringNotContainsString( "\x04", $po );
		$this->assertStringContainsString( 'Guardaralteracoes', $po );
	}

	/**
	 * Tests that translations_post() blocks suggesting an already rejected translation again, whoever suggests it,
	 * but allows validators to submit it.
	 */
	function test_translations_post_blocks_suggesting_a_rejected_translation_again() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Discard' ) );

		$user_1 = $this->set_normal_user_as_current();

		// Rejected translation by user 1.
		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'user_id'            => $user_1,
				'status'             => 'rejected',
				'translation_0'      => 'Verwerfen',
			)
		);

		// 1. User 1 tries to submit the identical rejected text again -> blocked.
		$this->submit_translation( $set, $original->id, $original->id, 'Verwerfen' );

		$this->assertStringContainsString( 'Identical rejected translation already exists.', $this->route->template_output );
		$waiting_for_user_1 = GP::$translation->find_many( array( 'original_id' => $original->id, 'user_id' => $user_1, 'status' => 'waiting' ) );
		$this->assertEmpty( $waiting_for_user_1, 'No new waiting translation must be created for the same user.' );

		// 2. User 2 submits the same text -> blocked too.
		$user_2 = $this->factory->user->create();
		wp_set_current_user( $user_2 );

		$this->submit_translation( $set, $original->id, $original->id, 'Verwerfen' );

		$this->assertStringContainsString( 'Identical rejected translation already exists.', $this->route->template_output );
		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $original->id, 'user_id' => $user_2 ) ) );

		// 3. Validator submits a rejected text on another original -> allowed and directly approved (current).
		$original_2 = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Cancel' ) );
		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original_2->id,
				'user_id'            => $user_1,
				'status'             => 'rejected',
				'translation_0'      => 'Abbrechen',
			)
		);

		$this->become_validator_for_set( $set );

		$this->submit_translation( $set, $original_2->id, $original_2->id, 'Abbrechen' );

		$current_translation = GP::$translation->find_one( array( 'original_id' => $original_2->id, 'status' => 'current' ) );
		$this->assertNotFalse( $current_translation, 'A validator can approve/submit a previously rejected string.' );
	}

	/**
	 * Tests that translations_post() blocks any user from submitting identical text when changes are requested.
	 */
	function test_translations_post_blocks_changesrequested_translation_for_all_users() {
		add_filter( 'gp_enable_changesrequested_status', '__return_true' );

		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Pending' ) );

		$user_1 = $this->set_normal_user_as_current();

		// Translation with changes requested.
		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'user_id'            => $user_1,
				'status'             => 'changesrequested',
				'translation_0'      => 'Ausstehend',
			)
		);

		// 1. User 1 submits the uncorrected string -> blocked.
		$this->submit_translation( $set, $original->id, $original->id, 'Ausstehend' );
		$this->assertStringContainsString( 'Identical translation with changes requested already exists.', $this->route->template_output );

		// 2. User 2 submits the uncorrected string -> also blocked.
		$user_2 = $this->factory->user->create();
		wp_set_current_user( $user_2 );

		$this->submit_translation( $set, $original->id, $original->id, 'Ausstehend' );
		$this->assertStringContainsString( 'Identical translation with changes requested already exists.', $this->route->template_output );
		$this->assertFalse( GP::$translation->find_one( array( 'original_id' => $original->id, 'user_id' => $user_2 ) ) );

		remove_filter( 'gp_enable_changesrequested_status', '__return_true' );
	}

	/**
	 * Without the changes requested status, such translations are shown as rejected, so the message says so too.
	 */
	function test_translations_post_reports_changesrequested_translation_as_rejected_without_the_status() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Pending' ) );

		$this->set_normal_user_as_current();

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'changesrequested',
				'translation_0'      => 'Ausstehend',
			)
		);

		$this->submit_translation( $set, $original->id, $original->id, 'Ausstehend' );

		$this->assertStringContainsString( 'Identical rejected translation already exists.', $this->route->template_output );
	}

	/**
	 * Tests that the gp_translation_block_rejected_or_changesrequested filter can bypass the block.
	 */
	function test_translations_post_filter_can_bypass_block() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'Retry' ) );

		$user = $this->set_normal_user_as_current();

		$this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'user_id'            => $user,
				'status'             => 'rejected',
				'translation_0'      => 'Wiederholen',
			)
		);

		// Filter unblocks the suggestion.
		add_filter( 'gp_translation_block_rejected_or_changesrequested', '__return_false' );

		$this->submit_translation( $set, $original->id, $original->id, 'Wiederholen' );

		remove_filter( 'gp_translation_block_rejected_or_changesrequested', '__return_false' );

		$created = GP::$translation->find_one( array( 'original_id' => $original->id, 'status' => 'waiting' ) );
		$this->assertNotFalse( $created, 'The filter bypassed the duplicate block and created the suggestion.' );
	}
}
