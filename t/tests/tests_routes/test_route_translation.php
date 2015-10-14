<?php

class GP_Test_Route_Translation extends GP_UnitTestCase_Route {
	var $route_class = 'GP_Route_Translation';


	/**
	 * @ticket 327
	 */
	function test_discard_warning_edit_function() {
		$set1 = $this->factory->translation_set->create_with_project_and_locale( array( 'locale' => 'bg' ), array( 'name' => 'project_one' ) );

		$project2 = $this->factory->project->create( array( 'name'=>'project_two' ) );
		$set2 = $this->factory->translation_set->create( array( 'locale' => $set1->locale, 'project_id' => $project2->id ) );

		$original1 = $this->factory->original->create( array( 'project_id' => $set1->project_id, 'status' => '+active', 'singular' => 'baba' ) );
		$original2 = $this->factory->original->create( array( 'project_id' => $set2->project_id, 'status' => '+active', 'singular' => 'baba' ) );

		// Create a translation with two warnings
		$warnings = array(
			0 => array( 'placeholder' => 'Missing %2$s placeholder in translation.' ),
			1 => array( 'should_begin_on_newline' => 'Original and translation should both begin on newline.' ),
		);
		$translation1 = $this->factory->translation->create( array( 'translation_set_id' => $set1->id, 'original_id' => $original1->id, 'status' => 'current', 'warnings' => $warnings ) );
		$translation1->set_as_current(); //calls propagate_across_projects

		// Second original shouldn't translated yet because of two warnings.
		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );

		$_POST['translation_id'] = $translation1->id;
		$_POST['index'] = 0;
		$_POST['key'] = 'placeholder';
		$this->route->discard_warning( $project2->path, $set2->locale, $set2->slug );

		// Second original shouldn't translated yet because of one warning.
		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 0, count( $set2_current_translations ) );

		$_POST['translation_id'] = $translation1->id;
		$_POST['index'] = 1;
		$_POST['key'] = 'should_begin_on_newline';
		$this->route->discard_warning( $project2->path, $set2->locale, $set2->slug );

		// Second original should be translated now.
		$set2_current_translations = GP::$translation->for_export( $project2, $set2, array( 'status' => 'current' ) );
		$this->assertEquals( 1, count( $set2_current_translations ) );
	}
}
