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
}