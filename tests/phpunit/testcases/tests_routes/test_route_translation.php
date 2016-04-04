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
}
