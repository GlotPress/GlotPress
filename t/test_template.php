<?php
require_once('init.php');

class GP_Test_Template_Functions extends GP_UnitTestCase {
	function test_gp_breadcrumb_should_return_empty_string_without_params() {
		$this->assertEquals( '', gp_breadcrumb() );
	}
	
	function test_gp_breadcrumb_should_run_empty_string_through_filter_without_params() {
		$filter = $this->getMock('Dummy', array('breadcrumb_filter'));
		$filter->expects( $this->once() )->method( 'breadcrumb_filter' )->with( $this->equalTo( '' ) );
		add_filter( 'gp_breadcrumb', array( &$filter, 'breadcrumb_filter') );
		gp_breadcrumb();
		remove_filter( 'gp_breadcrumb', array( &$filter, 'breadcrumb_filter') );
	}
	
	function test_gp_breadcrumb_should_join_all_crumbs() {
		gp_breadcrumb( array( 'baba', 'dyado'), array( 'separator' => '', 'breadcrumb-template' => '{breadcrumb}' ) );
		$this->assertEquals( 'babadyado', gp_breadcrumb() );
	}

	function test_gp_breadcrumb_should_use_the_separator_argument() {
		gp_breadcrumb( array( 'baba', 'dyado'), array( 'separator' => '---', 'breadcrumb-template' => '{breadcrumb}' ) );
		$this->assertEquals( 'baba---dyado', gp_breadcrumb() );
	}

	function test_gp_breadcrumb_should_replace_the_separator_argument_in_the_template_too() {
		gp_breadcrumb( array( 'baba', 'dyado'), array( 'separator' => '---', 'breadcrumb-template' => '{separator}xxx---{breadcrumb}' ) );
		$this->assertEquals( '---xxx---baba---dyado', gp_breadcrumb() );
	}

	
	function test_gp_breadcrumb_should_flatten_the_given_array_of_crumbs() {
		gp_breadcrumb( array( 'baba', array( 'dyado' ), 'muu' ), array( 'separator' => '', 'breadcrumb-template' => '{breadcrumb}' ) );
		$this->assertEquals( 'babadyadomuu', gp_breadcrumb() );
		
	}	
}
