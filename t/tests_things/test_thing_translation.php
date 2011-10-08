<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Thing_Translation extends GP_UnitTestCase {
	function test_translation_should_support_6_plurals() {
		$plurals = array( 'translation_0' => 'Zero', 'translation_1' => 'One', 'translation_2' => 'Two', 'translation_3' => 'Three', 'translation_4' => 'Four', 'translation_5' => 'Five' );
		$translation = $this->factory->translation->create( $plurals );
		$this->assertEqualFields( $translation, $plurals );
	}
	
	function test_translation_should_write_all_6_plurals_to_database() {
		$plurals = array( 'translation_0' => 'Zero', 'translation_1' => 'One', 'translation_2' => 'Two', 'translation_3' => 'Three', 'translation_4' => 'Four', 'translation_5' => 'Five' );
		$translation = $this->factory->translation->create( $plurals );
		$translation->reload();
		$this->assertEqualFields( $translation, $plurals );
	}
	
	function test_for_translation_shouldnt_exclude_originals_with_rejected_translation_if_status_has_untranslated() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$translation->reject();
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array( 'status' => 'untranslated' ) );
		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
	}
	
	function test_for_translation_should_include_untranslated_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array(), array('by' => 'translation', 'how' => 'asc') );
		$this->assertEquals( 2, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
		$this->assertEquals( $translation1->id, $for_translation[1]->id );
	}
	
	function test_for_translation_should_not_include_old_by_default() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation1_old = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1_current = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$translation1_current->set_as_current();
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array(), array('by' => 'translation', 'how' => 'asc') );
		$this->assertEquals( 2, count( $for_translation ) );
		$this->assertEquals( null, $for_translation[0]->id );
		$this->assertEquals( $translation1_current->id, $for_translation[1]->id );
	}
	
	
	function test_for_translation_should_not_include_untranslated_for_single_status() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_translation = GP::$translation->for_translation( $set->project, $set, 0, array('status' => 'current'), array('by' => 'translation', 'how' => 'asc') );
		$this->assertEquals( 1, count( $for_translation ) );
		$this->assertEquals( $translation1->id, $for_translation[0]->id );
	}
	
	function test_for_export_should_include_untranslated() {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$original1 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set->project_id ) );
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original1->id, 'status' => 'current' ) );
		$for_export = GP::$translation->for_export( $set->project, $set, 0, array('status' => 'current'), array('by' => 'translation', 'how' => 'asc') );
		$this->assertEquals( 2, count( $for_export ) );
		$this->assertEquals( $translation1->id, $for_export[0]->id );
		
	}
}
