<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Test_Default_Factories extends GP_UnitTestCase {
	function test_project_factory_create() {
		$project_factory = new GP_UnitTest_Factory_For_Project;
		$project = $project_factory->create();
		$this->assertEquals( 'Project 1', $project->name );
	}
	
	function test_project_factory_original() {
		$original_factory = new GP_UnitTest_Factory_For_Original;
		$original = $original_factory->create();
		$this->assertEquals( 'Original 1', $original->singular );
	}	
}