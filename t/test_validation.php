<?php
require_once('init.php');

class Mouse extends GP_Thing {
	var $table_basename = 'mice';
	var $field_names = array( 'id', 'name', 'rating', 'cat_id' );
	var $non_updatable_attributes = array( 'id', );

	function restrict_fields( $project ) {
		$project->name_should_not_be('empty');
		$project->rating_should_be('positive_int');
	}

	function normalize_fields( $args ) {
		$args = (array)$args;
		if ( isset( $args['cat_id'] ) ) {
			$args['cat_id'] = $this->force_false_to_null( $args['cat_id'] );
		}
		return $args;
	}
}

$gpdb->mice = '';


class GP_Test_Validation extends GP_UnitTestCase {
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

}
