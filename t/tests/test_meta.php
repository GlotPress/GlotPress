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

	/**
	 * @ticket 480
	 */
	function test_get_meta_uses_cache() {
		global $gpdb;

		gp_update_meta( '1', 'foo', 'bar', 'thing' );

		$num_queries = $gpdb->num_queries;

		// Cache is not primed, expect 1 query.
		gp_get_meta( 'thing', '1', 'foo' );
		$this->assertEquals( $num_queries + 1, $gpdb->num_queries );

		$num_queries = $gpdb->num_queries;

		// Cache is primed, expect no queries.
		gp_get_meta( 'thing', '1', 'foo' );
		$this->assertEquals( $num_queries, $gpdb->num_queries );
	}

	/**
	 * @ticket 480
	 */
	function test_get_meta_without_meta_key() {
		gp_update_meta( '1', 'key1', 'foo', 'thing' );
		gp_update_meta( '1', 'key2', 'foo', 'thing' );

		$meta = gp_get_meta( 'thing', '1' );
		$this->assertCount( 2, $meta );
		$this->assertEqualSets( array( 'key1', 'key2' ), array_keys( $meta ) );
	}
}
