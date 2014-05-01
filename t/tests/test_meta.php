<?php

class GP_Test_Meta extends GP_UnitTestCase {

	function test_update_meta_should_set_meta() {
		gp_update_meta( '1', 'foo', 'bar', 'thing' );
		$this->assertEquals( 'bar', gp_get_meta( 'thing', '1', 'foo' ) );
	}

	function test_delete_meta_without_value_should_delete_meta() {
		gp_update_meta( '1', 'foo', 'bar', 'thing' );
		gp_delete_meta( '1', 'foo', null, 'thing' );
		$this->assertEquals( null, gp_get_meta( 'thing', '1', 'foo' ) );
	}

	function test_delete_meta_with_value_should_delete_only_meta_with_value() {
		gp_update_meta( '1', 'foo', 'bar', 'thing' );
		gp_delete_meta( '1', 'foo', 'bar', 'thing' );
		$this->assertEquals( null, gp_get_meta( 'thing', '1', 'foo' ) );

		gp_update_meta( '1', 'foo', 'foo', 'thing' );
		gp_delete_meta( '1', 'foo', 'bar', 'thing' );
		$this->assertNotEquals( null, gp_get_meta( 'thing', '1', 'foo' ) );
	}

}