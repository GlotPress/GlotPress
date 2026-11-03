<?php

class GP_Test_Route_Translation_Filter_Counts extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Translation';

	private function create_translation( $set, $original, $status, $args = array() ) {
		return $this->factory->translation->create(
			array_merge(
				array(
					'translation_set_id' => $set->id,
					'original_id'        => $original->id,
					'status'             => $status,
				),
				$args
			)
		);
	}

	/**
	 * Requests the filter counts of a translation set, as the editor does after a change.
	 *
	 * @param GP_Translation_Set $set The translation set.
	 * @return array The counts, keyed by filter.
	 */
	private function filter_counts( $set ) {
		// The editor endpoints don't count the set themselves.
		$this->assertArrayNotHasKey( 'X-GlotPress-Filter-Counts', $this->route->headers );

		ob_start();
		$this->do_route_request(
			function () use ( $set ) {
				$this->route->filter_counts_get( $set->project->path, $set->locale, $set->slug );
			}
		);
		$output = ob_get_clean();

		$this->assertSame( 'application/json', trim( $this->route->headers['Content-Type'] ) );

		return json_decode( $output, true );
	}

	function test_suggestion_for_a_translated_original_only_adds_a_waiting_translation() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
		$this->create_translation( $set, $original, 'current' );

		$this->set_normal_user_as_current();
		$_POST['original_id']        = $original->id;
		$_POST['translation']        = array( $original->id => array( 'Suggestion' ) );
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'add-translation_' . $original->id );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->translations_post( $set->project->path, $set->locale, $set->slug );
			}
		);

		$counts = $this->filter_counts( $set );
		$this->assertSame( '1', $counts['translated'] );
		$this->assertSame( '1', $counts['waiting'] );
		$this->assertSame( '0', $counts['untranslated'] );
	}

	function test_approving_a_suggestion_for_a_translated_original_keeps_the_translated_count() {
		$set      = $this->factory->translation_set->create_with_project_and_locale();
		$original = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
		$this->create_translation( $set, $original, 'current' );
		$waiting = $this->create_translation( $set, $original, 'waiting' );

		$this->become_validator_for_set( $set );
		$this->set_status( $set, $waiting, 'current' );

		$counts = $this->filter_counts( $set );
		$this->assertSame( '1', $counts['translated'] );
		$this->assertSame( '0', $counts['waiting'] );
	}

	function test_rejecting_the_only_current_translation_makes_the_original_untranslated() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
		$translation = $this->create_translation( $set, $original, 'current' );

		$this->become_validator_for_set( $set );
		$this->set_status( $set, $translation, 'rejected' );

		$counts = $this->filter_counts( $set );
		$this->assertSame( '0', $counts['translated'] );
		$this->assertSame( '1', $counts['untranslated'] );
	}

	function test_discarding_the_last_warning_lowers_the_warnings_count() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
		$translation = $this->create_translation(
			$set,
			$original,
			'current',
			array( 'warnings' => array( 0 => array( 'placeholders' => 'Missing %s placeholder in translation.' ) ) )
		);
		$this->assertSame( 1, $set->warnings_count() );

		$this->set_admin_user_as_current();
		$_POST['translation_id']     = $translation->id;
		$_POST['index']              = 0;
		$_POST['key']                = 'placeholders';
		$_REQUEST['_gp_route_nonce'] = wp_create_nonce( 'discard-warning_0placeholders' );

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->discard_warning( $set->project->path, $set->locale, $set->slug );
			}
		);

		$counts = $this->filter_counts( $set );
		$this->assertSame( '0', $counts['warnings'] );
	}

	function test_filter_counts_of_an_unknown_translation_set_is_a_404() {
		$set = $this->factory->translation_set->create_with_project_and_locale();

		$this->do_route_request(
			function () use ( $set ) {
				$this->route->filter_counts_get( $set->project->path, $set->locale, 'unknown' );
			}
		);

		$this->assertSame( 404, $this->route->http_status );
	}
}
