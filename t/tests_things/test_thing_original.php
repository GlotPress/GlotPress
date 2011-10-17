<?php

require_once( dirname( __FILE__ ) . '/../init.php');

global $update_invocation_count;
$update_invocation_count = 0;

class MockOriginal extends GP_Original {
	function update( $data, $where = null ) {
		$GLOBALS['update_invocation_count']++;
		return parent::update( $data, $where );
	}	
}

class GP_Test_Thing_Original extends GP_UnitTestCase {
	
	function create_original_with_update_counter( $original_args = array() ) {
		$project = $this->factory->project->create();
		/* We are doing it this hackish way, because I could not make the PHPUnit mocker to count the update() invocations */
		$mock_original = new MockOriginal;
		$this->factory->original = new GP_UnitTest_Factory_For_Original( $this->factory, $mock_original );
		$original = $this->factory->original->create( array_merge( array( 'project_id' => $project->id ), $original_args ) );
		// the object doesn't retrieve default values, we need to select it back from the database to get them
		$original->reload();
		return array( $project, $original );
	}

	function test_import_for_project_should_not_update_unchanged_originals() {
		list( $project, $original ) = $this->create_original_with_update_counter();
		$original->import_for_project( $project, (object)array('entries' => array(new Translation_Entry( array('singular' => $original->singular) ) ) ) );
		$this->assertEquals( 2, $GLOBALS['update_invocation_count'], 'update should be invoked only 2 times' );
	}
	
	function test_import_for_project_should_update_changed_originals() {
		list( $project, $original ) = $this->create_original_with_update_counter( array(
			'comment' => 'Some comment'
		) );
		$original->import_for_project( $project, (object)array('entries' => array(new Translation_Entry( array('singular' => $original->singular ) ) ) ) );
		$this->assertEquals( 3, $GLOBALS['update_invocation_count'], 'update should be invoked 3 times' );
	}	
	
	function test_should_be_updated_with_should_return_true_if_only_singular_is_for_update_and_it_is_the_same() {
		$original = $this->factory->original->create();
		$this->assertFalse( $original->should_be_updated_with( array( 'singular' => $original->singular ) ) );
	}
	
	function test_should_be_updated_with_should_return_true_if_one_value_is_empty_string_and_the_other_is_null() {
		$original = $this->factory->original->create( array( 'comment' => NULL ) );
		$this->assertFalse( $original->should_be_updated_with( array( 'singular' => $original->singular, 'comment' => '' ) ) );
	}
}