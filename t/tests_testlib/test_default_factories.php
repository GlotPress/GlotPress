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
	
	function test_locale_factory_create() {
		$locale_factory =  new GP_UnitTest_Factory_For_Locale;
		$locale = $locale_factory->create();
		$this->assertEquals( 'aa', $locale->slug );
		$this->assertEquals( 'Locale 1', $locale->english_name );
		$this->assertSame( $locale, GP_Locales::by_slug( $locale->slug ) );
	}
}