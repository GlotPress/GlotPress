<?php

class GP_Test_Route_Note extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Note';

	function setUp() {
		parent::setUp();
		$this->translation = new GP_Translation;
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

		$_POST['translation_id'] = $translation->id;
		$_POST['note'] = 'Hey I am a note!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $translation->id );

		ob_start();
		$note = $this->route->new_post();
		ob_get_clean();

		$this->assertEquals( $note['note'], $_POST['note'] );
		$this->assertThereIsANoticeContaining( 'created' );
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

		$_POST['translation_id'] = $translation->id;
		$_POST['note'] = 'Hey I am a note to edit!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $translation->id );

		ob_start();
		$note = $this->route->new_post();
		ob_get_clean();
		$_POST['note_id'] = $note['id'];

		$_POST['note'] = 'Hey I am a note edited!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-note-' . $_POST['note_id'] );

		ob_start();
		$note = $this->route->edit_post();
		ob_get_clean();

		$this->assertEquals( $note['note'], $_POST['note'] );
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

		$_POST['translation_id'] = $translation->id;
		$_POST['note'] = 'Hey I am a note to delete!';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'new-note-' . $translation->id );

		ob_start();
		$note = $this->route->new_post();
		ob_get_clean();
		$_POST['note_id'] = $note['id'];

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'deleted-note-' . $_POST['note_id'] );

		ob_start();
		$note = $this->route->delete_post();
		ob_get_clean();

		$this->assertTrue( $note );
		$this->assertThereIsANoticeContaining( 'deleted' );
	}
}
