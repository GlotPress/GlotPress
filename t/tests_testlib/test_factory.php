<?php
require_once( dirname( __FILE__ ) . '/../init.php');

class GP_Thing_Test_Factory {
	function create( $args ) {
		
	}
	
	function save( $args ) {
	}
}

class GP_Test_Unittest_Factory extends GP_UnitTestCase {

	function create_factory( $field_names = array(), $defaults = array() ) {
		$thing = (object)compact( 'field_names' );
		$factory = new GP_UnitTest_Factory_For_Thing( null, $thing, $defaults );
		return $factory;
	}	
	
	function test_generator_sequence_should_start_with_1() {
		$sequence = new GP_UnitTest_Generator_Sequence();		
		$this->assertEquals( 1, $sequence->next() );
	}

	function test_generator_sequence_should_generate_consecutive_values() {
		$sequence = new GP_UnitTest_Generator_Sequence();
		$this->assertEquals( 1, $sequence->next() );
		$this->assertEquals( 2, $sequence->next() );
		$this->assertEquals( 3, $sequence->next() );
		$this->assertEquals( 4, $sequence->next() );
	}
	
	function test_generator_sequence_should_include_value_in_template() {
		$sequence = new GP_UnitTest_Generator_Sequence( 'Baba %s Dyado' );
		$this->assertEquals( 'Baba 1 Dyado', $sequence->next() );
	}
	
	function test_generator_sequence_should_start_with_2() {
		$sequence = new GP_UnitTest_Generator_Sequence( '%s', 2 );
		$this->assertEquals( 2, $sequence->next() );		
	}
	
	function test_generator_sequence_should_generate_consecutive_values_in_template() {
		$sequence = new GP_UnitTest_Generator_Sequence( 'Baba %s' );
		$this->assertEquals( 'Baba 1', $sequence->next() );
		$this->assertEquals( 'Baba 2', $sequence->next() );
		$this->assertEquals( 'Baba 3', $sequence->next() );
	}
	
	function test_generator_locale_name_should_start_with_aa() {
		$locale_name = new GP_UnitTest_Generator_Locale_Name;
		$this->assertEquals( 'aa', $locale_name->next() );
	}

	function test_generator_locale_name_should_generate_consecutive_values() {
		$locale_name = new GP_UnitTest_Generator_Locale_Name;
		$this->assertEquals( 'aa', $locale_name->next() );
		$this->assertEquals( 'ab', $locale_name->next() );
		$this->assertEquals( 'ac', $locale_name->next() );
		$this->assertEquals( 'ad', $locale_name->next() );
	}

	function test_factory_for_thing_should_construct_with_factory_and_thing_object() {
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
		$create_args = array( 'name' => 'value' );
		$thing = $this->create_thing_mock_with_name_field_and_with_create_which_should_be_called_once_with( $create_args );
		$factory->thing = $thing;
		$factory->create( $create_args );
	}
	
	private function create_thing_mock_with_name_field_and_with_create_which_should_be_called_once_with( $expected_create_args ) {
		$thing = $this->getMock( 'GP_Thing_Test_Factory' );
		$thing->field_names = array('name');
		$thing->expects( $this->once() )->method( 'create' )->with( $this->equalTo( $expected_create_args ) );
		return $thing;
	}
	
	function test_factory_for_thing_create_should_use_function_generator() {
		$generation_defintions = array(
			'full_name' => GP_UnitTest_Factory_For_Thing::callback( create_function( '$o', 'return $o->name . " baba";' ) ),
		);
		$factory = $this->create_factory( null, $generation_defintions );
		$create_args = array('name' => 'my name is');
		$updated_args = array('full_name' => 'my name is baba');
		$thing = $this->create_thing_stub_with_name_and_full_name_which_on_create_returns_mock_whose_save_should_be_called_with( $create_args, $updated_args );
		$factory->thing = $thing;
		$factory->create( $create_args );
	}

	private function create_thing_stub_with_name_and_full_name_which_on_create_returns_mock_whose_save_should_be_called_with( $create_args, $expected_save_args ) {
		$thing = $this->getMock( 'GP_Thing_Test_Factory' );
		$thing->field_names = array('name', 'full_name');
		$created_thing = $this->getMock( 'GP_Thing_Test_Factory' );
		foreach( $create_args as $name => $value ) {
			$created_thing->$name = $value;
		}
		$created_thing->expects( $this->once() )->method( 'save' )->with( $this->equalTo( $expected_save_args ) );
		$thing->expects( $this->once() )->method( 'create' )->will( $this->returnValue( $created_thing ) );
		return $thing;
	}	
}