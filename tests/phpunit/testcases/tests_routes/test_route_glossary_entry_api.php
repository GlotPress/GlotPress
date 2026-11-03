<?php

class GP_Test_Route_Glossary_Entry_Api extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Glossary_Entry';

	public $set;
	public $glossary;
	public $editor;

	function setUp(): void {
		parent::setUp();
		$this->route->api = true;
		$this->editor     = $this->factory->user->create();

		$this->set      = $this->factory->translation_set->create_with_project_and_locale();
		$this->glossary = GP::$glossary->create(
			array(
				'translation_set_id' => $this->set->id,
				'description'        => 'Project glossary',
			)
		);

		$this->create_entry( $this->glossary, 'plugin', 'noun', 'extension', 'Not "plugiciel".' );
		$this->create_entry( $this->glossary, 'Post', 'noun', 'article' );
		$this->create_entry( $this->glossary, 'post', 'verb', 'publier' );
	}

	function tearDown(): void {
		unset( $_GET['term'], $_GET['extended'] );
		parent::tearDown();
	}

	function create_entry( $glossary, $term, $part_of_speech, $translation, $comment = '' ) {
		return GP::$glossary_entry->create(
			array(
				'glossary_id'    => $glossary->id,
				'term'           => $term,
				'part_of_speech' => $part_of_speech,
				'translation'    => $translation,
				'comment'        => $comment,
				'last_edited_by' => $this->editor,
			)
		);
	}

	function create_locale_glossary() {
		$locale_set = GP::$translation_set->create(
			array(
				'name'       => 'Locale glossary set',
				'slug'       => $this->set->slug,
				'project_id' => 0,
				'locale'     => $this->set->locale,
			)
		);

		return GP::$glossary->create( array( 'translation_set_id' => $locale_set->id ) );
	}

	function create_project_without_glossary() {
		$project = $this->factory->project->create();
		$this->factory->translation_set->create(
			array(
				'project_id' => $project->id,
				'locale'     => $this->set->locale,
				'slug'       => $this->set->slug,
			)
		);

		return $project;
	}

	function set_date_modified( $entry, $date ) {
		global $wpdb;

		$wpdb->update( $wpdb->gp_glossary_entries, array( 'date_modified' => $date ), array( 'id' => $entry->id ) );
	}

	function get_glossary( $project_path = null ) {
		$this->route->glossary_entries_get( $project_path ?? $this->set->project->path, $this->set->locale, $this->set->slug );

		return $this->api_response();
	}

	function test_api_lists_all_entries() {
		$response = $this->get_glossary();

		$this->assertTemplateLoadedIs( 'glossary-view' );
		$this->assertSame( $this->glossary->id, $response->glossary->id );
		$this->assertSame( 'Project glossary', $response->glossary->description );
		$this->assertSame( $this->set->locale, $response->locale );
		$this->assertSame( $this->set->slug, $response->translation_set );
		$this->assertNull( $response->term );
		$this->assertFalse( $response->extended );
		$this->assertCount( 3, $response->entries );
	}

	function test_api_entry_fields() {
		$_GET['term'] = 'plugin';
		$response     = $this->get_glossary();

		$this->assertCount( 1, $response->entries );
		$entry = $response->entries[0];

		$this->assertSame( 'plugin', $entry->term );
		$this->assertSame( 'noun', $entry->part_of_speech );
		$this->assertSame( 'extension', $entry->translation );
		$this->assertSame( 'Not "plugiciel".', $entry->comment );
		$this->assertSame( $this->glossary->id, $entry->glossary_id );
		$this->assertIsInt( $entry->id );
		$this->assertNotEmpty( $entry->date_modified );
	}

	function test_api_does_not_expose_editors() {
		$response = $this->get_glossary();

		$this->assertCount( 3, $response->entries );
		foreach ( $response->entries as $entry ) {
			$this->assertObjectNotHasProperty( 'last_edited_by', $entry );
			$this->assertObjectNotHasProperty( 'user_login', $entry );
			$this->assertObjectNotHasProperty( 'user_display_name', $entry );
		}
	}

	function test_api_term_filter_is_case_insensitive_and_returns_every_part_of_speech() {
		$_GET['term'] = 'POST';
		$response     = $this->get_glossary();

		$this->assertSame( 'POST', $response->term );
		$this->assertEqualsCanonicalizing(
			array( 'article', 'publier' ),
			wp_list_pluck( $response->entries, 'translation' )
		);
	}

	function test_api_term_filter_matches_the_whole_term() {
		$_GET['term'] = 'plug';
		$response     = $this->get_glossary();

		$this->assertSame( array(), $response->entries );
	}

	function test_api_term_filter_ignores_surrounding_whitespace() {
		$_GET['term'] = '  plugin ';
		$response     = $this->get_glossary();

		$this->assertCount( 1, $response->entries );
	}

	function test_api_non_string_term_is_ignored() {
		$_GET['term'] = array( 'plugin' );
		$response     = $this->get_glossary();

		$this->assertNull( $response->term );
		$this->assertCount( 3, $response->entries );
	}

	function test_api_term_filter_uses_the_inherited_parent_glossary() {
		$child_project = $this->factory->project->create( array( 'parent_project_id' => $this->set->project->id ) );
		$this->factory->translation_set->create(
			array(
				'project_id' => $child_project->id,
				'locale'     => $this->set->locale,
				'slug'       => $this->set->slug,
			)
		);

		$_GET['term'] = 'plugin';
		$response     = $this->get_glossary( $child_project->path );

		$this->assertSame( $this->glossary->id, $response->glossary->id );
		$this->assertCount( 1, $response->entries );
	}

	function test_api_locale_glossary() {
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['term'] = 'theme';
		$this->route->glossary_entries_get( '/languages', $this->set->locale, $this->set->slug );
		$response = $this->api_response();

		$this->assertSame( $locale_glossary->id, $response->glossary->id );
		$this->assertCount( 1, $response->entries );
		$this->assertSame( 'thème', $response->entries[0]->translation );
	}

	function test_api_extended_merges_the_locale_glossary_with_project_entries_first() {
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'plugin', 'noun', 'module' );
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['extended'] = '1';
		$response         = $this->get_glossary();

		$this->assertTrue( $response->extended );
		$this->assertSame( $this->glossary->id, $response->glossary->id );
		$this->assertCount( 4, $response->entries );

		$translations = wp_list_pluck( $response->entries, 'translation', 'term' );
		$this->assertSame( 'extension', $translations['plugin'] );
		$this->assertSame( 'thème', $translations['theme'] );

		$glossary_ids = wp_list_pluck( $response->entries, 'glossary_id', 'term' );
		$this->assertSame( $locale_glossary->id, $glossary_ids['theme'] );
	}

	function test_api_extended_applies_the_term_filter_to_both_glossaries() {
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'post', 'verb', 'envoyer' );
		$this->create_entry( $locale_glossary, 'post', 'adjective', 'postérieur' );
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['extended'] = '1';
		$_GET['term']     = 'post';
		$response         = $this->get_glossary();

		$this->assertEqualsCanonicalizing(
			array( 'article', 'publier', 'postérieur' ),
			wp_list_pluck( $response->entries, 'translation' )
		);
	}

	function test_api_extended_falls_back_to_the_locale_glossary() {
		$other_project   = $this->create_project_without_glossary();
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['extended'] = '1';
		$response         = $this->get_glossary( $other_project->path );

		$this->assertTrue( $response->extended );
		$this->assertSame( $locale_glossary->id, $response->glossary->id );
		$this->assertCount( 1, $response->entries );
	}

	function test_api_extended_on_the_locale_glossary_does_not_duplicate_entries() {
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['extended'] = '1';
		$this->route->glossary_entries_get( '/languages', $this->set->locale, $this->set->slug );
		$response = $this->api_response();

		$this->assertTrue( $response->extended );
		$this->assertSame( $locale_glossary->id, $response->glossary->id );
		$this->assertCount( 1, $response->entries );
	}

	function test_api_extended_merges_an_inherited_parent_glossary_with_the_locale_glossary() {
		$child_project = $this->factory->project->create( array( 'parent_project_id' => $this->set->project->id ) );
		$this->factory->translation_set->create(
			array(
				'project_id' => $child_project->id,
				'locale'     => $this->set->locale,
				'slug'       => $this->set->slug,
			)
		);
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'plugin', 'noun', 'module' );
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$_GET['extended'] = '1';
		$response         = $this->get_glossary( $child_project->path );

		$this->assertSame( $this->glossary->id, $response->glossary->id );
		$this->assertCount( 4, $response->entries );

		$translations = wp_list_pluck( $response->entries, 'translation', 'term' );
		$this->assertSame( 'extension', $translations['plugin'] );
		$this->assertSame( 'thème', $translations['theme'] );
	}

	function test_api_without_extended_ignores_the_locale_glossary() {
		$other_project   = $this->create_project_without_glossary();
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$this->do_route_request(
			function () use ( $other_project ) {
				$this->route->glossary_entries_get( $other_project->path, $this->set->locale, $this->set->slug );
			}
		);

		$this->assert404();
	}

	function test_api_sends_the_most_recent_last_modified_date() {
		$locale_glossary = $this->create_locale_glossary();
		$locale_entry    = $this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		foreach ( GP::$glossary_entry->by_glossary_id( $this->glossary->id ) as $entry ) {
			$this->set_date_modified( $entry, '2020-01-01 10:00:00' );
		}
		$this->set_date_modified( $locale_entry, '2021-06-15 12:30:00' );

		$this->get_glossary();
		$this->assertSame( 'Wed, 01 Jan 2020 10:00:00 GMT', trim( $this->route->headers['Last-Modified'] ) );

		$this->route->headers = array();
		$_GET['extended']     = '1';
		$this->get_glossary();
		$this->assertSame( 'Tue, 15 Jun 2021 12:30:00 GMT', trim( $this->route->headers['Last-Modified'] ) );
	}

	function test_api_missing_glossary_is_a_json_404() {
		$other_set = $this->factory->translation_set->create_with_project_and_locale();

		$this->do_route_request(
			function () use ( $other_set ) {
				$this->route->glossary_entries_get( $other_set->project->path, $other_set->locale, $other_set->slug );
			}
		);

		$this->assert404();
		$this->assertFalse( $this->api_response()->success );
	}

	function test_html_view_ignores_the_api_parameters() {
		$locale_glossary = $this->create_locale_glossary();
		$this->create_entry( $locale_glossary, 'theme', 'noun', 'thème' );

		$this->route->api = false;
		$_GET['term']     = 'post';
		$_GET['extended'] = '1';

		$this->route->glossary_entries_get( $this->set->project->path, $this->set->locale, $this->set->slug );

		$this->assertTemplateLoadedIs( 'glossary-view' );
		$this->assertStringContainsString( 'extension', $this->route->template_output );
		$this->assertStringNotContainsString( 'thème', $this->route->template_output );
		$this->assertArrayNotHasKey( 'Last-Modified', (array) $this->route->headers );
	}
}
