<?php

class GP_Test_Note extends GP_UnitTestCase {

	function setUp() {
		parent::setUp();
		$this->route = new GP_Route_Note;
		$this->notes = new GP_Note;
	}

	function test_save() {
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
		$_POST['original_id'] = $original->id;
		$_POST['note'] = 'Hey I am a note!';

		$note = $this->notes->save();

		$this->assertEquals( $note->note, $_POST['note'] );
	}

	function test_edit() {
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
		$_POST['original_id'] = $original->id;
		$_POST['note'] = 'Hey I am a note!';

		$note = $this->notes->save();

		$note_content = 'Hey I am a note edited!';
		$note = $this->notes->edit( $note->id, $note_content, $translation );

		$this->assertEquals( $note->note, $note_content );
	}
}
