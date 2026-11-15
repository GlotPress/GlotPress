<?php

class GP_Test_Route_Translation_Row_Preview extends GP_UnitTestCase_Route {
	public $route_class = 'GP_Route_Translation';

	/**
	 * Renders the translations page of a set with a plural original.
	 *
	 * @param string $glossary 'none', 'empty' or 'with entries'.
	 * @return string The page output.
	 */
	private function render_plural_original( $glossary ) {
		$set = $this->factory->translation_set->create_with_project_and_locale();
		$this->factory->original->create(
			array(
				'project_id' => $set->project->id,
				'status'     => '+active',
				'singular'   => '%d Theme Update',
				'plural'     => '%d Theme Updates',
			)
		);

		if ( 'none' !== $glossary ) {
			$set_glossary = GP::$glossary->create( array( 'translation_set_id' => $set->id ) );
		}

		if ( 'with entries' === $glossary ) {
			GP::$glossary_entry->create(
				array(
					'glossary_id'    => $set_glossary->id,
					'term'           => 'theme',
					'part_of_speech' => 'noun',
					'translation'    => 'tema',
					'last_edited_by' => 1,
				)
			);
		}

		$this->route->translations_get( $set->project->path, $set->locale, $set->slug );

		return $this->route->template_output;
	}

	/**
	 * The placeholders of a plural original are marked once in the row preview, as in the singular.
	 *
	 * @dataProvider data_glossary
	 *
	 * @param string $glossary 'none', 'empty' or 'with entries'.
	 */
	function test_row_preview_marks_the_placeholders_of_a_plural_original_once( $glossary ) {
		$output = $this->render_plural_original( $glossary );

		$this->assertStringContainsString( '<span class="notranslate placeholder">%d</span>', $output );
		$this->assertStringNotContainsString( '<span class="notranslate placeholder"><span class="notranslate placeholder">', $output );
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	function data_glossary() {
		return array(
			'without a glossary'            => array( 'none' ),
			'with an empty glossary'        => array( 'empty' ),
			'with a glossary with an entry' => array( 'with entries' ),
		);
	}

	/**
	 * The editor tells when a translation was last changed, for example approved, if it isn't the date it was added.
	 */
	function test_editor_shows_the_date_a_translation_was_last_modified() {
		$set         = $this->factory->translation_set->create_with_project_and_locale();
		$original    = $this->factory->original->create( array( 'project_id' => $set->project->id, 'status' => '+active' ) );
		$translation = $this->factory->translation->create( array( 'translation_set_id' => $set->id, 'original_id' => $original->id, 'status' => 'current' ) );

		$this->set_translation_dates( $translation, '2026-03-01 10:00:00', '2026-03-01 10:00:00' );
		$this->assertStringNotContainsString( 'Last modified:', $this->render_set( $set ) );

		$this->set_translation_dates( $translation, '2026-03-01 10:00:00', '2026-04-15 08:30:00' );
		$output = $this->render_set( $set );
		$this->assertStringContainsString( 'Last modified:', $output );
		$this->assertStringContainsString( gp_format_user_date( '2026-04-15 08:30:00' ), $output );
	}

	private function set_translation_dates( $translation, $added, $modified ) {
		global $wpdb;
		$wpdb->update( $wpdb->gp_translations, array( 'date_added' => $added, 'date_modified' => $modified ), array( 'id' => $translation->id ) );
	}

	private function render_set( $set ) {
		$this->route->translations_get( $set->project->path, $set->locale, $set->slug );

		return $this->route->template_output;
	}
}
