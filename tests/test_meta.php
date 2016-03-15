<?php

/**
 * @group meta
 */
class GP_Test_Meta extends GP_UnitTestCase {

	/**
	 * @dataProvider data_meta_keys
	 */
	function test_gp_sanitize_meta_key( $expected, $meta_key ) {
		$this->assertSame( $expected, gp_sanitize_meta_key( $meta_key ) );
	}

	function data_meta_keys() {
		return array(
			array( 'foo', 'foo' ),
			array( 'fooBar', 'fooBar' ),
			array( 'foobar', 'foo-bar' ),
			array( 'foobar', 'foo.bar' ),
			array( 'foobar', 'foo:bar' ),
			array( 'foo_bar', 'foo_bar' ),
			array( 'foobar123', 'foobar123' ),
			array( 'foobar', 'foo?#+bar' ),
		);
	}

	function test_gp_get_meta_returns_false_for_falsely_object_ids() {
		$this->assertFalse( gp_get_meta( 'foo', null ) );
		$this->assertFalse( gp_get_meta( 'foo', false ) );
		$this->assertFalse( gp_get_meta( 'foo', 0 ) );
		$this->assertFalse( gp_get_meta( 'foo', '' ) );
		$this->assertFalse( gp_get_meta( 'foo', 'bar' ) );
	}

	function test_gp_get_meta_returns_false_for_falsely_object_types() {
		$this->assertFalse( gp_get_meta( null, 1 ) );
		$this->assertFalse( gp_get_meta( false, 1 ) );
		$this->assertFalse( gp_get_meta( '', 1 ) );
		$this->assertFalse( gp_get_meta( 0, 1 ) );
	}

	function test_gp_update_meta_returns_false_for_falsely_object_ids() {
		$this->assertFalse( gp_update_meta( null, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_update_meta( false, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_update_meta( 0, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_update_meta( '', 'key', 'value', 'type' ) );
		$this->assertFalse( gp_update_meta( 'bar', 'key', 'value', 'type' ) );
	}

	function test_gp_delete_meta_returns_false_for_falsely_object_ids() {
		$this->assertFalse( gp_delete_meta( null, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_delete_meta( false, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_delete_meta( 0, 'key', 'value', 'type' ) );
		$this->assertFalse( gp_delete_meta( '', 'key', 'value', 'type' ) );
		$this->assertFalse( gp_delete_meta( 'bar', 'key', 'value', 'type' ) );
	}

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

	function test_gp_update_meta_does_not_update_if_prev_value_equals_new_value() {
		$this->assertInternalType( 'int', gp_update_meta( '1', 'foo', 'foo', 'thing' ) );
		$this->assertTrue( gp_update_meta( '1', 'foo', 'foo', 'thing' ) ); // @todo Is this the correct return value?
	}

	/**
	 * @ticket 480
	 */
	function test_get_meta_uses_cache() {
		global $wpdb;

		gp_update_meta( '1', 'foo', 'bar', 'thing' );

		$num_queries = $wpdb->num_queries;

		// Cache is not primed, expect 1 query.
		gp_get_meta( 'thing', '1', 'foo' );
		$this->assertEquals( $num_queries + 1, $wpdb->num_queries );

		$num_queries = $wpdb->num_queries;

		// Cache is primed, expect no queries.
		gp_get_meta( 'thing', '1', 'foo' );
		$this->assertEquals( $num_queries, $wpdb->num_queries );
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
