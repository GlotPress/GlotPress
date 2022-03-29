<?php

class Mouse extends GP_Thing {
	var $table_basename = 'mice';
	var $field_names = array( 'id', 'name', 'rating', 'cat_id' );
	var $non_updatable_attributes = array( 'id', );

	function restrict_fields( $rules ) {
		$rules->name_should_not_be('empty');
		$rules->rating_should_be('positive_int');
	}

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['cat_id'] ) ) {
			$args['cat_id'] = $this->force_false_to_null( $args['cat_id'] );
		}
		return $args;
	}
}


class GP_Test_Validation extends GP_UnitTestCase {
	function setUp() {
		parent::setUp();

		global $wpdb;
		$wpdb->mice = '';
	}

	function test_basic() {
		$mickey = new Mouse( array( 'id' => 5, 'name' => 'Mickey', 'rating' => 11, 'cat_id' => 1, ) );
		$this->assertEquals( true, $mickey->validate() );
		$minnie = new Mouse( array( 'id' => 5, 'name' => '', 'rating' => 11, 'cat_id' => 1, ) );
		$this->assertEquals( false, $minnie->validate() );
	}

	function test_is_int() {
	   $callback = GP_Validators::get( 'int' );
	   $f = $callback['positive'];
	   $this->assertEquals( true, $f('0') );
	   $this->assertEquals( true, $f('1') );
	   $this->assertEquals( true, $f('-1') );
	   $this->assertEquals( true, $f('514') );
	   $this->assertEquals( true, $f('-514') );
	   $this->assertEquals( false, $f('aaa1aaa') );
	   $this->assertEquals( false, $f('2.3') );
	   $this->assertEquals( false, $f('aaa1') );
	   $this->assertEquals( false, $f('1aaa') );
	}

	function test_between() {
	   $callback = GP_Validators::get( 'between' );
	   $f = $callback['positive'];
	   $this->assertEquals( true, $f( 0, -1, 2 ) );
	}

	function test_one_of() {
		$callback = GP_Validators::get( 'one_of' );
		$f = $callback['positive'];
		$this->assertEquals( true, $f( 'a', array( 'a', 'b' ) ) );
		$this->assertEquals( false, $f( 'c', array( 'a', 'b' ) ) );
		$this->assertEquals( true, $f( 3, array( 1, 2, 3 ) ) );
		$this->assertEquals( false, $f( '1', array( 1, 2, 3 ) ) );
	}

	function test_is_ascii_string() {
		$callback = GP_Validators::get( 'consisting_only_of_ASCII_characters' );
		$f = $callback['positive'];
		$this->assertEquals( true, $f( 'a' ) );
		$this->assertEquals( true, $f( 'AbC' ) );
		$this->assertEquals( true, $f( 'foo bar' ) );
		$this->assertEquals( true, $f( '&' ) );
		$this->assertEquals( true, $f( '123abc' ) );
		$this->assertEquals( false, $f( 'äbc' ) );
		$this->assertEquals( false, $f( 'ãbc' ) );
	}

	function test_is_starting_or_ending_with_a_word_character() {
		$callback = GP_Validators::get( 'starting_or_ending_with_a_word_character' );
		$f = $callback['positive'];
		$this->assertEquals( true, $f( 'a' ) );
		$this->assertEquals( true, $f( 'foo bar' ) );
		$this->assertEquals( true, $f( 'a & b' ) );
		$this->assertEquals( false, $f( 'a ' ) );
		$this->assertEquals( false, $f( '-foo' ) );
		$this->assertEquals( false, $f( 'Hello world.' ) );
	}
}
