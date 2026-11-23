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

	function test_gp_update_meta_updates_an_existing_meta_value() {
		$this->assertIsInt( gp_update_meta( '1', 'key', 'value-1', 'thing' ) );
		$this->assertTrue( gp_update_meta( '1', 'key', 'value-2', 'thing'  ) );
		$this->assertSame( 'value-2', gp_get_meta( 'thing', '1', 'key' ) );
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
		$this->assertIsInt( gp_update_meta( '1', 'foo', 'foo', 'thing' ) );
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

	/**
	 * Adds a meta value to an object.
	 *
	 * @param string $object_type The object type.
	 * @param int    $object_id   The object ID.
	 */
	private function add_meta( $object_type, $object_id ) {
		gp_update_meta( $object_id, 'foo', "bar of $object_type $object_id", $object_type );

		// Put the meta in the cache.
		$this->assertSame( "bar of $object_type $object_id", gp_get_meta( $object_type, $object_id, 'foo' ) );
	}

	/**
	 * Counts the meta rows of an object, bypassing the cache.
	 *
	 * @param string $object_type The object type.
	 * @param int    $object_id   The object ID.
	 * @return int The number of meta rows.
	 */
	private function count_meta( $object_type, $object_id ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->gp_meta WHERE object_type = %s AND object_id = %d", $object_type, $object_id ) );
	}

	function test_deleting_a_project_deletes_its_meta_and_the_meta_of_its_objects() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$translation = $this->factory->translation->create_with_original_for_translation_set( $set );
		$glossary    = GP::$glossary->create_and_select( array( 'translation_set_id' => $set->id ) );
		$entry       = GP::$glossary_entry->create_and_select(
			array(
				'glossary_id'    => $glossary->id,
				'term'           => 'term',
				'part_of_speech' => 'noun',
				'translation'    => 'termo',
			)
		);
		$sub_project = $this->factory->project->create( array( 'parent_project_id' => $set->project_id ) );

		$objects = array(
			'project'         => $set->project_id,
			'translation_set' => $set->id,
			'original'        => $translation->original_id,
			'translation'     => $translation->id,
			'glossary'        => $glossary->id,
			'glossary_entry'  => $entry->id,
		);
		foreach ( $objects as $object_type => $object_id ) {
			$this->add_meta( $object_type, $object_id );
		}
		$this->add_meta( 'project', $sub_project->id );

		GP::$project->get( $set->project_id )->delete();

		foreach ( $objects as $object_type => $object_id ) {
			$this->assertSame( 0, $this->count_meta( $object_type, $object_id ), "The meta of the $object_type." );
			$this->assertSame( array(), gp_get_meta( $object_type, $object_id ), "The cached meta of the $object_type." );
		}
		$this->assertSame( 0, $this->count_meta( 'project', $sub_project->id ), 'The meta of the sub-project.' );
	}

	function test_deleting_an_object_keeps_the_meta_of_other_objects() {
		$project = $this->factory->project->create();
		$other   = $this->factory->project->create();

		$this->add_meta( 'project', $project->id );
		$this->add_meta( 'project', $other->id );
		// Another object type with the same ID, and a type of a plugin.
		$this->add_meta( 'original', $project->id );
		$this->add_meta( 'plugin_object', $project->id );
		$this->add_meta( 'gp_option', $project->id );

		$project->delete();

		$this->assertSame( 0, $this->count_meta( 'project', $project->id ) );
		$this->assertSame( 1, $this->count_meta( 'project', $other->id ) );
		$this->assertSame( 1, $this->count_meta( 'original', $project->id ) );
		$this->assertSame( 1, $this->count_meta( 'plugin_object', $project->id ) );
		$this->assertSame( 1, $this->count_meta( 'gp_option', $project->id ) );
	}

	function test_deleting_many_objects_deletes_their_meta() {
		$set   = $this->factory->translation_set->create_with_project_and_locale();
		$first = $this->factory->translation->create_with_original_for_translation_set( $set, array( 'translation_0' => '100% done' ) );
		$other = $this->factory->translation->create_with_original_for_translation_set( $set, array( 'translation_0' => 'Kept' ) );

		$this->add_meta( 'translation', $first->id );
		$this->add_meta( 'translation', $other->id );

		// A condition with a % sign, which must not be taken as a placeholder.
		GP::$translation->delete_many( array( 'translation_0' => '100% done' ) );

		$this->assertSame( 0, $this->count_meta( 'translation', $first->id ) );
		$this->assertSame( 1, $this->count_meta( 'translation', $other->id ) );
		$this->assertNotFalse( GP::$translation->get( $other->id ) );
	}

	function test_deleting_an_object_without_meta() {
		$project = $this->factory->project->create();

		$this->assertNotFalse( $project->delete() );
		$this->assertFalse( GP::$project->get( $project->id ) );
	}

	function test_a_failed_delete_keeps_the_meta() {
		global $wpdb;

		$project = $this->factory->project->create();
		$this->add_meta( 'project', $project->id );

		// Break the delete of the project row only.
		$break_delete = function ( $query ) use ( $wpdb ) {
			return 0 === strpos( $query, "DELETE FROM $wpdb->gp_projects" ) ? 'DELETE FROM not_a_table' : $query;
		};
		add_filter( 'query', $break_delete );
		$suppress = $wpdb->suppress_errors( true );

		$result = GP::$project->delete_many( array( 'id' => $project->id ) );

		$wpdb->suppress_errors( $suppress );
		remove_filter( 'query', $break_delete );

		$this->assertFalse( $result );
		$this->assertNotFalse( GP::$project->get( $project->id ) );
		$this->assertSame( 1, $this->count_meta( 'project', $project->id ) );
	}
}
