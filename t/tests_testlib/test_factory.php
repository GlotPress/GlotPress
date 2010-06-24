<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Thing_Test_Factory {
	function create( $args ) {
		return true;
	}
}

class GP_Test_Unittest_Factory extends GP_UnitTestCase {

	function create_factory( $field_names = array(), $defaults = array() ) {
		$thing = (object)compact( 'field_names' );
		$factory = new GP_UnitTest_Factory_For_Thing( $thing, $defaults );
		return $factory;
	}	
	
	function test_generator_sequence_should_start_with_1() {
		$sequence = new GP_UnitTest_Generator_Sequence();		
		$this->assertEquals( 1, $sequence->next() );
	}
	
	function test_generator_sequence_should_include_value_in_template() {
		$sequence = new GP_UnitTest_Generator_Sequence( 'Baba %s Dyado' );
		$this->assertEquals( 'Baba 1 Dyado', $sequence->next() );
	}
	
	function test_generator_sequence_should_start_with_2() {
		$sequence = new GP_UnitTest_Generator_Sequence( '%s', 2 );
		$this->assertEquals( 2, $sequence->next() );		
	}
	
	function test_generator_sequence_should_generate_consecutive_values() {
		$sequence = new GP_UnitTest_Generator_Sequence();
		$this->assertEquals( 1, $sequence->next() );
		$this->assertEquals( 2, $sequence->next() );
		$this->assertEquals( 3, $sequence->next() );
		$this->assertEquals( 4, $sequence->next() );
	}

	function test_generator_sequence_should_generate_consecutive_values_in_template() {
		$sequence = new GP_UnitTest_Generator_Sequence( 'Baba %s' );
		$this->assertEquals( 'Baba 1', $sequence->next() );
		$this->assertEquals( 'Baba 2', $sequence->next() );
		$this->assertEquals( 'Baba 3', $sequence->next() );
	}
	
	function test_factory_for_thing_should_construct_with_object() {
		$factory = $this->create_factory();
		$this->assertTrue( is_object( $factory->thing ) );
	}

	function test_factory_for_thing_generate_args_should_not_touch_args_if_no_generation_definitions() {
		$factory = $this->create_factory( array('name') );
		$args = array( 'name' => 'value' );
		$this->assertEquals( $args, $factory->generate_args( $args, array() ) );
	}

	function test_factory_for_thing_generate_args_should_not_touch_args_if_different_generation_defintions() {
		$factory = $this->create_factory( array('name') );
		$args = array( 'name' => 'value' );
		$this->assertEquals( $args, $factory->generate_args( $args ), array( 'other_name' => 5 ) );
	}

	function test_factory_for_thing_generate_args_should_set_undefined_scalar_values() {
		$factory = $this->create_factory( array('name') );
		$this->assertEquals( array('name' => 'default'), $factory->generate_args( array(), array( 'name' => 'default' ) ) );
	}
	
	function test_factory_for_thing_generate_args_should_use_generator() {
		$generator_stub = $this->getMock( 'GP_UnitTest_Generator_Sequence' );
		$generator_stub->expects( $this->exactly( 2 ) )->method( 'next' )->will( $this->onConsecutiveCalls( 'name 1', 'name 2' ) );
		$factory = $this->create_factory( array('name') );
		$generation_defintions = array( 'name' =>  $generator_stub );
		$this->assertEquals( array( 'name' => 'name 1'), $factory->generate_args( array(), $generation_defintions ) );
		$this->assertEquals( array( 'name' => 'name 2'), $factory->generate_args( array(), $generation_defintions ) );
	}

	function test_factory_for_thing_generate_args_should_return_error_on_bad_default_value() {
		$factory = $this->create_factory( array('name') );
		$this->assertWPError( $factory->generate_args( array(), array( 'name' => array( 'non-scalar default value' ) ) ) );
	}	

	function test_factory_for_thing_generate_args_should_use_default_generator_definition_if_non_given() {
		$factory = $this->create_factory( array('name'), array('name' => 'default') );
		$this->assertEquals( array('name' => 'default'), $factory->generate_args( array() ) );
	}
	
	function test_factory_for_thing_create_should_call_create_once() {
		$factory = $this->create_factory();
		$thing = $this->getMock( 'GP_Thing_Test_Factory' );
		$thing->field_names = array('name');
		$args = array( 'name' => 'value' );
		$thing->expects( $this->once() )->method( 'create' )->with( $this->equalTo( $args ) );
		$factory->thing = $thing;
		$factory->create( $args );
	}
}