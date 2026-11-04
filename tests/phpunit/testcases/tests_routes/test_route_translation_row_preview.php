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

		$this->assertStringNotContainsString( '<span class="notranslate placeholder"><span class="notranslate placeholder">', $output );
		$this->assertSame( 2, substr_count( $output, '<span class="original-text"><span class="notranslate placeholder">%d</span>' ) );
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
}
