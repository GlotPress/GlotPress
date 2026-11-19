<?php

class GP_Test_Route_Translation_Self_Status extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Translation';

	private function create_waiting_translation_for( $set, $user_id ) {
		$original = $this->factory->original->create(
			array(
				'project_id' => $set->project->id,
				'status'     => '+active',
			)
		);

		return $this->factory->translation->create(
			array(
				'translation_set_id' => $set->id,
				'original_id'        => $original->id,
				'status'             => 'waiting',
				'user_id'            => $user_id,
			)
		);
	}

	private function bulk_post( $set, $action, $translations ) {
		$row_ids = array();
		foreach ( $translations as $translation ) {
			$row_ids[] = $translation->original_id . '-' . $translation->id;
		}

		$_POST['bulk']               = array(
			'action'      => $action,
			'priority'    => '1',
			'row-ids'     => implode( ',', $row_ids ),
			'redirect_to' => '/',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'bulk-actions' );

		$this->route->bulk_post( $set->project->path, $set->locale, $set->slug );
	}

	public function test_user_can_bulk_reject_only_their_own_waiting_translations() {
		$set               = $this->factory->translation_set->create_with_project_and_locale();
		$other_translation = $this->create_waiting_translation_for( $set, $this->factory->user->create() );
		$user_id           = $this->set_normal_user_as_current();
		$own_translation   = $this->create_waiting_translation_for( $set, $user_id );

		$this->bulk_post( $set, 'reject', array( $own_translation, $other_translation ) );

		$this->assertSame( 'rejected', GP::$translation->get( $own_translation->id )->status, 'A user may bulk reject their own waiting translation.' );
		$this->assertSame( 'waiting', GP::$translation->get( $other_translation->id )->status, 'A user without approval rights must not reject someone else’s translation.' );
		$this->assertThereIsASuccessContaining( '1 translation was rejected.' );
		$this->assertThereIsAnErrorContaining( '1 translation was skipped because you are not allowed to change its status.' );
	}

	public function test_user_cannot_bulk_approve_their_own_waiting_translations() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$user_id     = $this->set_normal_user_as_current();
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$this->bulk_post( $set, 'approve', array( $translation ) );

		$this->assertSame( 'waiting', GP::$translation->get( $translation->id )->status, 'A user without approval rights must not approve their own translation.' );
		$this->assertSame( array( '1 translation was skipped because you are not allowed to change its status.' ), $this->route->errors );
	}

	public function test_user_without_approval_rights_cannot_run_bulk_actions_that_do_not_set_a_status() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$user_id     = $this->set_normal_user_as_current();
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$this->bulk_post( $set, 'set-priority', array( $translation ) );

		$this->assertSame( '0', (string) GP::$original->get( $translation->original_id )->priority, 'A user without approval rights must not change priorities.' );
		$this->assertNotAllowedRedirect();
	}

	public function test_user_cannot_approve_their_own_waiting_translation() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$user_id     = $this->set_normal_user_as_current();
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$reloaded = $this->set_status( $set, $translation, 'current' );

		$this->assertSame( 'waiting', $reloaded->status, 'A user without approval rights must not approve their own translation.' );
		$this->assertSame( 403, $this->route->http_status, 'The route should forbid the change instead of failing later.' );
	}

	public function test_user_cannot_request_changes_on_their_own_waiting_translation() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$user_id     = $this->set_normal_user_as_current();
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$reloaded = $this->set_status( $set, $translation, 'changesrequested' );

		$this->assertSame( 'waiting', $reloaded->status, 'A user without approval rights must not request changes on their own translation.' );
		$this->assertSame( 403, $this->route->http_status, 'The route should forbid the change instead of failing later.' );
	}

	public function test_user_can_reject_their_own_waiting_translation() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$user_id     = $this->set_normal_user_as_current();
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$reloaded = $this->set_status( $set, $translation, 'rejected' );

		$this->assertSame( 'rejected', $reloaded->status, 'A user may reject their own waiting translation.' );
	}

	public function test_validator_can_approve_their_own_waiting_translation() {
		$set     = $this->factory->translation_set->create_with_project_and_locale();
		$user_id = $this->set_normal_user_as_current();
		GP::$validator_permission->create(
			array(
				'user_id'     => $user_id,
				'action'      => 'approve',
				'project_id'  => $set->project->id,
				'locale_slug' => $set->locale,
				'set_slug'    => $set->slug,
			)
		);
		$translation = $this->create_waiting_translation_for( $set, $user_id );

		$reloaded = $this->set_status( $set, $translation, 'current' );

		$this->assertSame( 'current', $reloaded->status, 'A validator may approve, including their own translation.' );
	}
}
