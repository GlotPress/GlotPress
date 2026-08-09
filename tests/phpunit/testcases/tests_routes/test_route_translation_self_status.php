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

	private function set_status( $set, $translation, $status ) {
		$_POST['status']             = $status;
		$_POST['translation_id']     = $translation->id;
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'update-translation-status-' . $status . '_' . $translation->id );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->set_status( $set->project->path, $set->locale, $set->slug );
			}
		);

		return GP::$translation->get( $translation->id );
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
