<?php

class GP_Test_Template_Functions extends GP_UnitTestCase {

	function tearDown() {
		parent::tearDown();
		remove_all_filters('gp_breadcrumb_items');
	}

	function test_gp_breadcrumb_should_return_empty_string_without_params() {
		$this->assertEquals( '', gp_breadcrumb() );
	}

	function test_gp_breadcrumb_should_run_empty_string_through_filter_without_params() {
		$filter = $this->getMockBuilder('stdClass')->setMethods(array('breadcrumb_filter'))->getMock();
		$filter->expects( $this->once() )->method( 'breadcrumb_filter' )->with( $this->equalTo( array() ) );

		add_filter( 'gp_breadcrumb_items', array( $filter, 'breadcrumb_filter') );
		gp_breadcrumb();
		remove_filter( 'gp_breadcrumb_items', array( $filter, 'breadcrumb_filter') );
	}

	function test_gp_breadcrumb_should_display_default_list() {
		gp_breadcrumb( array( 'baba', 'dyado') );
		$this->assertEquals( '<ul class="breadcrumb"><li>baba</li><li>dyado</li></ul>', gp_breadcrumb() );
	}

	function test_gp_breadcrumb_should_join_all_crumbs() {
		gp_breadcrumb( array( 'baba', 'dyado') );
		$this->assertEquals( 'babadyado', gp_breadcrumb( null, array( 'before' => '', 'after' => '', 'breadcrumb-template' => '{breadcrumb}' ) ) );
	}

	function test_gp_breadcrumb_should_use_the_before_argument() {
		gp_breadcrumb( array( 'baba', 'dyado') );
		$this->assertEquals( '---baba---dyado', gp_breadcrumb( null, array( 'before' => '---', 'after' => '', 'breadcrumb-template' => '{breadcrumb}' ) ) );
	}

	function test_gp_breadcrumb_should_flatten_the_given_array_of_crumbs() {
		gp_breadcrumb( array( 'baba', array( 'dyado' ), 'muu' ) );
		$this->assertEquals( 'babadyadomuu', gp_breadcrumb( null, array( 'before' => '', 'after' => '', 'breadcrumb-template' => '{breadcrumb}' ) ) );

	}
}
