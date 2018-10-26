<?php

class GP_Test_Route_Note extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Note';

	function setUp() {
		parent::setUp();
	}

	function test_add_note_function() {
		$this->set_admin_user_as_current();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );
		$translation->set_as_current();

		$_REQUEST['original_id'] = $set->id;
		$_REQUEST['translation_id'] = $original->id;
		$_REQUEST['note'] = 'Hey I am a note!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $set->id );
		$this->route->new_post();
		$this->assertThereIsANoticeContaining( 'added');
	}

	function test_edit_note_function() {
		$this->set_admin_user_as_current();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );
		$translation->set_as_current();

		$_REQUEST['original_id'] = $set->id;
		$_REQUEST['translation_id'] = $original->id;
		$_REQUEST['note'] = 'Hey I am a note!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $set->id );
		$_REQUEST['note_id'] = $this->route->new_post();

		$_REQUEST['note'] = 'Hey I am a note edited!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-note-' . $_REQUEST['note_id'] );
		$this->route->edit_post();
		$this->assertThereIsANoticeContaining( 'updated' );
	}

	function test_delete_note_function() {
		$this->set_admin_user_as_current();

		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active', 'singular' => 'baba' ) );

		$translation = $this->factory->translation->create( array(
			'translation_set_id' => $set->id,
			'original_id'        => $original->id,
			'status'             => 'current',
		) );
		$translation->set_as_current();

		$_REQUEST['original_id'] = $set->id;
		$_REQUEST['translation_id'] = $original->id;
		$_REQUEST['note'] = 'Hey I am a note!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $set->id );
		$_REQUEST['note_id'] = $this->route->new_post();

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'deleted-note-' . $_REQUEST['note_id'] );
		$this->route->delete_post();
		$this->assertThereIsANoticeContaining( 'deleted' );
	}
}
