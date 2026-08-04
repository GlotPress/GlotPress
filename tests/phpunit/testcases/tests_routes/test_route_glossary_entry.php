<?php

class GP_Test_Route_Glossary_Entry extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary_Entry';

	/**
	 * Adding a glossary entry must target the glossary of the authorized set,
	 * not an arbitrary glossary named by a client-supplied glossary_id.
	 */
	function test_add_entry_cannot_target_a_glossary_of_another_set() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$authorized_glossary = GP::$glossary->create( array( 'translation_set_id' => $authorized_set->id ) );
		$other_glossary      = GP::$glossary->create( array( 'translation_set_id' => $other_set->id ) );

		$user = $this->become_validator_for_set( $authorized_set );

		$_POST['new_glossary_entry'] = array(
			'glossary_id'    => $other_glossary->id, // Points at a glossary the user is not authorized for.
			'term'           => 'security',
			'part_of_speech' => 'noun',
			'translation'    => 'Sicherheit',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-glossary-entry_' . $authorized_set->project->path . $authorized_set->locale . $authorized_set->slug );

		$this->route->glossary_entry_add_post( $authorized_set->project->path, $authorized_set->locale, $authorized_set->slug );

		// The other set's glossary must be untouched.
		$this->assertCount( 0, GP::$glossary_entry->by_glossary_id( $other_glossary->id ) );

		// The entry lands in the authorized set's glossary instead.
		$authorized_entries = GP::$glossary_entry->by_glossary_id( $authorized_glossary->id );
		$this->assertCount( 1, $authorized_entries );
		$this->assertEquals( 'security', $authorized_entries[0]->term );
	}

	/**
	 * Editing a glossary entry must not move it into a glossary of another set via a
	 * client-supplied glossary_id.
	 */
	function test_edit_entry_cannot_move_it_to_a_glossary_of_another_set() {
		$authorized_set = $this->factory->translation_set->create_with_project_and_locale();
		$other_set      = $this->factory->translation_set->create_with_project_and_locale();

		$authorized_glossary = GP::$glossary->create( array( 'translation_set_id' => $authorized_set->id ) );
		$other_glossary      = GP::$glossary->create( array( 'translation_set_id' => $other_set->id ) );

		$user = $this->become_validator_for_set( $authorized_set );

		$entry = GP::$glossary_entry->create(
			array(
				'glossary_id'    => $authorized_glossary->id,
				'term'           => 'security',
				'part_of_speech' => 'noun',
				'translation'    => 'Sicherheit',
				'last_edited_by' => $user,
			)
		);

		$_POST['glossary_entry'] = array(
			$entry->id => array(
				'glossary_entry_id' => $entry->id,
				'glossary_id'       => $other_glossary->id, // Points at a glossary the user is not authorized for.
				'term'              => 'edited',
				'part_of_speech'    => 'noun',
				'translation'       => 'Bearbeitet',
			),
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'edit-glossary-entry_' . $entry->id );

		$this->do_route_request(
			function () use ( $authorized_set ) {
				$this->route->glossary_entries_post( $authorized_set->project->path, $authorized_set->locale, $authorized_set->slug );
			}
		);

		$reloaded = GP::$glossary_entry->get( $entry->id );

		// The entry stays in its own glossary, the legitimate edit still applies, and the other glossary is untouched.
		$this->assertEquals( $authorized_glossary->id, (int) $reloaded->glossary_id );
		$this->assertEquals( 'edited', $reloaded->term );
		$this->assertCount( 0, GP::$glossary_entry->by_glossary_id( $other_glossary->id ) );
	}

	/**
	 * Reading a locale glossary that does not exist must not create it; only an
	 * explicit, authorized action may.
	 */
	function test_reading_a_missing_locale_glossary_does_not_create_it() {
		$locale = $this->factory->locale->create();

		$this->do_route_request(
			function () use ( $locale ) {
				$this->route->glossary_entries_get( '/languages', $locale->slug, 'aaaatest' );
			}
		);

		$this->assertEmpty(
			GP::$translation_set->by_project_id_slug_and_locale( 0, 'aaaatest', $locale->slug ),
			'Reading a missing locale glossary must not create a translation set.'
		);
	}

	/**
	 * A locale approver (the permission GTEs/locale managers hold on wordpress.org,
	 * granted through a filter rather than a stored row) can create the locale
	 * glossary through the explicit action.
	 */
	function test_a_locale_approver_can_create_the_locale_glossary() {
		$locale    = $this->factory->locale->create();
		$object_id = '0|' . $locale->slug . '|default';

		add_filter(
			'gp_pre_can_user',
			function ( $verdict, $args ) use ( $object_id ) {
				if ( 'approve' === $args['action'] && $object_id === $args['object_id'] ) {
					return true;
				}
				return $verdict;
			},
			10,
			2
		);
		$this->set_normal_user_as_current();

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'create-locale-glossary_' . $locale->slug . 'default' );

		$this->do_route_request(
			function () use ( $locale ) {
				$this->route->glossary_create_post( '/languages', $locale->slug, 'default' );
			}
		);

		$set = GP::$translation_set->by_project_id_slug_and_locale( 0, 'default', $locale->slug );
		$this->assertNotEmpty( $set, 'A locale approver can create the locale glossary.' );
		$this->assertNotEmpty( GP::$glossary->by_set_id( $set->id ) );
	}

	/**
	 * A user without approve on the locale must not create the locale glossary.
	 */
	function test_a_non_approver_cannot_create_the_locale_glossary() {
		$locale = $this->factory->locale->create();

		$this->set_normal_user_as_current();

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'create-locale-glossary_' . $locale->slug . 'default' );

		$this->do_route_request(
			function () use ( $locale ) {
				$this->route->glossary_create_post( '/languages', $locale->slug, 'default' );
			}
		);

		$this->assertEmpty(
			GP::$translation_set->by_project_id_slug_and_locale( 0, 'default', $locale->slug ),
			'A user without approve on the locale must not create the locale glossary.'
		);
	}

	/**
	 * An anonymous request must not create the locale glossary.
	 */
	function test_an_anonymous_user_cannot_create_the_locale_glossary() {
		$locale = $this->factory->locale->create();
		wp_set_current_user( 0 );

		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'create-locale-glossary_' . $locale->slug . 'default' );

		$this->do_route_request(
			function () use ( $locale ) {
				$this->route->glossary_create_post( '/languages', $locale->slug, 'default' );
			}
		);

		$this->assertEmpty(
			GP::$translation_set->by_project_id_slug_and_locale( 0, 'default', $locale->slug ),
			'An anonymous user must not create the locale glossary.'
		);
	}

	/**
	 * A missing nonce must block creation even for a locale approver.
	 */
	function test_a_missing_nonce_blocks_locale_glossary_creation() {
		$locale    = $this->factory->locale->create();
		$object_id = '0|' . $locale->slug . '|default';

		add_filter(
			'gp_pre_can_user',
			function ( $verdict, $args ) use ( $object_id ) {
				if ( 'approve' === $args['action'] && $object_id === $args['object_id'] ) {
					return true;
				}
				return $verdict;
			},
			10,
			2
		);
		$this->set_normal_user_as_current();

		unset( $_REQUEST['_gp_route_nonce'] );

		$this->do_route_request(
			function () use ( $locale ) {
				$this->route->glossary_create_post( '/languages', $locale->slug, 'default' );
			}
		);

		$this->assertEmpty(
			GP::$translation_set->by_project_id_slug_and_locale( 0, 'default', $locale->slug ),
			'A missing nonce must block locale glossary creation.'
		);
	}

	/**
	 * A validator of a sub-project set must not add entries to a glossary
	 * inherited from an ancestor project, which they hold no permission on.
	 */
	function test_add_entry_cannot_reach_a_parent_glossary_from_a_sub_project_set() {
		$parent_set      = $this->factory->translation_set->create_with_project_and_locale();
		$parent_glossary = GP::$glossary->create( array( 'translation_set_id' => $parent_set->id ) );

		$child_project = $this->factory->project->create( array( 'parent_project_id' => $parent_set->project->id ) );
		$child_set     = $this->factory->translation_set->create(
			array(
				'project_id' => $child_project->id,
				'locale'     => $parent_set->locale,
				'slug'       => $parent_set->slug,
			)
		);
		$child_set->project = $child_project;

		$this->become_validator_for_set( $child_set );

		$_POST['new_glossary_entry'] = array(
			'term'           => 'security',
			'part_of_speech' => 'noun',
			'translation'    => 'Sicherheit',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-glossary-entry_' . $child_project->path . $child_set->locale . $child_set->slug );

		$this->route->glossary_entry_add_post( $child_project->path, $child_set->locale, $child_set->slug );

		$this->assertCount( 0, GP::$glossary_entry->by_glossary_id( $parent_glossary->id ), 'A sub-project validator must not write into the parent project glossary.' );
	}

	/**
	 * A validator of the parent project can still add entries to the parent
	 * glossary through a sub-project that inherits it.
	 */
	function test_add_entry_reaches_a_parent_glossary_for_a_parent_validator() {
		$parent_set      = $this->factory->translation_set->create_with_project_and_locale();
		$parent_glossary = GP::$glossary->create( array( 'translation_set_id' => $parent_set->id ) );

		$child_project = $this->factory->project->create( array( 'parent_project_id' => $parent_set->project->id ) );
		$child_set     = $this->factory->translation_set->create(
			array(
				'project_id' => $child_project->id,
				'locale'     => $parent_set->locale,
				'slug'       => $parent_set->slug,
			)
		);

		$this->become_validator_for_set( $parent_set );

		$_POST['new_glossary_entry'] = array(
			'term'           => 'security',
			'part_of_speech' => 'noun',
			'translation'    => 'Sicherheit',
		);
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-glossary-entry_' . $child_project->path . $child_set->locale . $child_set->slug );

		$this->route->glossary_entry_add_post( $child_project->path, $child_set->locale, $child_set->slug );

		$this->assertCount( 1, GP::$glossary_entry->by_glossary_id( $parent_glossary->id ), 'A parent validator can add to the parent glossary.' );
	}
}
