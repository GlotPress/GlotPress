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

	function test_import_for_project_should_update_only_changed_originals() {
		$project = $this->factory->project->create();
		$mock_original = new MockOriginal;
		$this->factory->original = new GP_UnitTest_Factory_For_Original( $this->factory, $mock_original );
		$original = $this->factory->original->create( array( 'project_id' => $project->id ) );
		$original->import_for_project( $project, (object)array('entries' => array(new Translation_Entry( array('singular' => $original->singular) ) ) ) );
		
		$this->assertEquals( 2, $GLOBALS['update_invocation_count'], 'update should be invoked only 2 times' );
	}
}